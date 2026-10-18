<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Building;
use App\Models\ChargeSetting;
use App\Models\Invoice;
use App\Services\Charge\LateFeeCalculator;
use App\Services\PaymentService;
use App\Support\CurrentBuilding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IssueLateFeeInvoices extends Command
{
    protected $signature = 'charges:issue-late-fees';
    protected $description = 'Issue overdue late-fee invoices';

    public function handle(CurrentBuilding $current, PaymentService $payments): int
    {
        $issued = 0;
        $today = today();

        Building::where('status', 'active')->get()->each(function (Building $building) use (&$issued, $current, $payments, $today) {
            $current->actingAs($building->id, function () use (&$issued, $payments, $today) {
                $settings = ChargeSetting::first();
                if (! $settings || ! $settings->late_fee_enabled || (int) $settings->late_fee_value <= 0) {
                    return;
                }

                $ids = Invoice::query()
                    ->where('type', '!=', InvoiceType::Penalty->value)
                    ->whereIn('status', [InvoiceStatus::Unpaid->value, InvoiceStatus::Partial->value])
                    ->whereDate('due_date', '<', $today)
                    ->orderBy('due_date')->orderBy('id')->pluck('id');

                foreach ($ids as $id) {
                    $unitId = DB::transaction(function () use ($id, $settings, $today, &$issued) {
                        $invoice = Invoice::query()->whereKey($id)->lockForUpdate()->first();
                        if (! $invoice || ! in_array($invoice->status, [InvoiceStatus::Unpaid, InvoiceStatus::Partial], true)
                            || $invoice->type === InvoiceType::Penalty || ! $invoice->due_date->lt($today)) {
                            return null;
                        }

                        $outstanding = max(0, (int) $invoice->amount - (int) $invoice->paid_amount);
                        if ($outstanding === 0) {
                            return null;
                        }

                        $daysLate = (int) $invoice->due_date->diffInDays($today);
                        $totalFee = LateFeeCalculator::compute(
                            $outstanding, $daysLate, (string) $settings->late_fee_type,
                            (int) $settings->late_fee_value,
                            $settings->late_fee_cap === null ? null : (int) $settings->late_fee_cap,
                            (int) ($settings->late_fee_start_day ?? 1),
                        );
                        $already = (int) Invoice::query()->where('parent_invoice_id', $invoice->id)
                            ->where('type', InvoiceType::Penalty->value)
                            ->where('status', '!=', InvoiceStatus::Void->value)->sum('amount');
                        $delta = $totalFee - $already;
                        if ($delta <= 0) {
                            return null;
                        }

                        $number = sprintf('PF-%d-%s', $invoice->id, $today->format('Ymd'));
                        if (Invoice::query()->where('number', $number)->exists()) {
                            return null;
                        }

                        Invoice::create([
                            'unit_id' => $invoice->unit_id,
                            'contract_id' => $invoice->contract_id,
                            'payer_user_id' => $invoice->payer_user_id,
                            'parent_invoice_id' => $invoice->id,
                            'type' => InvoiceType::Penalty,
                            'number' => $number,
                            'amount' => $delta,
                            'paid_amount' => 0,
                            'issued_at' => $today,
                            'due_date' => $today->copy()->addDays((int) $settings->due_days),
                            'status' => InvoiceStatus::Unpaid,
                            'breakdown' => [
                                'source_invoice' => $invoice->number,
                                'days_late' => $daysLate,
                                'cumulative_fee' => $totalFee,
                                'previously_issued' => $already,
                            ],
                        ]);
                        $issued++;
                        return (int) $invoice->unit_id;
                    });

                    if ($unitId !== null) {
                        $payments->reallocate($unitId);
                    }
                }
            });
        });

        $this->info("Issued {$issued} late-fee invoice(s).");
        return self::SUCCESS;
    }
}
