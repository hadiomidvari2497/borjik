<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\ReceiptStatus;
use App\Models\Building;
use App\Models\File;
use App\Models\FileLink;
use App\Models\Invoice;
use App\Models\PaymentAllocation;
use App\Models\PaymentReceipt;
use App\Models\Unit;
use App\Models\User;
use App\Services\Sms\SmsSender;
use App\Support\CurrentBuilding;
use App\Support\Fa;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * ثبت و تأیید فیش و تخصیص مبلغ به صورت‌حساب‌ها (بند ۴.۱۵.۲ سند):
 * قدیمی‌ترین صورت‌حساب پرداخت‌نشده‌ی همان واحد اول، پرداخت جزئی مجاز، و مازاد به‌عنوان بستانکاری
 * در صورت‌حساب‌های بعدی کسر می‌شود (reallocate بعد از هر انتشار شارژ).
 */
class PaymentService
{
    public const FILE_TYPE = 'payment_receipt';

    public function __construct(private readonly SmsSender $sms)
    {
    }

    public function submit(User $user, Unit $unit, int $amount, CarbonInterface $paidAt, ?string $note, UploadedFile $upload): PaymentReceipt
    {
        $buildingId = app(CurrentBuilding::class)->id();
        $hash = hash_file('sha256', $upload->getRealPath());

        $meta = ['mime' => $upload->getMimeType(), 'size' => $upload->getSize(), 'name' => $upload->getClientOriginalName()];

        return DB::transaction(function () use ($user, $unit, $amount, $paidAt, $note, $buildingId, $hash, $meta, $upload) {
            // Serialize receipt submissions for this building before checking the hash.
            // Without this lock, simultaneous requests can both pass the duplicate check.
            Building::query()->whereKey($buildingId)->lockForUpdate()->firstOrFail();

            // Only pending/approved receipts reserve a hash; rejected receipts may be resubmitted.
            $duplicate = File::where('building_id', $buildingId)->where('sha256', $hash)
                ->whereIn('id', FileLink::where('attachable_type', self::FILE_TYPE)
                    ->whereIn('attachable_id', PaymentReceipt::whereIn('status', ['pending', 'approved'])->select('id'))->select('file_id'))
                ->exists();
            if ($duplicate) {
                throw new DomainException('این تصویر فیش قبلاً ثبت شده است.');
            }

            $path = $upload->store("receipts/{$buildingId}", 'local');
            if (! is_string($path) || $path === '') {
                throw new DomainException('ذخیره‌ی تصویر فیش ناموفق بود.');
            }

            $file = File::create([
                'uploader_id' => $user->id, 'building_id' => $buildingId, 'disk' => 'local', 'path' => $path,
                'original_name' => $meta['name'], 'mime' => $meta['mime'], 'size' => $meta['size'], 'sha256' => $hash, 'visibility' => 'private',
            ]);
            $receipt = PaymentReceipt::create([
                'unit_id' => $unit->id, 'user_id' => $user->id, 'amount' => $amount, 'paid_at' => $paidAt,
                'status' => ReceiptStatus::Pending, 'note' => $note,
            ]);
            FileLink::create(['file_id' => $file->id, 'attachable_type' => self::FILE_TYPE, 'attachable_id' => $receipt->id, 'collection' => 'receipt']);

            return $receipt;
        });
    }

    public function approve(PaymentReceipt $receipt, User $reviewer): void
    {
        DB::transaction(function () use ($receipt, $reviewer) {
            $locked = PaymentReceipt::lockForUpdate()->findOrFail($receipt->id);
            if ($locked->status !== ReceiptStatus::Pending) {
                throw new DomainException('این فیش قبلاً بررسی شده است.');
            }
            $locked->update(['status' => ReceiptStatus::Approved, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'reject_reason' => null]);
            $this->reallocate($locked->unit_id);
        });

        $this->notify($receipt->fresh(), 'receipt_approved');
    }

    public function reject(PaymentReceipt $receipt, User $reviewer, string $reason): void
    {
        DB::transaction(function () use ($receipt, $reviewer, $reason) {
            $locked = PaymentReceipt::lockForUpdate()->findOrFail($receipt->id);
            if ($locked->status !== ReceiptStatus::Pending) {
                throw new DomainException('این فیش قبلاً بررسی شده است.');
            }
            $locked->update(['status' => ReceiptStatus::Rejected, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'reject_reason' => $reason]);
        });

        $this->notify($receipt->fresh(), 'receipt_rejected');
    }

    /**
     * فیش‌های تأییدشده‌ی واحد را (قدیمی‌ترین اول) روی صورت‌حساب‌های پرداخت‌نشده (سررسید قدیمی‌تر اول) پخش می‌کند.
     * امن برای اجرای مکرر: فقط مانده‌ی تخصیص‌نیافته‌ی هر فیش مصرف می‌شود.
     */
    public function reallocate(int $unitId): void
    {
        DB::transaction(function () use ($unitId) {
            $invoices = Invoice::where('unit_id', $unitId)->whereIn('status', ['unpaid', 'partial'])
                ->orderBy('due_date')->orderBy('id')->lockForUpdate()->get();
            if ($invoices->isEmpty()) {
                return;
            }

            $receipts = PaymentReceipt::where('unit_id', $unitId)->where('status', 'approved')
                ->orderBy('paid_at')->orderBy('id')->lockForUpdate()->get();
            $used = PaymentAllocation::whereIn('receipt_id', $receipts->pluck('id'))
                ->selectRaw('receipt_id, sum(amount) as s')->groupBy('receipt_id')->pluck('s', 'receipt_id');

            foreach ($receipts as $receipt) {
                $remaining = (int) $receipt->amount - (int) ($used[$receipt->id] ?? 0);

                foreach ($invoices as $invoice) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $need = (int) $invoice->amount - (int) $invoice->paid_amount;
                    if ($need <= 0) {
                        continue;
                    }
                    $take = min($remaining, $need);

                    $allocation = PaymentAllocation::firstOrNew(['receipt_id' => $receipt->id, 'invoice_id' => $invoice->id]);
                    $allocation->amount = (int) ($allocation->amount ?? 0) + $take;
                    $allocation->save();

                    $invoice->paid_amount = (int) $invoice->paid_amount + $take;
                    $remaining -= $take;
                }
            }

            foreach ($invoices as $invoice) {
                $invoice->status = $invoice->paid_amount >= $invoice->amount
                    ? InvoiceStatus::Paid
                    : ($invoice->paid_amount > 0 ? InvoiceStatus::Partial : InvoiceStatus::Unpaid);
                $invoice->save();
            }
        });
    }

    private function notify(PaymentReceipt $receipt, string $template): void
    {
        $user = User::find($receipt->user_id);
        $unit = Unit::with('block')->find($receipt->unit_id);
        if (! $user || ! $unit) {
            return;
        }

        $this->sms->sendTemplate($template, $user->mobile, [
            'amount' => Fa::num($receipt->amount),
            'unit' => $unit->block->name.' / '.Fa::digits($unit->number),
            'building' => Building::find($receipt->building_id)?->name,
            'reason' => $receipt->reject_reason,
        ], $user->id, $receipt->building_id);
    }
}
