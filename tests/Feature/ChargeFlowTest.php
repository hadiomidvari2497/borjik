<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\ChargeSetting;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Role;
use App\Models\SmsLog;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Unit;
use App\Models\User;
use App\Services\Sms\SmsGateway;
use App\Support\CurrentBuilding;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** هزینه‌ها، تنظیمات شارژ، محاسبه و انتشار، و یادآوری پیامکی. */
class ChargeFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Building $building;
    private Block $block;
    private ExpenseCategory $water;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->owner = User::create(['mobile' => '09120000001', 'first_name' => 'م', 'last_name' => 'ت', 'status' => 'active', 'profile_completed_at' => now()]);
        $plan = SubscriptionPlan::create(['name' => 'p', 'units_limit' => 20, 'duration_months' => 1, 'price' => 0]);
        $sub = Subscription::create(['user_id' => $this->owner->id, 'plan_id' => $plan->id, 'units_limit' => 20, 'starts_at' => now()->subDay(), 'expires_at' => now()->addMonth(), 'status' => 'active']);
        $this->building = Building::create(['name' => 'برج آسمان', 'code' => '11111', 'created_by' => $this->owner->id, 'subscription_id' => $sub->id]);
        $this->join($this->owner, 'owner');

        $this->db(function () {
            ChargeSetting::create(['method' => 10]);
            $this->block = Block::create(['number' => 1, 'name' => 'الف', 'floors_count' => 5]);
            $this->water = ExpenseCategory::create(['name' => 'آب', 'kind' => 'variable', 'split_basis' => 'equal']);
        });
    }

    private function join(User $u, string $role): void
    {
        BuildingUserRole::create(['building_id' => $this->building->id, 'user_id' => $u->id, 'role_id' => Role::where('key', $role)->value('id'), 'status' => 'active']);
    }

    private function db(callable $fn)
    {
        return app(CurrentBuilding::class)->actingAs($this->building->id, $fn);
    }

    private function as(?User $u = null)
    {
        return $this->actingAs($u ?? $this->owner)->withSession(['current_building_id' => $this->building->id]);
    }

    private function unit(string $number, string $status = 'vacant', int $residents = 0): Unit
    {
        return $this->db(fn () => Unit::create(['block_id' => $this->block->id, 'number' => $number, 'area_m2' => 100, 'status' => $status, 'residents_count' => $residents]));
    }

    private function addExpense(int $amount = 100000, int $year = 1405, int $month = 7)
    {
        return $this->as()->post(route('expenses.store'), ['year' => $year, 'month' => $month, 'category_id' => $this->water->id, 'title' => 'قبض آب', 'amount' => $amount]);
    }

    private function settingsPayload(array $over = []): array
    {
        return $over + ['method' => 7, 'mode' => 'ratio', 'fixed_amount' => '۵۰٬۰۰۰', 'area_rate' => 0, 'person_rate' => 0, 'person_ratio_pct' => '۲۰',
            'calc_day' => 25, 'warn_days_before' => 5, 'due_days' => 10];
    }

    public function test_settings_are_saved_with_persian_digits_and_area_ratio_is_derived(): void
    {
        $this->as()->put(route('charge-settings.update'), $this->settingsPayload())->assertSessionHasNoErrors();

        $row = DB::table('charge_settings')->first();
        $this->assertSame(7, (int) $row->method);
        $this->assertSame(50000, (int) $row->fixed_amount);
        $this->assertSame(20, (int) $row->person_ratio_pct);
        $this->assertSame(80, (int) $row->area_ratio_pct);
    }

    public function test_invalid_settings_are_rejected(): void
    {
        $this->as()->put(route('charge-settings.update'), $this->settingsPayload(['person_ratio_pct' => 120]))->assertSessionHasErrors('person_ratio_pct');
        $this->as()->put(route('charge-settings.update'), $this->settingsPayload(['calc_day' => 31]))->assertSessionHasErrors('calc_day');
        $this->as()->put(route('charge-settings.update'), $this->settingsPayload(['late_fee_enabled' => 1]))->assertSessionHasErrors('late_fee_type');
    }

    public function test_fixed_rules_and_categories_in_use_are_protected(): void
    {
        $this->as()->post(route('charge-settings.rules.store'), ['name' => 'دوخوابه بدون پارکینگ', 'bedrooms' => '۲', 'has_parking' => 'no', 'has_storage' => 'any', 'amount' => '۶۰۰۰۰'])
            ->assertSessionHasNoErrors();
        $rule = DB::table('charge_fixed_rules')->first();
        $this->assertSame(2, (int) $rule->bedrooms);
        $this->assertSame(0, (int) $rule->has_parking);
        $this->assertNull($rule->has_storage);
        $this->as()->delete(route('charge-settings.rules.destroy', $rule->id));
        $this->assertSame(0, DB::table('charge_fixed_rules')->count());

        $this->addExpense();
        $this->as()->delete(route('charge-settings.categories.destroy', $this->water->id))->assertSessionHasErrors('general');
        $this->assertDatabaseHas('expense_categories', ['id' => $this->water->id]);
    }

    public function test_adding_an_expense_creates_the_period_and_accepts_persian_amounts(): void
    {
        $this->addExpense(100000)->assertRedirect();
        $this->as()->post(route('expenses.store'), ['year' => '۱۴۰۵', 'month' => '۷', 'title' => 'نظافت', 'amount' => '۲٬۵۰۰٬۰۰۰'])->assertSessionHasNoErrors();

        $this->assertSame(1, DB::table('charge_periods')->count());
        $this->assertSame('draft', DB::table('charge_periods')->value('status'));
        $this->assertSame(2600000, (int) DB::table('expenses')->sum('amount'));

        $this->as()->get(route('expenses.index', ['year' => 1405, 'month' => 7]))->assertOk()->assertSee('قبض آب');
        $this->as()->delete(route('expenses.destroy', DB::table('expenses')->value('id')));
        $this->assertSame(1, DB::table('expenses')->whereNull('deleted_at')->count());
    }

    public function test_calculate_then_publish_issues_invoices_and_locks_the_period(): void
    {
        $this->unit('1', 'sold', 3);
        $this->unit('2');
        $this->addExpense(100000);
        $periodId = (int) DB::table('charge_periods')->value('id');

        $this->as()->post(route('charges.calculate', $periodId))->assertRedirect(route('charges.show', $periodId));
        $period = DB::table('charge_periods')->first();
        $this->assertSame('calculated', $period->status);
        $this->assertSame(100000, (int) $period->total_expenses);
        $this->as()->get(route('charges.show', $periodId))->assertOk()->assertSee('جمع شارژها');

        $this->as()->post(route('charges.publish', $periodId))->assertSessionHasNoErrors();
        $this->assertSame('published', DB::table('charge_periods')->value('status'));
        $this->assertSame([50000, 50000], DB::table('invoices')->orderBy('id')->pluck('amount')->map(fn ($v) => (int) $v)->all());
        $this->assertSame('CH-140507-'.DB::table('units')->orderBy('id')->value('id'), DB::table('invoices')->orderBy('id')->value('number'));

        $this->addExpense(5000)->assertSessionHasErrors('general');           // قفل پس از انتشار
        $this->as()->post(route('charges.publish', $periodId))->assertSessionHasErrors('general'); // انتشار دوباره
        $this->assertSame(2, DB::table('invoices')->count());
    }

    public function test_changing_expenses_invalidates_a_calculated_period(): void
    {
        $this->unit('1');
        $this->addExpense(100000);
        $periodId = (int) DB::table('charge_periods')->value('id');
        $this->as()->post(route('charges.calculate', $periodId));
        $this->assertSame('calculated', DB::table('charge_periods')->value('status'));

        $this->addExpense(20000);
        $this->assertSame('draft', DB::table('charge_periods')->value('status'));
        $this->as()->post(route('charges.publish', $periodId))->assertSessionHasErrors('general'); // باید دوباره محاسبه شود
    }

    public function test_units_without_residents_must_be_confirmed_before_publishing(): void
    {
        $this->db(fn () => ChargeSetting::first()->update(['method' => 3, 'person_rate' => 20000]));
        $this->unit('1', 'sold', 0);   // ساکن ثبت نشده → محاسبه نمی‌شود
        $this->unit('2', 'sold', 2);   // ۴۰٬۰۰۰
        $this->as()->post(route('charges.open'), ['year' => 1405, 'month' => 7]);
        $periodId = (int) DB::table('charge_periods')->value('id');

        $this->as()->post(route('charges.calculate', $periodId));
        $this->as()->post(route('charges.publish', $periodId))->assertSessionHasErrors('general');
        $this->assertSame(0, DB::table('invoices')->count());

        $this->as()->post(route('charges.publish', $periodId), ['confirm_skipped' => 1])->assertSessionHasNoErrors();
        $this->assertSame([40000], DB::table('invoices')->pluck('amount')->map(fn ($v) => (int) $v)->all());
    }

    public function test_tenants_cannot_reach_the_charge_pages(): void
    {
        $tenant = User::create(['mobile' => '09120000009', 'first_name' => 'ا', 'last_name' => 'ب', 'status' => 'active', 'profile_completed_at' => now()]);
        $this->join($tenant, 'tenant');

        $this->as($tenant)->get(route('charges.index'))->assertForbidden();
        $this->as($tenant)->get(route('expenses.index'))->assertForbidden();
        $this->as($tenant)->get(route('charge-settings.edit'))->assertForbidden();
    }

    public function test_reminder_is_sent_once_inside_the_warning_window_and_only_without_expenses(): void
    {
        $gateway = new class implements SmsGateway {
            public array $sent = [];

            public function send(string $mobile, string $text): string
            {
                $this->sent[] = [$mobile, $text];

                return 'fake';
            }
        };
        $this->app->instance(SmsGateway::class, $gateway);

        // ۱۸ مهر ۱۴۰۵: هنوز قبل از پنجره‌ی یادآوری (۲۰ تا ۲۵ مهر)
        $this->travelTo(now()->setDate(2026, 10, 10)->setTime(9, 0));
        $this->artisan('charges:remind-expenses')->assertSuccessful();
        $this->assertSame(0, SmsLog::count());

        // ۲۲ مهر ۱۴۰۵: داخل پنجره
        $this->travelTo(now()->setDate(2026, 10, 14)->setTime(9, 0));
        $this->artisan('charges:remind-expenses')->assertSuccessful();
        $this->assertSame(1, SmsLog::where('template_key', 'expense_reminder')->count());
        $this->assertSame('09120000001', $gateway->sent[0][0]);
        $this->assertStringContainsString('برج آسمان', $gateway->sent[0][1]);

        $this->artisan('charges:remind-expenses')->assertSuccessful();
        $this->assertSame(1, SmsLog::where('template_key', 'expense_reminder')->count()); // تکراری نمی‌شود

        $this->travelBack();
    }

    public function test_reminder_is_skipped_when_expenses_exist(): void
    {
        $this->app->instance(SmsGateway::class, new class implements SmsGateway {
            public function send(string $mobile, string $text): string
            {
                return 'fake';
            }
        });

        $this->travelTo(now()->setDate(2026, 10, 14)->setTime(9, 0));
        $this->addExpense(100000, 1405, 7);
        $this->artisan('charges:remind-expenses')->assertSuccessful();

        $this->assertSame(0, SmsLog::count());
        $this->travelBack();
    }
}
