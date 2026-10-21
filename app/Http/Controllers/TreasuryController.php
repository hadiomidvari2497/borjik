<?php
namespace App\Http\Controllers;
use App\Models\Invoice;
use App\Models\PaymentReceipt;
use App\Models\TreasuryTransaction;
use App\Support\JalaliDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\Jalalian;
class TreasuryController extends Controller {
 public function index(Request $request) {
  $now=Jalalian::now(); $year=(int)$request->query('year',$now->getYear()); $month=(int)$request->query('month',$now->getMonth());
  abort_unless($year>=1380 && $year<=1500 && $month>=1 && $month<=12,404);
  $from=(new Jalalian($year,$month,1))->toCarbon()->startOfDay();
  $to=$from->copy()->addMonthNoOverflow();
  $query=TreasuryTransaction::where('transaction_date','>=',$from->toDateString())->where('transaction_date','<',$to->toDateString());
  $transactions=(clone $query)->orderByDesc('transaction_date')->orderByDesc('id')->paginate(20)->withQueryString();
  $income=(int)(clone $query)->where('type','income')->sum('amount');
  $expense=(int)(clone $query)->where('type','expense')->sum('amount');
  $open=Invoice::whereIn('status',['unpaid','partial']);
  $overdue=(clone $open)->whereDate('due_date','<',today());
  $receipts=(int)PaymentReceipt::where('status','approved')->whereBetween('paid_at',[$from->toDateString(),$to->copy()->subDay()->toDateString()])->sum('amount');
  return view('treasury.index',[
   'transactions'=>$transactions,'year'=>$year,'month'=>$month,'income'=>$income,'expense'=>$expense,'net'=>$income-$expense,
   'debt'=>(int)$open->sum(DB::raw('amount - paid_amount')),'overdueDebt'=>(int)$overdue->sum(DB::raw('amount - paid_amount')),
   'approvedReceipts'=>$receipts,'F'=>\App\Support\Fa::class,
  ]);
 }
 public function store(Request $request) {
  $v=$request->validate([
   'type'=>['required','in:income,expense'],'category'=>['required','string','max:100'],'title'=>['required','string','max:180'],
   'amount'=>['required','integer','min:1','max:99999999999'],'transaction_date'=>['required','string','max:20'],
   'payment_method'=>['required','in:cash,transfer,card,cheque,other'],'reference'=>['nullable','string','max:120'],'notes'=>['nullable','string','max:5000'],
  ]);
  $date=JalaliDate::parse($v['transaction_date']);
  if(!$date) throw ValidationException::withMessages(['transaction_date'=>'تاریخ شمسی معتبر نیست.']);
  $v['transaction_date']=$date->toDateString(); $v['recorded_by']=$request->user()->id;
  TreasuryTransaction::create($v);
  return back()->with('status','تراکنش خزانه ثبت شد.');
 }
 public function destroy(int $id) {
  TreasuryTransaction::findOrFail($id)->delete();
  return back()->with('status','تراکنش از دفتر خزانه حذف شد.');
 }
}