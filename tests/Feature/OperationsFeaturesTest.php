<?php
namespace Tests\Feature;
use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\MaintenancePlan;
use App\Models\Role;
use App\Models\User;
use App\Support\CurrentBuilding;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class OperationsFeaturesTest extends TestCase {
 use RefreshDatabase;
 private Building $building;
 private User $owner;
 protected function setUp(): void {
  parent::setUp(); $this->seed(RolePermissionSeeder::class);
  $this->owner=User::create(['mobile'=>'09120000991','first_name'=>'مالک','last_name'=>'تست','status'=>'active','profile_completed_at'=>now()]);
  $this->building=Building::create(['name'=>'ساختمان عملیات','code'=>'90991','created_by'=>$this->owner->id]);
  BuildingUserRole::create(['building_id'=>$this->building->id,'user_id'=>$this->owner->id,'role_id'=>Role::where('key','owner')->value('id'),'status'=>'active']);
 }
 private function open(string $method,string $uri,array $data=[]) {
  return $this->actingAs($this->owner)->withSession(['current_building_id'=>$this->building->id])->{$method}($uri,$data);
 }
 public function test_operations_center_shows_building_operational_overview(): void {
  $this->open('get',route('operations-center.index'))->assertOk()->assertSee('نمای کلی عملیات امروز')->assertSee('شیفت حراست امروز');
 }
 public function test_maintenance_asset_and_plan_accept_jalali_dates(): void {
  $this->open('post',route('maintenance.assets.store'),['name'=>'آسانسور اصلی','category'=>'آسانسور','installed_at'=>'۱۴۰۵/۰۷/۲۹'])->assertRedirect()->assertSessionHasNoErrors();
  $assetId=(int)\Illuminate\Support\Facades\DB::table('maintenance_assets')->value('id');
  $this->assertDatabaseHas('maintenance_assets',['id'=>$assetId,'building_id'=>$this->building->id,'name'=>'آسانسور اصلی','installed_at'=>'2026-10-21 00:00:00']);
  $this->open('post',route('maintenance.plans.store'),['asset_id'=>$assetId,'title'=>'بازدید ایمنی','frequency'=>'monthly','next_due_at'=>'۱۴۰۵/۰۸/۱۰'])->assertRedirect()->assertSessionHasNoErrors();
  $planId=(int)\Illuminate\Support\Facades\DB::table('maintenance_plans')->value('id');
  $this->assertDatabaseHas('maintenance_plans',['id'=>$planId,'building_id'=>$this->building->id,'next_due_at'=>'2026-11-01 00:00:00']);
  $this->open('post',route('maintenance.plans.complete',$planId),['performed_at'=>'۱۴۰۵/۰۷/۲۹','cost'=>'250000','notes'=>'سرویس دوره‌ای'])->assertRedirect()->assertSessionHasNoErrors();
  $this->assertDatabaseHas('maintenance_records',['plan_id'=>$planId,'building_id'=>$this->building->id,'performed_at'=>'2026-10-21 00:00:00','cost'=>250000]);
 }
 public function test_treasury_accepts_jalali_date_and_is_building_scoped(): void {
  $this->open('post',route('treasury.store'),['type'=>'expense','category'=>'تعمیرات','title'=>'تعمیر پمپ','amount'=>'125000','transaction_date'=>'۱۴۰۵/۰۷/۲۹','payment_method'=>'transfer'])->assertRedirect()->assertSessionHasNoErrors();
  $this->assertDatabaseHas('treasury_transactions',['building_id'=>$this->building->id,'type'=>'expense','title'=>'تعمیر پمپ','amount'=>125000,'transaction_date'=>'2026-10-21 00:00:00']);
  $other=Building::create(['name'=>'ساختمان دیگر','code'=>'90992','created_by'=>$this->owner->id]);
  app(CurrentBuilding::class)->actingAs($other->id,function(){\App\Models\TreasuryTransaction::create(['type'=>'income','category'=>'سایر','title'=>'رکورد خصوصی','amount'=>1,'transaction_date'=>'2026-10-21','payment_method'=>'cash']);});
  $this->open('get',route('treasury.index'))->assertOk()->assertSee('تعمیر پمپ')->assertDontSee('رکورد خصوصی');
 }
 public function test_tenants_cannot_open_management_modules(): void {
  $tenant=User::create(['mobile'=>'09120000992','first_name'=>'ساکن','last_name'=>'تست','status'=>'active','profile_completed_at'=>now()]);
  BuildingUserRole::create(['building_id'=>$this->building->id,'user_id'=>$tenant->id,'role_id'=>Role::where('key','tenant')->value('id'),'status'=>'active']);
  $this->actingAs($tenant)->withSession(['current_building_id'=>$this->building->id])->get(route('maintenance.index'))->assertForbidden();
  $this->actingAs($tenant)->withSession(['current_building_id'=>$this->building->id])->get(route('treasury.index'))->assertForbidden();
 }
}