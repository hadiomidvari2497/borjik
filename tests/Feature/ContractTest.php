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
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** قراردادهای فروش و اجاره: ثبت، اثر روی وضعیت واحد و ساکنین، پایان اجاره، دسترسی و فایل قرارداد. */
class ContractTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $tenant;
    private User $tenant2;
    private User $buyer;
    private User $buyer2;
    private User $stranger;
    private Building $building;
    private Unit $unit;

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
        $this->tenant2 = $this->user('09120000003', 'مستأجردوم');
        $this->buyer = $this->user('09120000004', 'خریدار');
        $this->buyer2 = $this->user('09120000005', 'خریداردوم');
        $this->stranger = $this->user('09120000006', 'بیگانه');

        $plan = SubscriptionPlan::create(['name' => 'p', 'units_limit' => 20, 'duration_months' => 1, 'price' => 0]);
        $sub = Subscription::create(['user_id' => $this->manager->id, 'plan_id' => $plan->id, 'units_limit' => 20, 'starts_at' => now()->subDay(), 'expires_at' => now()->addMonth(), 'status' => 'active']);
        $this->building = Building::create(['name' => 'برج آسمان', 'code' => '11111', 'created_by' => $this->manager->id, 'subscription_id' => $sub->id]);

        $this->join($this->manager, 'owner');
        $this->join($this->tenant, 'tenant');
        $this->join($this->tenant2, 'tenant');
        $this->join($this->buyer, 'unit_owner');
        $this->join($this->buyer2, 'unit_owner');

        $this->db(function () {
            $block = Block::create(['number' => 1, 'name' => 'الف', 'floors_count' => 5]);
            $this->unit = Unit::create(['block_id' => $block->id, 'number' => '1', 'area_m2' => 90, 'status' => 'vacant', 'residents_count' => 0]);
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

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('contract.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF");
    }

    private function rent(array $over = [])
    {
        return $this->as($this->manager)->post(route('contracts.store'), $over + [
            'type' => 'rent', 'unit_id' => $this->unit->id, 'user_id' => $this->tenant->id,
            'deposit_amount' => '۱۰۰٬۰۰۰٬۰۰۰', 'monthly_rent' => '۵٬۰۰۰٬۰۰۰',
            'signed_at' => JalaliDate::format(today()), 'end_date' => JalaliDate::format(today()->addYear()), 'charge_payer' => 'tenant',
        ]);
    }

    private function sale(User $buyer, array $over = [])
    {
        return $this->as($this->manager)->post(route('contracts.store'), $over + [
            'type' => 'sale', 'unit_id' => $this->unit->id, 'user_id' => $buyer->id,
            'sale_price' => '۹٬۰۰۰٬۰۰۰٬۰۰۰', 'signed_at' => JalaliDate::format(today()),
        ]);
    }

    private function unitStatus(): string
    {
        return (string) DB::table('units')->where('id', $this->unit->id)->value('status');
    }

    private function contractId(int $n = 1): int
    {
        return (int) DB::table('contracts')->orderBy('id')->skip($n - 1)->value('id');
    }

    public function test_rent_contract_marks_the_unit_rented_and_registers_the_tenant(): void
    {
        $this->rent(['contract_file' => $this->pdf()])->assertSessionHasNoErrors();

        $id = $this->contractId();
        $this->assertDatabaseHas('contracts', ['id' => $id, 'type' => 'rent', 'status' => 'active', 'deposit_amount' => 100000000, 'monthly_rent' => 5000000, 'charge_payer' => 'tenant', 'created_by' => $this->manager->id]);
        $this->assertSame('rented', $this->unitStatus());
        $this->assertDatabaseHas('unit_parties', ['unit_id' => $this->unit->id, 'user_id' => $this->tenant->id, 'relation' => 'tenant', 'contract_id' => $id, 'to_date' => null]);

        $file = DB::table('files')->first();
        Storage::disk('local')->assertExists($file->path);
        $this->assertSame('private', $file->visibility);
        $this->assertSame(1, DB::table('file_links')->where('attachable_type', 'contract')->where('attachable_id', $id)->count());
        $this->as($this->manager)->get(route('contracts.show', $id))->assertOk();
    }

    public function test_a_unit_cannot_have_two_active_rent_contracts(): void
    {
        $this->rent()->assertSessionHasNoErrors();
        $this->rent(['user_id' => $this->tenant2->id])->assertSessionHasErrors('general');

        $this->assertSame(1, DB::table('contracts')->count());
        $this->assertSame(1, DB::table('unit_parties')->count());
    }

    public function test_rent_validations(): void
    {
        $this->rent(['end_date' => JalaliDate::format(today()->subDay())])->assertSessionHasErrors('end_date');
        $this->rent(['signed_at' => JalaliDate::format(today()->addDay())])->assertSessionHasErrors('signed_at');
        $this->rent(['monthly_rent' => '0'])->assertSessionHasErrors('monthly_rent');
        $this->rent(['charge_payer' => 'x'])->assertSessionHasErrors('charge_payer');
        $this->rent(['user_id' => $this->stranger->id])->assertSessionHasErrors('general'); // عضو ساختمان با نقش مستأجر نیست
        $this->rent(['user_id' => $this->buyer->id])->assertSessionHasErrors('general');    // مالک واحد است، مستأجر نیست

        DB::table('units')->where('id', $this->unit->id)->update(['status' => 'repair']);
        $this->rent()->assertSessionHasErrors('general');

        $this->assertSame(0, DB::table('contracts')->count());
    }

    public function test_ending_a_rent_frees_the_unit(): void
    {
        $this->rent();
        $id = $this->contractId();

        $this->as($this->manager)->post(route('contracts.end', $id))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contracts', ['id' => $id, 'status' => 'ended']);
        $this->assertNotNull(DB::table('contracts')->where('id', $id)->value('ended_at'));
        $this->assertNull(DB::table('unit_parties')->where('contract_id', $id)->whereNull('to_date')->first());
        $this->assertSame('vacant', $this->unitStatus());

        $this->as($this->manager)->post(route('contracts.end', $id))->assertSessionHasErrors('general'); // دوباره قابل پایان دادن نیست
        $this->rent(['user_id' => $this->tenant2->id])->assertSessionHasNoErrors();                         // حالا قرارداد جدید ممکن است
        $this->assertSame('rented', $this->unitStatus());
    }

    public function test_sale_transfers_ownership_and_ends_the_previous_owner(): void
    {
        $this->sale($this->buyer)->assertSessionHasNoErrors();
        $this->assertSame('sold', $this->unitStatus());
        $this->assertDatabaseHas('unit_parties', ['unit_id' => $this->unit->id, 'user_id' => $this->buyer->id, 'relation' => 'owner', 'to_date' => null]);
        $this->assertDatabaseHas('contracts', ['type' => 'sale', 'sale_price' => 9000000000, 'charge_payer' => 'owner', 'status' => 'active']);

        $this->sale($this->buyer)->assertSessionHasErrors('general'); // مالک فعلی است

        $this->sale($this->buyer2)->assertSessionHasNoErrors();
        $this->assertNotNull(DB::table('unit_parties')->where('user_id', $this->buyer->id)->value('to_date'));
        $this->assertSame(1, DB::table('unit_parties')->where('unit_id', $this->unit->id)->where('relation', 'owner')->whereNull('to_date')->count());
        $this->assertSame('ended', DB::table('contracts')->where('id', $this->contractId(1))->value('status'));
        $this->assertSame('active', DB::table('contracts')->where('id', $this->contractId(2))->value('status'));
    }

    public function test_sale_validations_and_buyer_role(): void
    {
        $this->sale($this->buyer, ['sale_price' => '0'])->assertSessionHasErrors('sale_price');
        $this->sale($this->tenant)->assertSessionHasErrors('general'); // مستأجر است، مالک واحد نیست
        $this->assertSame(0, DB::table('contracts')->count());
    }

    public function test_selling_a_rented_unit_keeps_it_rented(): void
    {
        $this->rent();
        $this->sale($this->buyer)->assertSessionHasNoErrors();

        $this->assertSame('rented', $this->unitStatus());

        $this->as($this->manager)->post(route('contracts.end', $this->contractId(1)));
        $this->assertSame('sold', $this->unitStatus()); // با پایان اجاره، واحدِ دارای مالک «فروخته‌شده» می‌شود
    }

    public function test_charge_payer_can_change_only_on_an_active_rent(): void
    {
        $this->rent();
        $id = $this->contractId();

        $this->as($this->manager)->put(route('contracts.payer', $id), ['charge_payer' => 'owner'])->assertSessionHasNoErrors();
        $this->assertSame('owner', DB::table('contracts')->where('id', $id)->value('charge_payer'));
        $this->as($this->manager)->put(route('contracts.payer', $id), ['charge_payer' => 'x'])->assertSessionHasErrors('charge_payer');

        $this->as($this->manager)->post(route('contracts.end', $id));
        $this->as($this->manager)->put(route('contracts.payer', $id), ['charge_payer' => 'tenant'])->assertSessionHasErrors('general');
    }

    public function test_only_managers_reach_the_contract_pages(): void
    {
        $this->rent();
        $id = $this->contractId();

        $this->as($this->tenant)->get(route('contracts.index'))->assertForbidden();
        $this->as($this->tenant)->get(route('contracts.create'))->assertForbidden();
        $this->as($this->tenant)->get(route('contracts.show', $id))->assertForbidden();
        $this->as($this->tenant)->post(route('contracts.end', $id))->assertForbidden();

        $this->as($this->manager)->get(route('contracts.index'))->assertOk();
        $this->as($this->manager)->get(route('contracts.create', ['type' => 'rent']))->assertOk();
        $this->as($this->manager)->get(route('contracts.create', ['type' => 'sale']))->assertOk();
    }

    public function test_my_contracts_lists_own_contracts_and_rents_of_owned_units(): void
    {
        $this->sale($this->buyer);
        $this->rent();

        $this->as($this->tenant)->get(route('my.contracts'))->assertOk()->assertSee('قرارداد اجاره')->assertDontSee('قرارداد خرید');
        $this->as($this->buyer)->get(route('my.contracts'))->assertOk()->assertSee('قرارداد خرید')->assertSee('قرارداد اجاره'); // مالک، اجاره‌ی واحد خودش را هم می‌بیند
        $this->as($this->tenant2)->get(route('my.contracts'))->assertOk()->assertDontSee('قرارداد اجاره —');
    }

    public function test_contract_file_is_visible_only_to_managers_parties_and_the_unit_owner(): void
    {
        $this->sale($this->buyer);
        $this->rent(['contract_file' => $this->pdf()]);
        $fileId = (int) DB::table('files')->value('id');

        $this->as($this->manager)->get(route('files.show', $fileId))->assertOk();
        $this->as($this->tenant)->get(route('files.show', $fileId))->assertOk();     // طرف قرارداد
        $this->as($this->buyer)->get(route('files.show', $fileId))->assertOk();      // مالک فعلی واحد
        $this->as($this->tenant2)->get(route('files.show', $fileId))->assertForbidden();
        $this->as($this->buyer2)->get(route('files.show', $fileId))->assertForbidden();
    }

    public function test_attaching_a_file_replaces_the_previous_one(): void
    {
        $this->rent(['contract_file' => $this->pdf()]);
        $id = $this->contractId();
        $old = DB::table('files')->first();

        $this->as($this->manager)->post(route('contracts.file', $id), ['contract_file' => $this->pdf()])->assertSessionHasNoErrors();

        $this->assertSame(1, DB::table('file_links')->where('attachable_type', 'contract')->where('attachable_id', $id)->count());
        $this->assertSame(1, DB::table('files')->count());
        $this->assertNotSame($old->path, DB::table('files')->value('path'));
        Storage::disk('local')->assertMissing($old->path);

        $this->as($this->manager)->post(route('contracts.file', $id), ['contract_file' => UploadedFile::fake()->create('x.exe', 10)])->assertSessionHasErrors('contract_file');
    }
}
