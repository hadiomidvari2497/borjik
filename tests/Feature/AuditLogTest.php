<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Contract;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Unit;
use App\Models\User;
use App\Services\Sms\SmsGateway;
use App\Support\AuditPresenter;
use App\Support\CurrentBuilding;
use App\Support\Fa;
use App\Support\JalaliDate;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** تاریخچه‌ی تغییرات (بند ۲.۳): چه کسی، چه زمانی، چه تغییری؛ نمایش فقط برای مالک/مدیرِ همان ساختمان. */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $tenant;
    private Building $building;
    private Subscription $sub;
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
        $this->sub = Subscription::create(['user_id' => $this->manager->id, 'plan_id' => $plan->id, 'units_limit' => 20, 'starts_at' => now()->subDay(), 'expires_at' => now()->addYear(), 'status' => 'active']);
        $this->building = Building::create(['name' => 'برج آسمان', 'code' => '11111', 'created_by' => $this->manager->id, 'subscription_id' => $this->sub->id]);

        foreach ([[$this->manager, 'owner'], [$this->tenant, 'tenant']] as [$u, $role]) {
            BuildingUserRole::create(['building_id' => $this->building->id, 'user_id' => $u->id, 'role_id' => Role::where('key', $role)->value('id'), 'status' => 'active']);
        }

        $this->inBuilding($this->building->id, function () {
            $block = Block::create(['number' => 1, 'name' => 'الف', 'floors_count' => 5]);
            $this->unit = Unit::create(['block_id' => $block->id, 'number' => '1', 'area_m2' => 90, 'status' => 'vacant', 'residents_count' => 0]);
        });
    }

    private function inBuilding(int $id, callable $fn)
    {
        return app(CurrentBuilding::class)->actingAs($id, $fn);
    }

    private function as(User $u)
    {
        return $this->actingAs($u)->withSession(['current_building_id' => $this->building->id]);
    }

    private function logs(string $type, int $id, ?string $event = null)
    {
        $q = DB::table('audit_logs')->where('auditable_type', $type)->where('auditable_id', $id)->orderBy('id');

        return $event ? $q->where('event', $event)->get() : $q->get();
    }

    private function rent()
    {
        return $this->as($this->manager)->post(route('contracts.store'), [
            'type' => 'rent', 'unit_id' => $this->unit->id, 'user_id' => $this->tenant->id,
            'deposit_amount' => '۱۰۰٬۰۰۰٬۰۰۰', 'monthly_rent' => '۵٬۰۰۰٬۰۰۰',
            'signed_at' => JalaliDate::format(today()), 'end_date' => JalaliDate::format(today()->addYear()), 'charge_payer' => 'tenant',
        ]);
    }

    public function test_creating_a_record_is_logged_with_building_and_values(): void
    {
        $rows = $this->logs(Unit::class, $this->unit->id, 'created');

        $this->assertCount(1, $rows);
        $this->assertSame($this->building->id, (int) $rows[0]->building_id);
        $new = json_decode($rows[0]->new_values, true);
        $this->assertSame('1', (string) $new['number']);
        $this->assertArrayNotHasKey('created_at', $new);
        $this->assertArrayNotHasKey('updated_at', $new);
        $this->assertNull($rows[0]->old_values);
    }

    public function test_updates_record_only_the_changed_columns_with_old_and_new_and_the_actor(): void
    {
        $this->actingAs($this->manager);
        $this->inBuilding($this->building->id, fn () => Unit::find($this->unit->id)->update(['residents_count' => 3, 'notes' => 'تست']));

        $rows = $this->logs(Unit::class, $this->unit->id, 'updated');
        $this->assertCount(1, $rows);
        $this->assertSame($this->manager->id, (int) $rows[0]->user_id);

        $old = json_decode($rows[0]->old_values, true);
        $new = json_decode($rows[0]->new_values, true);
        $this->assertEqualsCanonicalizing(['residents_count', 'notes'], array_keys($new));
        $this->assertSame(0, (int) $old['residents_count']);
        $this->assertSame(3, (int) $new['residents_count']);
        $this->assertSame('تست', $new['notes']);
    }

    public function test_noop_and_secret_only_updates_are_not_logged(): void
    {
        $this->inBuilding($this->building->id, function () {
            $unit = Unit::find($this->unit->id);
            $unit->update(['residents_count' => 0]); // بدون تغییر
        });
        $this->assertCount(0, $this->logs(Unit::class, $this->unit->id, 'updated'));

        $before = DB::table('audit_logs')->where('auditable_type', User::class)->where('auditable_id', $this->tenant->id)->count();
        $this->tenant->update(['password' => 'secret-123', 'last_login_at' => now()]);
        $this->assertSame($before, DB::table('audit_logs')->where('auditable_type', User::class)->where('auditable_id', $this->tenant->id)->count());

        $this->tenant->update(['last_name' => 'عوض‌شده']);
        $rows = $this->logs(User::class, $this->tenant->id, 'updated');
        $this->assertCount(1, $rows);
        $this->assertArrayNotHasKey('password', json_decode($rows[0]->new_values, true));

        $created = json_decode($this->logs(User::class, $this->tenant->id, 'created')[0]->new_values, true);
        $this->assertArrayNotHasKey('password', $created);
    }

    public function test_deleting_logs_the_old_values(): void
    {
        $blockId = $this->inBuilding($this->building->id, function () {
            $b = Block::create(['number' => 2, 'name' => 'ب', 'floors_count' => 3]);
            $b->delete();

            return $b->id;
        });

        $rows = $this->logs(Block::class, $blockId, 'deleted');
        $this->assertCount(1, $rows);
        $this->assertSame('ب', json_decode($rows[0]->old_values, true)['name']);
        $this->assertNull($rows[0]->new_values);
    }

    public function test_soft_delete_and_restore_are_logged_without_a_duplicate_update(): void
    {
        $this->rent();
        $id = (int) DB::table('contracts')->value('id');

        $this->inBuilding($this->building->id, function () use ($id) {
            $c = Contract::find($id);
            $c->delete();
            Contract::withTrashed()->find($id)->restore();
        });

        $this->assertCount(1, $this->logs(Contract::class, $id, 'deleted'));
        $this->assertCount(1, $this->logs(Contract::class, $id, 'restored'));
        $this->assertCount(0, $this->logs(Contract::class, $id, 'updated'));
    }

    public function test_a_contract_registration_leaves_a_full_trail(): void
    {
        $this->rent()->assertSessionHasNoErrors();
        $id = (int) DB::table('contracts')->value('id');

        $created = $this->logs(Contract::class, $id, 'created');
        $this->assertCount(1, $created);
        $this->assertSame($this->manager->id, (int) $created[0]->user_id);

        $unit = $this->logs(Unit::class, $this->unit->id, 'updated');
        $this->assertCount(1, $unit);
        $this->assertSame('vacant', json_decode($unit[0]->old_values, true)['status']);
        $this->assertSame('rented', json_decode($unit[0]->new_values, true)['status']);
        $this->assertSame($this->manager->id, (int) $unit[0]->user_id);

        $this->assertSame(1, DB::table('audit_logs')->where('auditable_type', \App\Models\UnitParty::class)->where('event', 'created')->count());
        $this->assertSame(1, DB::table('audit_logs')->where('auditable_type', \App\Models\Invoice::class)->where('event', 'created')->count()); // صورت‌حساب اجاره‌ی اول
    }

    public function test_ending_a_rent_logs_the_party_change_made_through_the_model(): void
    {
        $this->rent();
        $id = (int) DB::table('contracts')->value('id');
        $this->as($this->manager)->post(route('contracts.end', $id))->assertSessionHasNoErrors();

        $updates = DB::table('audit_logs')->where('auditable_type', \App\Models\UnitParty::class)->where('event', 'updated')->get();
        $this->assertCount(1, $updates);
        $this->assertArrayHasKey('to_date', json_decode($updates[0]->new_values, true));
        $this->assertSame($this->manager->id, (int) $updates[0]->user_id);
    }

    public function test_audit_page_is_for_managers_of_the_building_only(): void
    {
        $this->rent();

        $this->as($this->tenant)->get(route('audit.index'))->assertForbidden();

        $this->as($this->manager)->get(route('audit.index'))->assertOk()->assertSee('تاریخچه‌ی تغییرات')->assertSee('قرارداد');
        $this->as($this->manager)->get(route('audit.index', ['model' => Contract::class, 'event' => 'created']))->assertOk()->assertSee('قرارداد')->assertDontSee('>ویرایش</span>', false);
        $this->as($this->manager)->get(route('audit.index', ['id' => 999999]))->assertOk()->assertSee('موردی پیدا نشد');
    }

    public function test_other_buildings_logs_are_never_shown(): void
    {
        $other = Building::create(['name' => 'ZZBUILDING', 'code' => '22222', 'created_by' => $this->manager->id, 'subscription_id' => $this->sub->id]);
        $this->inBuilding($other->id, function () {
            $block = Block::create(['number' => 1, 'name' => 'ZZBLOCK', 'floors_count' => 2]);
            Unit::create(['block_id' => $block->id, 'number' => '777', 'area_m2' => 50, 'status' => 'vacant', 'residents_count' => 0]);
        });

        $this->assertGreaterThan(0, DB::table('audit_logs')->where('building_id', $other->id)->count());

        $this->as($this->manager)->get(route('audit.index'))->assertOk()->assertDontSee('۷۷۷')->assertDontSee('ZZBLOCK')->assertDontSee('ZZBUILDING');
    }

    public function test_contract_page_shows_its_history_to_managers(): void
    {
        $this->rent();
        $id = (int) DB::table('contracts')->value('id');

        $this->as($this->manager)->get(route('contracts.show', $id))->assertOk()->assertSee('تاریخچه‌ی تغییرات');
    }

    public function test_presenter_formats_values_in_persian(): void
    {
        $this->assertSame('—', AuditPresenter::value('notes', null));
        $this->assertSame(Fa::money(5000000), AuditPresenter::value('monthly_rent', '5000000'));
        $this->assertSame('بله', AuditPresenter::value('late_fee_enabled', 1));
        $this->assertSame('خیر', AuditPresenter::value('late_fee_enabled', '0'));
        $this->assertSame(Fa::digits('#12'), AuditPresenter::value('unit_id', 12));
        $this->assertSame('اجاره‌داده‌شده', AuditPresenter::value('status', 'rented'));
        $this->assertSame(Fa::date(Carbon::parse('2026-10-17'), 'Y/m/d'), AuditPresenter::value('due_date', '2026-10-17 00:00:00'));
        $this->assertSame('اجاره‌ی ماهانه', AuditPresenter::field('monthly_rent'));
        $this->assertSame('some_unknown_column', AuditPresenter::field('some_unknown_column'));
    }
}
