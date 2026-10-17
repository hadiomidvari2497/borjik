<?php

namespace App\Console\Commands;

use App\Models\Building;
use App\Models\Contract;
use App\Services\RentInvoiceService;
use App\Support\CurrentBuilding;
use Illuminate\Console\Command;

/** صدور صورت‌حساب اجاره‌ی ماهانه‌ی قراردادهای فعال همه‌ی ساختمان‌ها. در Scheduler روزانه اجرا می‌شود و تکرارناپذیر است. */
class IssueRentInvoices extends Command
{
    protected $signature = 'rent:issue-invoices';

    protected $description = 'صدور صورت‌حساب اجاره‌ی ماهانه‌ی قراردادهای فعال';

    public function handle(RentInvoiceService $service, CurrentBuilding $current): int
    {
        $issued = 0;
        $failed = 0;

        Building::where('status', 'active')->get()->each(function (Building $building) use ($service, $current, &$issued, &$failed) {
            $current->actingAs($building->id, function () use ($service, &$issued, &$failed) {
                Contract::active()->where('type', 'rent')->get()->each(function (Contract $contract) use ($service, &$issued, &$failed) {
                    try {
                        $issued += $service->issueFor($contract);
                    } catch (\Throwable $e) {
                        $failed++;
                        report($e); // خطای یک قرارداد، بقیه را متوقف نمی‌کند
                    }
                });
            });
        });

        $this->info("{$issued} صورت‌حساب اجاره صادر شد.");
        if ($failed > 0) {
            $this->error("{$failed} قرارداد با خطا مواجه شد (جزئیات در لاگ).");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
