<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\File;
use App\Models\FileLink;
use App\Models\UnitParty;
use App\Services\ContractService;
use App\Support\CurrentBuilding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /** تصویر فیش را فقط بارگذارنده یا کاربری با مجوز بررسی پرداخت (در همان ساختمان) می‌بیند. */
    public function show(Request $request, int $id)
    {
        $file = File::findOrFail($id);
        $user = $request->user();

        abort_unless((int) $file->building_id === app(CurrentBuilding::class)->id(), 404);
        abort_unless((int) $file->uploader_id === (int) $user->id || $user->can('payment.review') || $this->canViewContractFile($file, $user), 403);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->response($file->path, $file->original_name, [
            'Content-Type' => $file->mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }

    /** فایل قرارداد: مالک/مدیر (contract.manage)، طرف قرارداد یا مالک فعلی همان واحد. */
    private function canViewContractFile(File $file, $user): bool
    {
        $link = FileLink::where('file_id', $file->id)->where('attachable_type', ContractService::FILE_TYPE)->first();
        if (! $link) {
            return false;
        }
        if ($user->can('contract.manage')) {
            return true;
        }

        $contract = Contract::find($link->attachable_id);
        if (! $contract) {
            return false;
        }
        if ((int) $contract->user_id === (int) $user->id) {
            return true;
        }

        return UnitParty::where('unit_id', $contract->unit_id)->where('user_id', $user->id)->where('relation', 'owner')->whereNull('to_date')->exists();
    }
}
