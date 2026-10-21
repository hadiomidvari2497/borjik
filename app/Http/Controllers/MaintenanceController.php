<?php
namespace App\Http\Controllers;
use App\Models\MaintenanceAsset;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceRecord;
use App\Models\User;
use App\Support\CurrentBuilding;
use App\Support\JalaliDate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
class MaintenanceController extends Controller {
 public function index() {
  $buildingId=app(CurrentBuilding::class)->id();
  return view('maintenance.index',[
   'assets'=>MaintenanceAsset::with(['plans'=>fn($q)=>$q->where('is_active',true)->orderBy('next_due_at')])->orderBy('name')->get(),
   'plans'=>MaintenancePlan::with(['asset','assignee'])->where('is_active',true)->orderBy('next_due_at')->get(),
   'records'=>MaintenanceRecord::with(['asset','performer'])->orderByDesc('performed_at')->limit(20)->get(),
   'staff'=>User::whereHas('buildingRoles',fn($q)=>$q->where('building_id',$buildingId)->where('status','active')->whereHas('role',fn($r)=>$r->whereIn('key',['maintenance','manager'])))->orderBy('first_name')->get(),
   'F'=>\App\Support\Fa::class,
  ]);
 }
 public function storeAsset(Request $request) {
  $v=$request->validate([
   'name'=>['required','string','max:150'],'category'=>['nullable','string','max:80'],'location'=>['nullable','string','max:150'],
   'manufacturer'=>['nullable','string','max:120'],'model'=>['nullable','string','max:120'],'serial_number'=>['nullable','string','max:120'],
   'installed_at'=>['nullable','string','max:20'],'warranty_until'=>['nullable','string','max:20'],'notes'=>['nullable','string','max:5000'],
  ]);
  foreach(['installed_at','warranty_until'] as $field) {
   if(filled($v[$field]??null)) { $date=JalaliDate::parse($v[$field]); if(!$date) throw ValidationException::withMessages([$field=>'تاریخ شمسی معتبر نیست.']); $v[$field]=$date->toDateString(); }
   else $v[$field]=null;
  }
  MaintenanceAsset::create($v);
  return back()->with('status','تجهیز ساختمان ثبت شد.');
 }
 public function storePlan(Request $request) {
  $buildingId=app(CurrentBuilding::class)->id();
  $v=$request->validate([
   'asset_id'=>['required','integer',Rule::exists('maintenance_assets','id')->where('building_id',$buildingId)],
   'title'=>['required','string','max:180'],'description'=>['nullable','string','max:5000'],
   'frequency'=>['required','in:weekly,monthly,quarterly,semiannual,annual,custom'],'interval_days'=>['nullable','integer','between:1,3650'],
   'next_due_at'=>['required','string','max:20'],'estimated_cost'=>['nullable','integer','min:0','max:99999999999'],
   'assigned_to'=>['nullable','integer',Rule::exists('building_user_roles','user_id')->where('building_id',$buildingId)->where('status','active')],
  ]);
  $date=JalaliDate::parse($v['next_due_at']);
  if(!$date) throw ValidationException::withMessages(['next_due_at'=>'تاریخ سررسید شمسی معتبر نیست.']);
  if($v['frequency']==='custom' && empty($v['interval_days'])) throw ValidationException::withMessages(['interval_days'=>'برای بازه دلخواه، فاصله روزها را وارد کن.']);
  $v['next_due_at']=$date->toDateString();
  MaintenancePlan::create($v);
  return back()->with('status','برنامه سرویس دوره‌ای ثبت شد.');
 }
 public function complete(Request $request,int $plan) {
  $item=MaintenancePlan::with('asset')->findOrFail($plan);
  $v=$request->validate(['performed_at'=>['required','string','max:20'],'cost'=>['nullable','integer','min:0','max:99999999999'],'notes'=>['nullable','string','max:5000']]);
  $date=JalaliDate::parse($v['performed_at']);
  if(!$date) throw ValidationException::withMessages(['performed_at'=>'تاریخ انجام شمسی معتبر نیست.']);
  MaintenanceRecord::create(['asset_id'=>$item->asset_id,'plan_id'=>$item->id,'title'=>$item->title,'performed_at'=>$date->toDateString(),'cost'=>(int)($v['cost']??0),'notes'=>$v['notes']??null,'performed_by'=>$request->user()->id]);
  $next=$item->next_due_at->copy();
  do {
   switch($item->frequency) {
    case 'weekly': $next->addWeek(); break;
    case 'monthly': $next->addMonthNoOverflow(); break;
    case 'quarterly': $next->addMonthsNoOverflow(3); break;
    case 'semiannual': $next->addMonthsNoOverflow(6); break;
    case 'annual': $next->addYearNoOverflow(); break;
    default: $next->addDays(max(1,(int)$item->interval_days)); break;
   }
  } while($next->lt($date));
  $item->update(['next_due_at'=>$next->toDateString()]);
  return back()->with('status','سرویس ثبت شد و سررسید بعدی به‌روزرسانی شد.');
 }
}