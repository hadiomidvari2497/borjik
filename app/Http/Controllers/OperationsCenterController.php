<?php
namespace App\Http\Controllers;
use App\Models\Invoice;
use App\Models\MaintenancePlan;
use App\Models\PaymentReceipt;
use App\Models\SecurityShift;
use App\Models\Supply;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class OperationsCenterController extends Controller {
 public function index(Request $request) {
  $today=today();
  $open=Ticket::query()->whereNotIn('status',['done','closed']);
  $overdueTickets=(clone $open)->whereNotNull('deadline')->where('deadline','<',now())->orderBy('deadline')->limit(8)->get();
  $overdueInvoices=Invoice::query()->whereIn('status',['unpaid','partial'])->whereDate('due_date','<',$today);
  $weekTasks=(clone $open)->whereNotNull('deadline')->whereBetween('deadline',[$today->copy()->startOfDay(),$today->copy()->addDays(7)->endOfDay()])->orderBy('deadline')->limit(8)->get();
  $shifts=SecurityShift::with('user')->where(function($q) use($today) {
   $q->whereDate('shift_date',$today->toDateString())->orWhere(function($w) use($today){$w->whereNull('shift_date')->where('weekday',$today->dayOfWeek);});
  })->orderBy('start_time')->get();
  return view('operations-center.index',[
   'overdueTickets'=>$overdueTickets,
   'overdueInvoicesCount'=>(clone $overdueInvoices)->count(),
   'overdueDebt'=>(int)(clone $overdueInvoices)->sum(DB::raw('amount - paid_amount')),
   'openTicketsCount'=>(clone $open)->count(),
   'pendingReceipts'=>PaymentReceipt::where('status','pending')->count(),
   'weekTasks'=>$weekTasks,
   'shifts'=>$shifts,
   'maintenanceDue'=>MaintenancePlan::with('asset')->where('is_active',true)->whereDate('next_due_at','<=',$today->copy()->addDays(14))->orderBy('next_due_at')->limit(8)->get(),
   'lowSupplies'=>Supply::whereColumn('quantity','<=','min_quantity')->orderBy('name')->limit(8)->get(),
   'F'=>\App\Support\Fa::class,
  ]);
 }
}