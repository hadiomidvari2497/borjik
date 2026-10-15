<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\FileLink;
use App\Models\UnitParty;
use App\Services\ContractService;
use Illuminate\Http\Request;

/** «قرارداد من»: قراردادهایی که کاربر طرف آن است، و قراردادهای اجاره‌ی واحدهایی که مالک فعلی‌شان است. */
class MyContractController extends Controller
{
    public function index(Request $request)
    {
        $uid = $request->user()->id;
        $owned = UnitParty::where('user_id', $uid)->where('relation', 'owner')->whereNull('to_date')->pluck('unit_id');

        $contracts = Contract::with(['unit.block', 'user'])
            ->where(fn ($q) => $q->where('user_id', $uid)->orWhere(fn ($w) => $w->where('type', 'rent')->whereIn('unit_id', $owned)))
            ->orderByDesc('id')->get();

        $files = FileLink::where('attachable_type', ContractService::FILE_TYPE)->whereIn('attachable_id', $contracts->pluck('id'))
            ->orderByDesc('id')->get()->unique('attachable_id')->pluck('file_id', 'attachable_id');

        return view('my.contracts', ['contracts' => $contracts, 'files' => $files, 'uid' => $uid]);
    }
}
