<?php

namespace App\Services;

use App\Enums\ChargePayer;
use App\Enums\ContractStatus;
use App\Enums\ContractType;
use App\Enums\UnitStatus;
use App\Models\Contract;
use App\Models\File;
use App\Models\FileLink;
use App\Models\Unit;
use App\Models\UnitParty;
use App\Models\User;
use App\Support\Fa;
use App\Support\CurrentBuilding;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * قراردادهای فروش و اجاره (بند ۴.۱۳ سند).
 * - ثبت قرارداد، وضعیت واحد و ارتباط عضو با واحد (UnitParty) را در همان تراکنش به‌روز می‌کند.
 * - هر واحد در هر لحظه فقط یک قرارداد اجاره‌ی فعال و یک مالک فعال دارد.
 * - فروش به‌صورت «انتقال مالکیت» ثبت می‌شود: مالک قبلی از تاریخ فروش پایان می‌یابد.
 */
class ContractService
{
    public const FILE_TYPE = 'contract';

    public function __construct(private readonly Notifier $notifier)
    {
    }

    /** اعضای ساختمان با نقش مالک واحد یا مستأجر (فعال یا دعوت‌شده). @return Collection<int, object{id:int,name:string,mobile:?string,status:string}> */
    public function candidates(string $roleKey): Collection
    {
        return $this->memberQuery($roleKey)
            ->orderBy('u.last_name')->orderBy('u.first_name')
            ->get(['u.id', 'u.first_name', 'u.last_name', 'u.company_name', 'u.person_type', 'u.mobile', 'bur.status'])
            ->unique('id')
            ->map(function ($r) {
                $name = $r->person_type === 'legal' ? (string) $r->company_name : trim($r->first_name.' '.$r->last_name);

                return (object) ['id' => (int) $r->id, 'name' => $name !== '' ? $name : (string) $r->mobile, 'mobile' => $r->mobile, 'status' => $r->status];
            })->values();
    }

    public function registerRent(User $by, Unit $unit, User $tenant, int $deposit, int $monthlyRent, CarbonInterface $signedAt, CarbonInterface $endDate, ChargePayer $payer, ?UploadedFile $file = null): Contract
    {
        if (! $this->isMember($tenant->id, 'tenant')) {
            throw new DomainException('مستأجر باید ابتدا با نقش «مستأجر» به‌عنوان عضو ساختمان ثبت شده باشد.');
        }
        if ($endDate->lte($signedAt)) {
            throw new DomainException('تاریخ پایان قرارداد باید بعد از تاریخ عقد باشد.');
        }

        $contract = $this->withStoredFile($file, function (?array $meta) use ($by, $unit, $tenant, $deposit, $monthlyRent, $signedAt, $endDate, $payer) {
            return DB::transaction(function () use ($by, $unit, $tenant, $deposit, $monthlyRent, $signedAt, $endDate, $payer, $meta) {
                $locked = Unit::lockForUpdate()->findOrFail($unit->id);

                if ($locked->status === UnitStatus::Repair) {
                    throw new DomainException('این واحد در حال تعمیر است و فعلاً قابل اجاره نیست.');
                }
                if ($this->activeRent($locked->id)->exists()) {
                    throw new DomainException('این واحد قرارداد اجاره‌ی فعال دارد؛ ابتدا آن را پایان دهید.');
                }

                $contract = Contract::create([
                    'unit_id' => $locked->id, 'user_id' => $tenant->id, 'type' => ContractType::Rent,
                    'deposit_amount' => $deposit, 'monthly_rent' => $monthlyRent,
                    'signed_at' => $signedAt, 'start_date' => $signedAt, 'end_date' => $endDate,
                    'charge_payer' => $payer, 'status' => ContractStatus::Active, 'created_by' => $by->id,
                ]);

                // اگر مستأجر دیگری هنوز به‌عنوان ساکن ثبت است، از تاریخ عقد پایان می‌یابد
                UnitParty::where('unit_id', $locked->id)->where('relation', 'tenant')->whereNull('to_date')->get()
                    ->each(fn (UnitParty $p) => $p->update(['to_date' => $signedAt]));
                UnitParty::create(['unit_id' => $locked->id, 'user_id' => $tenant->id, 'relation' => 'tenant', 'contract_id' => $contract->id, 'from_date' => $signedAt]);

                $this->syncUnitStatus($locked);
                if ($meta) {
                    $this->link($contract, $meta, $by);
                }

                return $contract;
            });
        });

        $this->announceRent($contract);

        return $contract;
    }

    public function registerSale(User $by, Unit $unit, User $buyer, int $price, CarbonInterface $soldAt, ?UploadedFile $file = null): Contract
    {
        if (! $this->isMember($buyer->id, 'unit_owner')) {
            throw new DomainException('خریدار باید ابتدا با نقش «مالک واحد» به‌عنوان عضو ساختمان ثبت شده باشد.');
        }

        $previousOwner = null;

        $contract = $this->withStoredFile($file, function (?array $meta) use ($by, $unit, $buyer, $price, $soldAt, &$previousOwner) {
            return DB::transaction(function () use ($by, $unit, $buyer, $price, $soldAt, $meta, &$previousOwner) {
                $locked = Unit::lockForUpdate()->findOrFail($unit->id);
                $current = UnitParty::where('unit_id', $locked->id)->where('relation', 'owner')->whereNull('to_date')->first();

                if ($current && (int) $current->user_id === (int) $buyer->id) {
                    throw new DomainException('این شخص هم‌اکنون مالک این واحد است.');
                }

                $previousOwner = $current ? (int) $current->user_id : null;

                if ($current) {
                    $current->update(['to_date' => $soldAt->lt($current->from_date) ? $current->from_date : $soldAt]);
                }
                Contract::where('unit_id', $locked->id)->where('type', ContractType::Sale->value)->where('status', ContractStatus::Active->value)->get()
                    ->each(fn (Contract $c) => $c->update(['status' => ContractStatus::Ended, 'ended_at' => $soldAt]));

                $contract = Contract::create([
                    'unit_id' => $locked->id, 'user_id' => $buyer->id, 'type' => ContractType::Sale,
                    'sale_price' => $price, 'signed_at' => $soldAt, 'charge_payer' => ChargePayer::Owner,
                    'status' => ContractStatus::Active, 'created_by' => $by->id,
                ]);
                UnitParty::create(['unit_id' => $locked->id, 'user_id' => $buyer->id, 'relation' => 'owner', 'contract_id' => $contract->id, 'from_date' => $soldAt]);

                $this->syncUnitStatus($locked);
                if ($meta) {
                    $this->link($contract, $meta, $by);
                }

                return $contract;
            });
        });

        $this->announceSale($contract, $previousOwner);

        return $contract;
    }

    /** پایان قرارداد اجاره (تخلیه): مستأجر از ساکنین واحد حذف و وضعیت واحد دوباره محاسبه می‌شود. */
    public function endRent(Contract $contract): void
    {
        DB::transaction(function () use ($contract) {
            $locked = Contract::lockForUpdate()->findOrFail($contract->id);
            if (! $locked->isActiveRent()) {
                throw new DomainException('فقط قرارداد اجاره‌ی فعال قابل پایان دادن است.');
            }

            $today = today();
            $locked->update(['status' => ContractStatus::Ended, 'ended_at' => $today]);
            UnitParty::where('contract_id', $locked->id)->whereNull('to_date')->get()->each(fn (UnitParty $p) => $p->update(['to_date' => $today]));
            $this->syncUnitStatus(Unit::lockForUpdate()->findOrFail($locked->unit_id));
        });

        $this->announceEnd($contract);
    }

    /** تغییر «شارژ بر عهده»؛ از دوره‌ی محاسبه‌ی بعدی اعمال می‌شود (بند ۴.۱۵.۱). */
    public function changePayer(Contract $contract, ChargePayer $payer): void
    {
        if (! $contract->isActiveRent()) {
            throw new DomainException('فقط برای قرارداد اجاره‌ی فعال می‌توان مسئول پرداخت شارژ را تغییر داد.');
        }
        $contract->update(['charge_payer' => $payer]);
    }

    /** پیوست یا جایگزینیِ فایل قرارداد. */
    public function attachFile(Contract $contract, UploadedFile $file, User $by): void
    {
        $this->withStoredFile($file, function (?array $meta) use ($contract, $by) {
            $old = FileLink::where('attachable_type', self::FILE_TYPE)->where('attachable_id', $contract->id)->get();

            DB::transaction(function () use ($contract, $by, $meta, $old) {
                $this->link($contract, $meta, $by);
                foreach ($old as $link) {
                    $link->delete();
                }
            });

            foreach ($old as $link) {
                $f = $link->file;
                if ($f && ! FileLink::where('file_id', $f->id)->exists()) {
                    Storage::disk($f->disk)->delete($f->path);
                    $f->delete();
                }
            }

            return null;
        });
    }

    private function unitLabel(Unit $unit): string
    {
        $unit->loadMissing('block');

        return $unit->block->name.' / '.Fa::digits($unit->number);
    }

    private function announceRent(Contract $contract): void
    {
        $unit = Unit::with('block')->find($contract->unit_id);
        if (! $unit) {
            return;
        }
        $this->notifier->send((int) $contract->user_id, (int) $contract->building_id, 'contract_registered', 'قرارداد اجاره‌ی شما ثبت شد',
            'قرارداد اجاره‌ی واحد '.$this->unitLabel($unit).' با اجاره‌ی ماهانه '.Fa::num($contract->monthly_rent).' تومان تا تاریخ '.Fa::date($contract->end_date, 'Y/m/d').' ثبت شد.',
            route('my.contracts'), ['level' => 'success', 'category' => 'contract', 'key' => 'contract:'.$contract->id.':registered', 'label' => 'مشاهده قرارداد']);
    }

    private function announceSale(Contract $contract, ?int $previousOwner): void
    {
        $unit = Unit::with('block')->find($contract->unit_id);
        if (! $unit) {
            return;
        }
        $label = $this->unitLabel($unit);
        $buildingId = (int) $contract->building_id;

        $this->notifier->send((int) $contract->user_id, $buildingId, 'contract_registered', 'قرارداد خرید شما ثبت شد',
            'مالکیت واحد '.$label.' با مبلغ '.Fa::num($contract->sale_price).' تومان به نام شما ثبت شد.',
            route('my.contracts'), ['level' => 'success', 'category' => 'contract', 'key' => 'contract:'.$contract->id.':registered', 'label' => 'مشاهده قرارداد']);

        if ($previousOwner && $previousOwner !== (int) $contract->user_id) {
            $this->notifier->send($previousOwner, $buildingId, 'ownership_transferred', 'مالکیت واحد منتقل شد',
                'مالکیت واحد '.$label.' در تاریخ '.Fa::date($contract->signed_at, 'Y/m/d').' به مالک جدید منتقل شد.',
                null, ['category' => 'contract', 'key' => 'contract:'.$contract->id.':transferred']);
        }
    }

    private function announceEnd(Contract $contract): void
    {
        $unit = Unit::with('block')->find($contract->unit_id);
        if (! $unit) {
            return;
        }
        $this->notifier->send((int) $contract->user_id, (int) $contract->building_id, 'contract_ended', 'قرارداد اجاره‌ی شما پایان یافت',
            'قرارداد اجاره‌ی واحد '.$this->unitLabel($unit).' پایان یافت.',
            route('my.contracts'), ['level' => 'warning', 'category' => 'contract', 'key' => 'contract:'.$contract->id.':ended', 'label' => 'مشاهده قرارداد']);
    }

    /** وضعیت واحد را از روی قراردادها و ساکنین فعلی محاسبه می‌کند («در حال تعمیر» دستی است و دست نمی‌خورد). */
    public function syncUnitStatus(Unit $unit): void
    {
        if ($unit->status === UnitStatus::Repair) {
            return;
        }

        $hasOwner = UnitParty::where('unit_id', $unit->id)->where('relation', 'owner')->whereNull('to_date')->exists();
        $status = $this->activeRent($unit->id)->exists() ? UnitStatus::Rented : ($hasOwner ? UnitStatus::Sold : UnitStatus::Vacant);

        if ($unit->status !== $status) {
            $unit->update(['status' => $status]);
        }
    }

    private function activeRent(int $unitId)
    {
        return Contract::where('unit_id', $unitId)->where('type', ContractType::Rent->value)->where('status', ContractStatus::Active->value);
    }

    private function memberQuery(string $roleKey): Builder
    {
        return DB::table('building_user_roles as bur')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->join('users as u', 'u.id', '=', 'bur.user_id')
            ->where('bur.building_id', app(CurrentBuilding::class)->id())
            ->where('r.key', $roleKey)->whereIn('bur.status', ['active', 'invited'])->whereNull('u.deleted_at');
    }

    private function isMember(int $userId, string $roleKey): bool
    {
        return $this->memberQuery($roleKey)->where('u.id', $userId)->exists();
    }

    /** فایل را قبل از تراکنش ذخیره می‌کند و اگر عملیات شکست خورد، پاکش می‌کند. */
    private function withStoredFile(?UploadedFile $upload, callable $operation): mixed
    {
        $meta = null;
        if ($upload) {
            $buildingId = app(CurrentBuilding::class)->id();
            $meta = [
                'path' => $upload->store("contracts/{$buildingId}", 'local'),
                'mime' => $upload->getMimeType(), 'size' => $upload->getSize(), 'name' => $upload->getClientOriginalName(),
                'hash' => hash_file('sha256', $upload->getRealPath()),
            ];
        }

        try {
            return $operation($meta);
        } catch (\Throwable $e) {
            if ($meta) {
                Storage::disk('local')->delete($meta['path']);
            }
            throw $e;
        }
    }

    private function link(Contract $contract, array $meta, User $by): void
    {
        $file = File::create([
            'uploader_id' => $by->id, 'building_id' => app(CurrentBuilding::class)->id(), 'disk' => 'local', 'path' => $meta['path'],
            'original_name' => $meta['name'], 'mime' => $meta['mime'], 'size' => $meta['size'], 'sha256' => $meta['hash'], 'visibility' => 'private',
        ]);
        FileLink::create(['file_id' => $file->id, 'attachable_type' => self::FILE_TYPE, 'attachable_id' => $contract->id, 'collection' => 'contract']);
    }
}
