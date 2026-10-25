<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\ChargeSetting;
use App\Models\ExpenseCategory;
use App\Models\PaymentReceipt;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Unit;
use App\Models\UnitParty;
use App\Models\User;
use App\Services\Notifier;
use App\Services\Sms\SmsGateway;
use App\Services\Ui\MenuBuilder;
use App\Support\CurrentBuilding;
use App\Support\JalaliDate;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** اعلان‌های درون‌سامانه‌ای (بند ۴.۱۸): ارسال از رویدادهای سامانه، تفکیک ساختمان، خوانده‌شدن و تاریخچه. */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $tenant;
    private User $buyer;
    private User $buyer2;
    private Building $building;
    private Building $other;
    private Subscription $sub;
    private Unit $unit;
    private Block $block;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);

        $this->app->instance(SmsGateway::class, new class implements SmsGateway {
            public function send(string $mobile, string $text, ?string $templateKey = null, array $templateVars = []): string
            {
                return 'fake';
            }
        });

        $this->manager = $this->user('09120000001', 'مدیر');
        $this->tenant = $this->user('09120000002', 'مستأجر');
        $this->buyer = $this->user('09120000004', 'خریدار');
        $this->buyer2 = $this->user('09120000005', 'خریداردوم');

        $plan = SubscriptionPlan::create(['name' => 'p', 'units_limit' => 20, 'duration_months' => 1, 'price' => 0]);
        $this->sub = Subscription::create(['user_id' => $this->manager->id, 'plan_id' => $plan->id, 'units_limit' => 20, 'starts_at' => now()->subDay(), 'expires_at' => now()->addYear(), 'status' => 'active']);
        $this->building = Building::create(['name' => 'برج آسمان', 'code' => '11111', 'created_by' => $this->manager->id, 'subscription_id' => $this->sub->id]);
        $this->other = Building::create(['name' => 'برج دیگر', 'code' => '22222', 'created_by' => $this->manager->id, 'subscription_id' => $this->sub->id]);

        $this->join($this->manager, 'owner');
        $this->join($this->tenant, 'tenant');
        $this->join($this->buyer, 'unit_owner');
        $this->join($this->buyer2, 'unit_owner');
        BuildingUserRole::create(['building_id' => $this->other->id, 'user_id' => $this->tenant->id, 'role_id' => Role::where('key', 'tenant')->value('id'), 'status' => 'active']);

        $this->db(function () {
            $this->block = Block::create(['number' => 1, 'name' => 'الف', 'floors_count' => 5]);
            $this->unit = Unit::create(['block_id' => $this->block->id, 'number' => '1', 'area_m2' => 90, 'status' => 'vacant', 'residents_count' => 0]);
        });
    }

    private function user(string $mobile, string $name): User
    {
        return User::create(['mobile' => $mobile, 'first_name' => $name, 'last_name' => 'تست', 'status' => 'active', 'profile_completed_at' => now()]);
    }

    private function join(User $u, string $role): void
    {
        BuildingUserRole::create(['building_id' => $this->building->id, 'user_id' => $u->id, 'role_id' => Role::where('key', $role)->value('id'), 'status' => 'active']);
    }

    private function db(callable $fn)
    {
        return app(CurrentBuilding::class)->actingAs($this->building->id, $fn);
    }

    private function as(User $u)
    {
        return $this->actingAs($u)->withSession(['current_building_id' => $this->building->id]);
    }

    private function notes(User $u, ?string $event = null, ?int $buildingId = null)
    {
        $q = DB::table('notifications')->where('notifiable_type', User::class)->where('notifiable_id', $u->id)->orderBy('created_at')->orderBy('id');
        $rows = $q->get()->map(function ($r) {
            $r->d = json_decode($r->data, true);

            return $r;
        })->filter(fn ($r) => (int) ($r->d['building_id'] ?? 0) === ($buildingId ?? $this->building->id));

        return $event ? $rows->filter(fn ($r) => ($r->d['event'] ?? null) === $event)->values() : $rows->values();
    }

    private function png(int $width): string
    {
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));

        return "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', $width, 1, 8, 2, 0, 0, 0)).$chunk('IDAT', gzcompress("\x00".str_repeat("\xff\x00\x00", $width))).$chunk('IEND', '');
    }

    private function rent()
    {
        return $this->as($this->manager)->post(route('contracts.store'), [
            'type' => 'rent', 'unit_id' => $this->unit->id, 'user_id' => $this->tenant->id,
            'deposit_amount' => '۱۰۰٬۰۰۰٬۰۰۰', 'monthly_rent' => '۵٬۰۰۰٬۰۰۰',
            'signed_at' => JalaliDate::format(today()), 'end_date' => JalaliDate::format(today()->addYear()), 'charge_payer' => 'tenant',
        ]);
    }

    private function sale(User $buyer)
    {
        return $this->as($this->manager)->post(route('contracts.store'), [
            'type' => 'sale', 'unit_id' => $this->unit->id, 'user_id' => $buyer->id,
            'sale_price' => '۹٬۰۰۰٬۰۰۰٬۰۰۰', 'signed_at' => JalaliDate::format(today()),
        ]);
    }

    // ───────── Notifier

    public function test_notifier_sends_to_users_ids_and_dedupes_by_key_per_building(): void
    {
        $n = app(Notifier::class);

        $this->assertSame(1, $n->send($this->tenant, $this->building->id, 'x', 'عنوان', 'متن', null, ['key' => 'k1']));
        $this->assertSame(0, $n->send($this->tenant->id, $this->building->id, 'x', 'عنوان', 'متن', null, ['key' => 'k1'])); // همان کلید
        $this->assertSame(1, $n->send($this->tenant, $this->other->id, 'x', 'عنوان', 'متن', null, ['key' => 'k1']));          // ساختمان دیگر، مستقل است
        $this->assertSame(2, $n->send([$this->tenant, $this->tenant->id, $this->manager], $this->building->id, 'y', 'عنوان', 'متن')); // تکراری‌ها یکی می‌شوند
        $this->assertSame(0, $n->send(null, $this->building->id, 'y', 'عنوان', 'متن'));

        $d = $this->notes($this->tenant, 'x')[0]->d;
        $this->assertSame($this->building->id, $d['building_id']);
        $this->assertSame('info', $d['level']);
        $this->assertSame('k1', $d['key']);
        $this->assertSame(1, $this->notes($this->tenant, 'x', $this->other->id)->count());
    }

    public function test_managers_are_active_owners_and_managers_only(): void
    {
        $ids = app(Notifier::class)->managers($this->building->id)->pluck('id')->all();

        $this->assertSame([$this->manager->id], $ids);
    }

    // ───────── رویدادها

    public function test_payment_review_notifies_the_submitter(): void
    {
        [$a, $b] = $this->db(fn () => [
            PaymentReceipt::create(['unit_id' => $this->unit->id, 'user_id' => $this->tenant->id, 'amount' => 5000, 'paid_at' => today(), 'status' => 'pending']),
            PaymentReceipt::create(['unit_id' => $this->unit->id, 'user_id' => $this->tenant->id, 'amount' => 7000, 'paid_at' => today(), 'status' => 'pending']),
        ]);

        $this->as($this->manager)->post(route('payments.approve', $a->id))->assertSessionHasNoErrors();
        $this->as($this->manager)->post(route('payments.reject', $b->id), ['reason' => 'تصویر ناخوانا است'])->assertSessionHasNoErrors();

        $ok = $this->notes($this->tenant, 'receipt_approved');
        $this->assertCount(1, $ok);
        $this->assertSame('success', $ok[0]->d['level']);
        $this->assertSame(route('my.payments'), $ok[0]->d['url']);

        $no = $this->notes($this->tenant, 'receipt_rejected');
        $this->assertCount(1, $no);
        $this->assertSame('warning', $no[0]->d['level']);
        $this->assertStringContainsString('تصویر ناخوانا است', $no[0]->d['message']);
    }

    public function test_submitting_a_receipt_notifies_the_managers_but_not_the_submitter(): void
    {
        $this->db(function () {
            $this->unit->update(['status' => 'rented']);
            UnitParty::create(['unit_id' => $this->unit->id, 'user_id' => $this->tenant->id, 'relation' => 'tenant', 'from_date' => today()]);
        });

        $this->as($this->tenant)->post(route('my.payments.store'), [
            'unit_id' => $this->unit->id, 'amount' => 5000, 'paid_at' => JalaliDate::format(today()),
            'receipt' => UploadedFile::fake()->createWithContent('r.png', $this->png(3)),
        ])->assertSessionHasNoErrors();

        $rows = $this->notes($this->manager, 'receipt_submitted');
        $this->assertCount(1, $rows);
        $this->assertSame(route('payments.index'), $rows[0]->d['url']);
        $this->assertCount(0, $this->notes($this->tenant, 'receipt_submitted'));
    }

    public function test_contract_events_notify_the_parties(): void
    {
        $this->rent()->assertSessionHasNoErrors();
        $this->assertCount(1, $this->notes($this->tenant, 'contract_registered'));
        $this->assertCount(1, $this->notes($this->tenant, 'rent_invoice_issued')); // صورت‌حساب اجاره‌ی اول همان لحظه صادر می‌شود

        $contractId = (int) DB::table('contracts')->value('id');
        $this->as($this->manager)->post(route('contracts.end', $contractId))->assertSessionHasNoErrors();
        $ended = $this->notes($this->tenant, 'contract_ended');
        $this->assertCount(1, $ended);
        $this->assertSame('warning', $ended[0]->d['level']);

        $this->sale($this->buyer)->assertSessionHasNoErrors();
        $this->assertCount(1, $this->notes($this->buyer, 'contract_registered'));

        $this->sale($this->buyer2)->assertSessionHasNoErrors();
        $this->assertCount(1, $this->notes($this->buyer2, 'contract_registered'));
        $this->assertCount(1, $this->notes($this->buyer, 'ownership_transferred')); // مالک قبلی
    }

    public function test_publishing_a_charge_notifies_each_payer_once(): void
    {
        $unit2 = $this->db(function () {
            ChargeSetting::create(['method' => 10]);
            ExpenseCategory::create(['name' => 'آب', 'kind' => 'variable', 'split_basis' => 'equal']);
            $this->unit->update(['status' => 'sold', 'residents_count' => 3]);
            UnitParty::create(['unit_id' => $this->unit->id, 'user_id' => $this->buyer->id, 'relation' => 'owner', 'from_date' => today()]);

            return Unit::create(['block_id' => $this->block->id, 'number' => '2', 'area_m2' => 100, 'status' => 'sold', 'residents_count' => 2]);
        });
        $this->db(fn () => UnitParty::create(['unit_id' => $unit2->id, 'user_id' => $this->buyer2->id, 'relation' => 'owner', 'from_date' => today()]));

        $this->as($this->manager)->post(route('expenses.store'), ['year' => 1405, 'month' => 7, 'category_id' => DB::table('expense_categories')->value('id'), 'title' => 'قبض آب', 'amount' => 100000])->assertSessionHasNoErrors();
        $periodId = (int) DB::table('charge_periods')->value('id');
        $this->as($this->manager)->post(route('charges.calculate', $periodId))->assertSessionHasNoErrors();
        $this->as($this->manager)->post(route('charges.publish', $periodId))->assertSessionHasNoErrors();

        foreach ([$this->buyer, $this->buyer2] as $payer) {
            $rows = $this->notes($payer, 'charge_published');
            $this->assertCount(1, $rows);
            $this->assertSame(route('my.invoices'), $rows[0]->d['url']);
            $this->assertSame('charge:'.$periodId, $rows[0]->d['key']);
        }
        $this->assertCount(0, $this->notes($this->tenant, 'charge_published'));
    }

    // ───────── صفحه‌ی اعلان‌ها

    public function test_inbox_lists_only_the_current_buildings_notifications(): void
    {
        $n = app(Notifier::class);
        $n->send($this->tenant, $this->building->id, 'a', 'اعلان ساختمان فعال', 'متن الف');
        $n->send($this->tenant, $this->other->id, 'b', 'اعلان ساختمان دیگر', 'متن ب');

        $this->as($this->tenant)->get(route('notifications.index'))->assertOk()
            ->assertSee('اعلان ساختمان فعال')->assertDontSee('اعلان ساختمان دیگر');
    }

    public function test_unread_filter_and_reading_a_notification(): void
    {
        $n = app(Notifier::class);
        $n->send($this->tenant, $this->building->id, 'a', 'اعلان خوانده', 'متن', route('my.invoices'));
        $n->send($this->tenant, $this->building->id, 'b', 'اعلان جدید یک', 'متن', route('my.contracts'));
        $firstId = $this->notes($this->tenant, 'a')[0]->id;

        $this->as($this->tenant)->post(route('notifications.read', $firstId))->assertRedirect(route('my.invoices'));
        $this->assertNotNull(DB::table('notifications')->where('id', $firstId)->value('read_at'));

        $this->as($this->tenant)->get(route('notifications.index', ['filter' => 'unread']))->assertOk()
            ->assertSee('اعلان جدید یک')->assertDontSee('اعلان خوانده');
        $this->as($this->tenant)->get(route('notifications.index'))->assertOk()->assertSee('اعلان خوانده')->assertSee('اعلان جدید یک');
    }

    public function test_a_notification_without_url_can_be_marked_read_and_stays_on_the_inbox(): void
    {
        app(Notifier::class)->send($this->tenant, $this->building->id, 'a', 'بدون لینک', 'متن');
        $id = $this->notes($this->tenant)[0]->id;

        $this->as($this->tenant)->post(route('notifications.read', $id))->assertRedirect(route('notifications.index'));
        $this->assertNotNull(DB::table('notifications')->where('id', $id)->value('read_at'));
    }

    public function test_users_cannot_read_other_peoples_or_other_buildings_notifications(): void
    {
        $n = app(Notifier::class);
        $n->send($this->manager, $this->building->id, 'a', 'برای مدیر', 'متن', route('payments.index'));
        $n->send($this->tenant, $this->other->id, 'b', 'ساختمان دیگر', 'متن', route('my.invoices'));

        $managers = $this->notes($this->manager)[0]->id;
        $elsewhere = $this->notes($this->tenant, null, $this->other->id)[0]->id;

        $this->as($this->tenant)->post(route('notifications.read', $managers))->assertNotFound();
        $this->as($this->tenant)->post(route('notifications.read', $elsewhere))->assertNotFound();
        $this->assertNull(DB::table('notifications')->where('id', $managers)->value('read_at'));
        $this->assertNull(DB::table('notifications')->where('id', $elsewhere)->value('read_at'));
    }

    public function test_read_all_marks_only_my_notifications_in_this_building(): void
    {
        $n = app(Notifier::class);
        $n->send($this->tenant, $this->building->id, 'a', 'الف', 'متن');
        $n->send($this->tenant, $this->building->id, 'b', 'ب', 'متن');
        $n->send($this->tenant, $this->other->id, 'c', 'ج', 'متن');
        $n->send($this->manager, $this->building->id, 'd', 'د', 'متن');

        $this->as($this->tenant)->post(route('notifications.read-all'))->assertRedirect(route('notifications.index'));

        $this->assertTrue($this->notes($this->tenant)->every(fn ($r) => $r->read_at !== null));
        $this->assertTrue($this->notes($this->tenant, null, $this->other->id)->every(fn ($r) => $r->read_at === null));
        $this->assertTrue($this->notes($this->manager)->every(fn ($r) => $r->read_at === null));
    }

    public function test_menu_badge_counts_unread_notifications_of_the_current_building(): void
    {
        $n = app(Notifier::class);
        $n->send($this->tenant, $this->building->id, 'a', 'الف', 'متن');
        $n->send($this->tenant, $this->building->id, 'b', 'ب', 'متن');
        $n->send($this->tenant, $this->other->id, 'c', 'ج', 'متن'); // ساختمان دیگر شمرده نمی‌شود
        DB::table('notifications')->where('id', $this->notes($this->tenant, 'b')[0]->id)->update(['read_at' => now()]);

        $current = app(CurrentBuilding::class);
        $current->set($this->building->id, ['tenant']);
        try {
            $items = collect(app(MenuBuilder::class)->build($this->tenant))->flatMap(fn ($g) => $g['items']);
            $this->assertSame(1, $items->firstWhere('label', 'اعلان‌ها')['badge']);
        } finally {
            $current->clear();
        }
    }
}
