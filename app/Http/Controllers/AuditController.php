<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Support\AuditPresenter;
use App\Support\CurrentBuilding;
use App\Support\JalaliDate;
use App\Support\MobileNumber;
use Illuminate\Http\Request;

/** تاریخچه‌ی تغییرات ساختمان (بند ۲.۳): چه کسی، چه زمانی، چه تغییری؛ فقط مالک و مدیر (audit.view). */
class AuditController extends Controller
{
    public function index(Request $request)
    {
        $q = AuditLog::with('user')->where('building_id', app(CurrentBuilding::class)->id())->orderByDesc('id');

        if (array_key_exists($model = (string) $request->query('model'), config('audit.models'))) {
            $q->where('auditable_type', $model);
        }
        if (array_key_exists($event = (string) $request->query('event'), config('audit.events'))) {
            $q->where('event', $event);
        }
        $id = MobileNumber::toLatinDigits(trim((string) $request->query('id')));
        if ($id !== '' && ctype_digit($id)) {
            $q->where('auditable_id', (int) $id);
        }
        if ($from = JalaliDate::parse((string) $request->query('from'))) {
            $q->where('created_at', '>=', $from->startOfDay());
        }
        if ($to = JalaliDate::parse((string) $request->query('to'))) {
            $q->where('created_at', '<=', $to->endOfDay());
        }

        $logs = $q->paginate(25)->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'rows' => $logs->getCollection()->map(fn (AuditLog $l) => AuditPresenter::describe($l)),
        ]);
    }
}
