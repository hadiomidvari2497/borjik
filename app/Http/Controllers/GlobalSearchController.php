<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\UnitParty;
use App\Models\User;
use App\Support\CurrentBuilding;
use App\Support\MobileNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request, CurrentBuilding $current): JsonResponse
    {
        $buildingId = $current->id();
        abort_unless($buildingId, 403);

        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:80']]);
        $term = trim(MobileNumber::toLatinDigits($data['q']));
        $like = '%'.$term.'%';
        $user = $request->user();
        $results = collect();

        $add = static function (string $group, string $title, string $subtitle, string $url, string $icon) use ($results): void {
            $results->push(compact('group', 'title', 'subtitle', 'url', 'icon'));
        };

        // واحدها: مدیران دارای مجوز، همه واحدهای همین ساختمان را می‌بینند؛ ساکن فقط واحدهای مرتبط خودش را.
        if ($user->hasPermission('unit.view')) {
            Unit::query()->where('building_id', $buildingId)->with('block')
                ->where(function ($query) use ($like) {
                    $query->where('number', 'like', $like)
                        ->orWhereHas('block', fn ($block) => $block->where('number', 'like', $like));
                })->limit(5)->get()->each(function (Unit $unit) use ($add) {
                    $block = $unit->block?->number ? 'بلوک '.$unit->block->number : 'واحد';
                    $add('واحدها', 'واحد '.$unit->number, $block, route('units.index', ['q' => $unit->number]), 'unit');
                });
        } else {
            Unit::query()->where('units.building_id', $buildingId)
                ->whereIn('units.id', UnitParty::query()->where('building_id', $buildingId)
                    ->where('user_id', $user->id)->whereNull('to_date')->select('unit_id'))
                ->with('block')
                ->where('number', 'like', $like)->limit(5)->get()
                ->each(function (Unit $unit) use ($add) {
                    $add('واحدهای من', 'واحد '.$unit->number, 'واحد مرتبط با حساب شما', route('my.units'), 'unit');
                });
        }

        // افراد فقط برای نقش‌هایی که مجوز مدیریت اعضا یا پرسنل دارند قابل جست‌وجو هستند.
        if ($user->hasPermission('member.manage') || $user->hasPermission('staff.manage')) {
            User::query()->whereNull('users.deleted_at')
                ->whereHas('buildingRoles', fn ($roles) => $roles->where('building_id', $buildingId)->where('status', 'active'))
                ->where(function ($query) use ($like) {
                    $query->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)
                        ->orWhere('company_name', 'like', $like)->orWhere('mobile', 'like', $like);
                })->orderBy('first_name')->limit(6)->get()
                ->each(function (User $person) use ($add, $user) {
                    $name = $person->display_name ?: $person->mobile;
                    $page = $user->hasPermission('staff.manage') ? route('staff.index') : route('members.index');
                    $add('اعضا و پرسنل', $name, 'عضو فعال ساختمان', $page, 'user');
                });
        }

        // درخواست‌ها: مدیر همه، مسئول فقط موارد ارجاع‌شده و ساکن فقط درخواست‌های خودش را می‌بیند.
        if ($user->hasPermission('request.view.all') || $user->hasPermission('request.view.domain') || $user->hasPermission('request.view.own')) {
            Ticket::query()->where('building_id', $buildingId)->where('kind', 'request')
                ->where(function ($query) use ($like) {
                    $query->where('title', 'like', $like)->orWhere('number', 'like', $like)
                        ->orWhere('description', 'like', $like);
                })
                ->when(! $user->hasPermission('request.view.all') && $user->hasPermission('request.view.domain'),
                    fn ($query) => $query->whereHas('assignees', fn ($assignees) => $assignees->where('user_id', $user->id)))
                ->when(! $user->hasPermission('request.view.all') && ! $user->hasPermission('request.view.domain'),
                    fn ($query) => $query->where('created_by', $user->id))
                ->latest()->limit(6)->get()->each(function (Ticket $ticket) use ($add, $user) {
                    $own = (int) $ticket->created_by === (int) $user->id;
                    $url = $own && ! $user->hasPermission('request.view.domain')
                        ? route('my.requests.show', $ticket->id)
                        : route('requests.show', $ticket->id);
                    $add('درخواست‌ها', $ticket->title, 'شماره '.$ticket->number, $url, 'request');
                });
        }

        // قراردادها: مدیر دارای مجوز، همه قراردادهای ساختمان؛ عضو فقط قراردادهای مرتبط با خودش.
        if ($user->hasPermission('contract.manage') || $user->hasPermission('contract.view.own')) {
            $ownedUnitIds = UnitParty::query()->where('building_id', $buildingId)->where('user_id', $user->id)
                ->where('relation', 'owner')->whereNull('to_date')->select('unit_id');
            Contract::query()->where('building_id', $buildingId)->with(['unit', 'user'])
                ->when(! $user->hasPermission('contract.manage'), function ($query) use ($user, $ownedUnitIds) {
                    $query->where(function ($scope) use ($user, $ownedUnitIds) {
                        $scope->where('user_id', $user->id)
                            ->orWhere(fn ($owned) => $owned->where('type', 'rent')->whereIn('unit_id', $ownedUnitIds));
                    });
                })
                ->where(function ($query) use ($like) {
                    $query->whereHas('unit', fn ($unit) => $unit->where('number', 'like', $like))
                        ->orWhereHas('user', fn ($person) => $person->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)->orWhere('company_name', 'like', $like));
                })->latest()->limit(5)->get()->each(function (Contract $contract) use ($add, $user) {
                    $unitNumber = $contract->unit?->number ?? '—';
                    $person = $contract->user?->display_name ?? 'طرف قرارداد';
                    $url = $user->hasPermission('contract.manage') ? route('contracts.show', $contract->id) : route('my.contracts');
                    $add('قراردادها', 'قرارداد واحد '.$unitNumber, $person, $url, 'contract');
                });
        }

        // صورت‌حساب: مسئول مالی مجاز به جست‌وجوی ساختمان؛ ساکن فقط صورت‌حساب‌های پرداختی خودش.
        if ($user->hasPermission('payment.review') || $user->hasPermission('charge.calculate') || $user->hasPermission('invoice.view.own')) {
            Invoice::query()->where('building_id', $buildingId)->with('unit')
                ->when(! $user->hasPermission('payment.review') && ! $user->hasPermission('charge.calculate'),
                    fn ($query) => $query->where('payer_user_id', $user->id))
                ->whereHas('unit', fn ($unit) => $unit->where('number', 'like', $like))
                ->latest()->limit(5)->get()->each(function (Invoice $invoice) use ($add, $user) {
                    $url = $user->hasPermission('payment.review') || $user->hasPermission('charge.calculate')
                        ? route('payments.index') : route('my.invoices.show', $invoice->id);
                    $add('صورت‌حساب‌ها', 'صورت‌حساب واحد '.$invoice->unit?->number, 'مبلغ '.number_format((int) $invoice->amount).' ریال', $url, 'invoice');
                });
        }

        // اسناد مطابق مجوز مشاهده و محدودیت نقش تعریف‌شده روی هر سند.
        if ($user->hasPermission('document.view')) {
            Document::query()->where('building_id', $buildingId)
                ->where(function ($query) use ($current) {
                    $query->whereDoesntHave('roles')
                        ->orWhereHas('roles', fn ($roles) => $roles->whereIn('roles.key', $current->roles()));
                })
                ->where(function ($query) use ($like) {
                    $query->where('title', 'like', $like)->orWhere('description', 'like', $like);
                })->latest()->limit(5)->get()->each(function (Document $document) use ($add) {
                    $add('اسناد', $document->title, 'بایگانی اسناد ساختمان', route('documents.download', $document->id), 'document');
                });
        }

        return response()->json([
            'results' => $results->take(24)->values(),
            'query' => $term,
        ]);
    }
}
