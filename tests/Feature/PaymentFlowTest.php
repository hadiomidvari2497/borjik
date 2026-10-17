<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Unit;
use App\Models\UnitParty;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\Sms\SmsGateway;
use App\Services\UnitFinance;
use App\Support\CurrentBuilding;
use App\Support\JalaliDate;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** ثبت و تأیید فیش، تخصیص به قدیمی‌ترین صورت‌حساب، مازاد و دسترسی به تصویر فیش. */
class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $tenant;
    private Building $building;
    private Unit $unit;
    private object $gateway;
    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);

        $this->gateway = new class implements SmsGateway {
            public array $sent = [];

            public function send(string $mobile, string $text): string
            {
                $this->sent[] = [$mobile, $text];

                return 'fake';
            }
        };
        $this->app->instance(SmsGateway::class, $this->gateway);

        $this->manager = $this->user('09120000001', 'مدیر');
        $this->tenant = $this->user('09120000002', 'مستأجر');
        $plan = SubscriptionPlan::create(['name' => 'p', 'units_limit' => 20, 'duration_months' => 1, 'price' => 0]);
        $sub = Subscription::create(['user_id' => $this->manager->id, 'plan_id' => $plan->id, 'units_limit' => 20, 'starts_at' => now()->subDay(), 'expires_at' => now()->addMonth(), 'status' => 'active']);
        $this->building = Building::create(['name' => 'برج آسمان', 'code' => '11111', 'created_by' => $this->manager->id, 'subscription_id' => $sub->id]);
        $this->join($this->manager, 'owner');
        $this->join($this->tenant, 'tenant');

        $this->db(function () {
            $block = Block::create(['number' => 1, 'name' => 'الف', 'floors_count' => 5]);
            $this->unit = Unit::create(['block_id' => $block->id, 'number' => '1', 'area_m2' => 90, 'status' => 'rented', 'residents_count' => 2]);
            UnitParty::create(['unit_id' => $this->unit->id, 'user_id' => $this->tenant->id, 'relation' => 'tenant', 'from_date' => today()]);
            $this->invoice('T-1', 100000, today()->subDays(20));   // قدیمی‌تر
            $this->invoice('T-2', 50000, today()->addDays(10));
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

    private function invoice(string $number, int $amount, $due): Invoice
    {
        return Invoice::create([
            'unit_id' => $this->unit->id, 'payer_user_id' => $this->tenant->id, 'type' => 'charge', 'number' => $number,
            'amount' => $amount, 'paid_amount' => 0, 'issued_at' => today()->subDays(30), 'due_date' => $due, 'status' => 'unpaid',
        ]);
    }

    private function as(User $u)
    {
        return $this->actingAs($u)->withSession(['current_building_id' => $this->building->id]);
    }

    /** یک PNG معتبر و کوچک بدون افزونه‌ی GD؛ با عرض متفاوت، محتوا (و هش) متفاوت می‌شود. */
    private function png(int $width): string
    {
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));

        return "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', $width, 1, 8, 2, 0, 0, 0))
            .$chunk('IDAT', gzcompress("\x00".str_repeat("\xff\x00\x00", $width)))
            .$chunk('IEND', '');
    }

    private function submit(int $amount, ?UploadedFile $file = null, array $over = [])
    {
        $file ??= UploadedFile::fake()->createWithContent('r'.(++$this->seq).'.png', $this->png($this->seq));

        return $this->as($this->tenant)->post(route('my.payments.store'), $over + [
            'unit_id' => $this->unit->id, 'amount' => $amount, 'paid_at' => JalaliDate::format(today()), 'receipt' => $file,
        ]);
    }

    private function receiptId(int $n = 1): int
    {
        return (int) DB::table('payment_receipts')->orderBy('id')->skip($n - 1)->value('id');
    }

    private function balance(): int
    {
        return $this->db(fn () => app(UnitFinance::class)->balance($this->unit->id));
    }

    public function test_submitting_creates_a_pending_receipt_and_stores_the_file_privately(): void
    {
        $this->submit(120000, over: ['amount' => '۱۲۰٬۰۰۰'])->assertSessionHasNoErrors()->assertRedirect(route('my.payments'));

        $this->assertDatabaseHas('payment_receipts', ['unit_id' => $this->unit->id, 'amount' => 120000, 'status' => 'pending']);
        $file = DB::table('files')->first();
        Storage::disk('local')->assertExists($file->path);
        $this->assertSame('private', $file->visibility);
        $this->assertSame(1, DB::table('file_links')->where('attachable_type', 'payment_receipt')->count());
        $this->assertSame(-150000, $this->balance()); // فیش در انتظار حساب نمی‌شود
    }

    public function test_approval_pays_the_oldest_invoice_first_and_allows_partial_payment(): void
    {
        $this->submit(120000);
        $this->as($this->manager)->post(route('payments.approve', $this->receiptId()))->assertSessionHasNoErrors();

        $a = DB::table('invoices')->where('number', 'T-1')->first();
        $b = DB::table('invoices')->where('number', 'T-2')->first();
        $this->assertSame([100000, 'paid'], [(int) $a->paid_amount, $a->status]);
        $this->assertSame([20000, 'partial'], [(int) $b->paid_amount, $b->status]);
        $this->assertSame([100000, 20000], DB::table('payment_allocations')->orderBy('id')->pluck('amount')->map(fn ($v) => (int) $v)->all());
        $this->assertSame($this->manager->id, (int) DB::table('payment_receipts')->value('reviewed_by'));
        $this->assertSame(-30000, $this->balance());
        $this->assertStringContainsString('تأیید', end($this->gateway->sent)[1]);

        $this->submit(30000);
        $this->as($this->manager)->post(route('payments.approve', $this->receiptId(2)));
        $this->assertSame('paid', DB::table('invoices')->where('number', 'T-2')->value('status'));
        $this->assertSame(0, $this->balance());
    }

    public function test_surplus_becomes_credit_and_is_used_by_the_next_invoice(): void
    {
        $this->submit(200000);
        $this->as($this->manager)->post(route('payments.approve', $this->receiptId()));
        $this->assertSame(50000, $this->balance());

        $c = $this->db(fn () => $this->invoice('T-3', 80000, today()->addDays(30)));
        $this->db(fn () => app(PaymentService::class)->reallocate($this->unit->id));

        $c = DB::table('invoices')->where('id', $c->id)->first();
        $this->assertSame([50000, 'partial'], [(int) $c->paid_amount, $c->status]);
        $this->assertSame(-30000, $this->balance());
        $this->assertSame(200000, (int) DB::table('payment_allocations')->sum('amount')); // هیچ مبلغی دو بار مصرف نمی‌شود
    }

    public function test_rejecting_needs_a_reason_and_allocates_nothing(): void
    {
        $this->submit(100000);
        $id = $this->receiptId();

        $this->as($this->manager)->post(route('payments.reject', $id))->assertSessionHasErrors('reason');
        $this->as($this->manager)->post(route('payments.reject', $id), ['reason' => 'تصویر ناخوانا است'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payment_receipts', ['id' => $id, 'status' => 'rejected', 'reject_reason' => 'تصویر ناخوانا است']);
        $this->assertSame(0, DB::table('payment_allocations')->count());
        $this->assertSame(-150000, $this->balance());
        $this->assertStringContainsString('تصویر ناخوانا است', end($this->gateway->sent)[1]);

        $this->as($this->manager)->post(route('payments.approve', $id))->assertSessionHasErrors('general'); // بعد از رد قابل تأیید نیست
    }

    public function test_a_receipt_cannot_be_approved_twice(): void
    {
        $this->submit(100000);
        $id = $this->receiptId();

        $this->as($this->manager)->post(route('payments.approve', $id))->assertSessionHasNoErrors();
        $this->as($this->manager)->post(route('payments.approve', $id))->assertSessionHasErrors('general');

        $this->assertSame(100000, (int) DB::table('payment_allocations')->sum('amount'));
    }

    public function test_submission_validation(): void
    {
        $this->submit(0)->assertSessionHasErrors('amount');
        $this->submit(1000, over: ['paid_at' => JalaliDate::format(today()->addDays(3))])->assertSessionHasErrors('paid_at');
        $this->submit(1000, over: ['paid_at' => '1405/13/40'])->assertSessionHasErrors('paid_at');
        $this->as($this->tenant)->post(route('my.payments.store'), ['unit_id' => $this->unit->id, 'amount' => 1000, 'paid_at' => JalaliDate::format(today())])->assertSessionHasErrors('receipt');
        $this->submit(1000, UploadedFile::fake()->create('virus.exe', 10))->assertSessionHasErrors('receipt');

        $other = $this->db(fn () => Unit::create(['block_id' => $this->unit->block_id, 'number' => '2', 'area_m2' => 70]));
        $this->submit(1000, over: ['unit_id' => $other->id])->assertSessionHasErrors('unit_id'); // واحد دیگران

        $this->submit(1000, UploadedFile::fake()->create('r.pdf', 100, 'application/pdf'))->assertSessionHasNoErrors(); // pdf مجاز است
        $this->assertSame(1, DB::table('payment_receipts')->count());
    }

    public function test_the_same_image_cannot_be_used_twice(): void
    {
        $bytes = $this->png(33);

        $this->submit(1000, UploadedFile::fake()->createWithContent('a.png', $bytes))->assertSessionHasNoErrors();
        $this->submit(1000, UploadedFile::fake()->createWithContent('b.png', $bytes))->assertSessionHasErrors('general');

        $this->assertSame(1, DB::table('payment_receipts')->count());
    }

    public function test_a_rejected_receipt_image_can_be_resubmitted(): void
    {
        $bytes = $this->png(34);

        $this->submit(1000, UploadedFile::fake()->createWithContent('a.png', $bytes))->assertSessionHasNoErrors();
        $this->as($this->manager)->post(route('payments.reject', $this->receiptId()), ['reason' => 'تصویر ناخوانا است'])
            ->assertSessionHasNoErrors();

        $this->submit(1000, UploadedFile::fake()->createWithContent('b.png', $bytes))->assertSessionHasNoErrors();

        $this->assertSame(2, DB::table('payment_receipts')->count());
        $this->assertSame(1, DB::table('payment_receipts')->where('status', 'pending')->count());
        $this->assertSame(1, DB::table('payment_receipts')->where('status', 'rejected')->count());
    }

    public function test_receipt_images_are_only_visible_to_the_uploader_and_reviewers(): void
    {
        $this->submit(1000);
        $fileId = (int) DB::table('files')->value('id');
        $stranger = $this->user('09120000003', 'غریبه');
        $this->join($stranger, 'tenant');

        $this->as($this->tenant)->get(route('files.show', $fileId))->assertOk();
        $this->as($this->manager)->get(route('files.show', $fileId))->assertOk();
        $this->as($stranger)->get(route('files.show', $fileId))->assertForbidden();
    }

    public function test_pages_and_permissions(): void
    {
        $this->as($this->tenant)->get(route('payments.index'))->assertForbidden();
        $this->as($this->manager)->get(route('payments.index'))->assertOk();

        $this->as($this->tenant)->get(route('my.invoices'))->assertOk()->assertSee('T-1', false)->assertSee('معوق');
        $this->as($this->tenant)->get(route('my.payments'))->assertOk();

        $this->as($this->manager)->get(route('units.index'))->assertOk()->assertSee('بدهکار');
    }
}
