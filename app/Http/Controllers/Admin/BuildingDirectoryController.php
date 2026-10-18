<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BuildingDirectoryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'subscription' => ['nullable', Rule::in(['active', 'expired', 'none'])],
            'type' => ['nullable', Rule::in(['residential', 'commercial', 'office', 'educational', 'mixed', 'other'])],
        ]);

        $q = trim((string) ($filters['q'] ?? ''));
        $like = '%'.addcslashes($q, '%_\\\\').'%';
        $query = Building::query()
            ->with([
                'createdBy:id,first_name,last_name,company_name,mobile',
                'subscription.plan:id,name',
                'province:id,name',
                'city:id,name',
            ])
            ->withCount('units')
            ->when($q !== '', fn ($query) => $query->where(function ($search) use ($like) {
                $search->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhere('address', 'like', $like)
                    ->orWhereHas('createdBy', fn ($owner) => $owner->where('mobile', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('company_name', 'like', $like));
            }))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['type']), fn ($query) => $query->where('type', $filters['type']))
            ->when(($filters['subscription'] ?? '') === 'none', fn ($query) => $query->whereNull('subscription_id'))
            ->when(($filters['subscription'] ?? '') === 'active', fn ($query) => $query->whereHas('subscription', fn ($s) => $s->where('status', 'active')->where('expires_at', '>', now())))
            ->when(($filters['subscription'] ?? '') === 'expired', fn ($query) => $query->whereNotNull('subscription_id')->whereHas('subscription', fn ($s) => $s->where('status', '!=', 'active')->orWhereNull('expires_at')->orWhere('expires_at', '<=', now())))
            ->orderByDesc('created_at');

        return view('admin.buildings.index', [
            'buildings' => $query->paginate(20)->withQueryString(),
            'filters' => array_merge(['q' => '', 'status' => '', 'subscription' => '', 'type' => ''], $filters),
            'stats' => [
                'total' => Building::query()->count(),
                'active' => Building::query()->where('status', 'active')->count(),
                'units' => Unit::query()->count(),
                'subscribed' => Building::query()->whereHas('subscription', fn ($s) => $s->where('status', 'active')->where('expires_at', '>', now()))->count(),
            ],
            'typeLabels' => [
                'residential' => 'مسکونی',
                'commercial' => 'تجاری',
                'office' => 'اداری',
                'educational' => 'آموزشی',
                'mixed' => 'مختلط',
                'other' => 'سایر',
            ],
        ]);
    }
}
