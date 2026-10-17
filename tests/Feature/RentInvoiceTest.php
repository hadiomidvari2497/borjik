<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Unit;
use App\Models\User;
use App\Services\Sms\SmsGateway;
use App\Support\CurrentBuilding;
use App\Support\JalaliDate;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** صدور خودکار صورت‌حساب اجاره‌ی ماهانه (rent:issue-invoices) و صدور اولیه هنگام ثبت قرارداد. */
class RentInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $tenant;
    private Building $building;
    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);

        $this->app->instance(SmsGateway::class, new class implements SmsGateway {
            public function send(string $mobile, string $text): string
            {
                return 'fake';
            }
        });

        $this->manager = User::create(['mobile' => '09120000001', 'first_name' => 'مدیر', 'last_name' => 'تست', 'status' => 'active', 'profile_completed_at' => now()]);
        $this->tenant = User::create(['mobile' => '09120000002', 'first_name' => 'مستأجر', 'last_name' => 'تست', 'status' => 'active', 'profile_completed_at' => now()]);

        $plan = SubscriptionPlan::create(['name' => 'p', 'units_limit' => 20, 'duration_months' => 1, 'price' => 0]);
        $sub = Subscription::create(['user_id' => $this->manager->id, 'plan_id' => $plan->id, 'units_limit' => 20, 'starts_at' => now()->subDay(), 'expires_at' => now()->addYear(), 'status' => 'active']);
        $this->building = Building::create(['name' => 'برج آسمان', 'code' => '11111', 'created_by' => $this->manager->id, 'subscription_id' => $sub->id]);

        foreach ([[$this->manager, 'owner'], [$this->tenant, 'tenant']] as [$u, $role]) {
            BuildingUserRole::create(['building_id' => $this->building->id, 'user_id' => $u->id, 'role_id' => Role::where('key', $role)->value('id'), 'status' => 'active']);
        }

        app(CurrentBuilding::class)->actingAs($this->building->id, function () {
            $block = Block::create(['number' => 1, 'name' => 'الف', 'floors_count' => 5]);
            $this->unit = Unit::create(['block_id' => $block->id, 'number' => '1', 'area_m2' => 90, 'status' => 'vacant', 'residents_count' => 0]);
        });
    }

    private function as(User $u)
    {
        return $this->actingAs($u)->withSession(['current_building_id' => $this->building->id]);
    }

    private function rent(array $over = [])
    {
        return $this->as($this->manager)->post(route('contracts.store'), $over + [
            'type' => 'rent', 'unit_id' => $this->unit->id, 'user_id' => $this->tenant->id,
            'deposit_amount' => '۱۰۰٬۰۰۰٬۰۰۰', 'monthly_rent' => '۵٬۰۰۰٬۰۰۰',
            'signed_at' => JalaliDate::format(today()), 'end_date' => JalaliDate::format(today()->addYear()), 'charge_payer' => 'tenant',
        ]);
    }

    private function runCommand(): void
    {
        $this->artisan('rent:issue-invoices')->assertSuccessful();
    }

    private function rentInvoices()
    {
        return DB::table('invoices')->where('type', 'rent')->orderBy('id')->get();
    }

    private function day(string $dbDate): string
    {
        return substr($dbDate, 0, 10);
    }

    public function test_registering_a_rent_issues_the_first_invoice_right_away(): void
    {
        $this->rent()->assertSessionHasNoErrors();

        $contractId = (int) DB::table('contracts')->value('id');
        $invoices = $this->rentInvoices();

        $this->assertCount(1, $invoices);
        $inv = $invoices[0];
        $this->assertSame($contractId, (int) $inv->contract_id);
        $this->assertSame($this->unit->id, (int) $inv->unit_id);
        $this->assertSame($this->tenant->id, (int) $inv->payer_user_id);
        $this->assertSame(5000000, (int) $inv->amount);
        $this->assertSame(0, (int) $inv->paid_amount);
        $this->assertSame('unpaid', $inv->status);
        $this->assertSame(today()->toDateString(), $this->day($inv->issued_at));
        $this->assertSame(today()->addDays(10)->toDateString(), $this->day($inv->due_date)); // مهلتِ پیش‌فرض ۱۰ روز
        $this->assertStringStartsWith('RT-'.$contractId.'-', $inv->number);

        $this->assertSame(1, DB::table('sms_logs')->where('template_key', 'rent_invoice_issued')->where('user_id', $this->tenant->id)->count());
        $this->as($this->manager)->get(route('contracts.show', $contractId))->assertOk()->assertSee($inv->number);
    }

    public function test_running_the_command_again_does_not_duplicate_invoices(): void
    {
        $this->rent();
        $this->runCommand();
        $this->runCommand();

        $this->assertCount(1, $this->rentInvoices());
        $this->assertSame(1, DB::table('sms_logs')->where('template_key', 'rent_invoice_issued')->count());
    }

    public function test_missed_months_are_issued_once_the_date_arrives(): void
    {
        $this->rent();

        $this->travelTo(now()->addDays(40));
        $this->runCommand();
        $this->runCommand();

        $invoices = $this->rentInvoices();
        $this->assertCount(2, $invoices); // ماه عقد + ماه بعد؛ ماه سوم هنوز نرسیده
        $this->assertCount(2, $invoices->pluck('number')->unique());
        $this->assertTrue(Carbon::parse($this->day($invoices[1]->issued_at))->gt(Carbon::parse($this->day($invoices[0]->issued_at))));

        $this->travelTo(now()->addDays(40)); // مجموعاً ۸۰ روز: ماه سوم رسیده
        $this->runCommand();
        $this->assertCount(3, $this->rentInvoices());
    }

    public function test_a_backdated_contract_does_not_create_back_invoices(): void
    {
        $this->rent(['signed_at' => JalaliDate::format(today()->subDays(10))])->assertSessionHasNoErrors();
        $this->runCommand();
        $this->assertCount(0, $this->rentInvoices()); // سررسید ۱۰ روز پیش مربوط به قبل از ثبت در سامانه است

        $this->travelTo(now()->addDays(40));
        $this->runCommand();
        $this->assertCount(1, $this->rentInvoices()); // سررسید بعدی که بعد از ثبت است
    }

    public function test_no_invoice_on_or_after_the_contract_end_date(): void
    {
        $this->rent(['end_date' => JalaliDate::format(today()->addDays(10))])->assertSessionHasNoErrors();
        $this->assertCount(1, $this->rentInvoices());

        $this->travelTo(now()->addDays(40));
        $this->runCommand();
        $this->assertCount(1, $this->rentInvoices());
    }

    public function test_ended_contracts_no_longer_get_invoices(): void
    {
        $this->rent();
        $contractId = (int) DB::table('contracts')->value('id');
        $this->as($this->manager)->post(route('contracts.end', $contractId))->assertSessionHasNoErrors();

        $this->travelTo(now()->addDays(40));
        $this->runCommand();

        $this->assertCount(1, $this->rentInvoices());
    }

    public function test_the_command_is_scheduled_daily(): void
    {
        \Illuminate\Support\Facades\Artisan::all(); // کنسول را بوت می‌کند تا routes/console.php (و زمان‌بندی‌ها) بارگذاری شود
        $events = collect($this->app->make(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->filter(fn ($e) => str_contains($e->command, 'rent:issue-invoices'));

        $this->assertCount(1, $events);
        $this->assertSame('0 6 * * *', $events->first()->expression);
    }
}
