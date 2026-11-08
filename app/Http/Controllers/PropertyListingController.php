<?php

namespace App\Http\Controllers;

use App\Models\PropertyListing;
use App\Models\PropertyListingPackageOrder;
use App\Models\SiteSetting;
use App\Services\PropertyListingQuota;
use App\Services\Payments\PaymentGatewayRegistry;
use App\Models\Unit;
use App\Models\UnitParty;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PropertyListingController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'listing_type' => ['nullable', Rule::in(['sale', 'rent', 'buy_request', 'rent_request'])],
            'property_type' => ['nullable', Rule::in(['residential', 'commercial', 'land'])],
            'province' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'neighborhood' => ['nullable', 'string', 'max:120'],
            'min_area' => ['nullable', 'numeric', 'min:1', 'max:1000000'],
            'max_area' => ['nullable', 'numeric', 'min:1', 'max:1000000'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:30'],
            'parking' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['newest', 'price_asc', 'price_desc', 'area_desc'])],
        ]);

        $listings = PropertyListing::query()->published()
            ->when(! empty($filters['q']), function ($query) use ($filters) {
                $term = trim($filters['q']);
                $query->where(function ($nested) use ($term) {
                    $nested->where('title', 'like', '%'.$term.'%')
                        ->orWhere('description', 'like', '%'.$term.'%')
                        ->orWhere('neighborhood', 'like', '%'.$term.'%');
                });
            })
            ->when(! empty($filters['listing_type']), fn ($q) => $q->where('listing_type', $filters['listing_type']))
            ->when(! empty($filters['property_type']), fn ($q) => $q->where('property_type', $filters['property_type']))
            ->when(! empty($filters['province']), fn ($q) => $q->where('province', 'like', '%'.$filters['province'].'%'))
            ->when(! empty($filters['city']), fn ($q) => $q->where('city', 'like', '%'.$filters['city'].'%'))
            ->when(! empty($filters['neighborhood']), fn ($q) => $q->where('neighborhood', 'like', '%'.$filters['neighborhood'].'%'))
            ->when(isset($filters['min_area']), fn ($q) => $q->where('area', '>=', (float) $filters['min_area']))
            ->when(isset($filters['max_area']), fn ($q) => $q->where('area', '<=', (float) $filters['max_area']))
            ->when(isset($filters['min_price']), fn ($q) => $q->whereRaw('COALESCE(monthly_rent, price, deposit) >= ?', [(int) $filters['min_price']]))
            ->when(isset($filters['max_price']), fn ($q) => $q->whereRaw('COALESCE(monthly_rent, price, deposit) <= ?', [(int) $filters['max_price']]))
            ->when(isset($filters['bedrooms']), fn ($q) => $q->where('bedrooms', '>=', (int) $filters['bedrooms']))
            ->when(! empty($filters['parking']), fn ($q) => $q->where('parking_count', '>', 0))
            ->when(($filters['sort'] ?? 'newest') === 'price_asc', fn ($q) => $q->orderByRaw('COALESCE(monthly_rent, price, deposit) IS NULL')->orderByRaw('COALESCE(monthly_rent, price, deposit) ASC'))
            ->when(($filters['sort'] ?? 'newest') === 'price_desc', fn ($q) => $q->orderByRaw('COALESCE(monthly_rent, price, deposit) DESC'))
            ->when(($filters['sort'] ?? 'newest') === 'area_desc', fn ($q) => $q->orderByDesc('area'))
            ->when(($filters['sort'] ?? 'newest') === 'newest', fn ($q) => $q->latest())
            ->paginate(12)
            ->withQueryString();

        return view('properties.index', compact('listings'));
    }

    public function show(PropertyListing $listing)
    {
        abort_unless($listing->status === 'active' && (! $listing->expires_at || $listing->expires_at->isFuture()), 404);

        $listing->loadMissing('user');

        // آگهی‌های مشابه: هم‌نوع، با اولویت همان شهر؛ فقط آگهی‌های منتشرشده.
        $similar = PropertyListing::published()
            ->whereKeyNot($listing->getKey())
            ->where('listing_type', $listing->listing_type)
            ->orderByRaw('CASE WHEN city = ? THEN 0 ELSE 1 END', [(string) $listing->city])
            ->latest()
            ->limit(4)
            ->get();

        return view('properties.show', compact('listing', 'similar'));
    }

    public function mine(Request $request, PropertyListingQuota $quota, PaymentGatewayRegistry $gateways)
    {
        $listings = PropertyListing::where('user_id', $request->user()->id)->latest()->paginate(15);
        $packages = collect(SiteSetting::get('property_listings.packages', []))->filter(fn ($package) => is_array($package) && ! empty($package['active']) && (int) ($package['price'] ?? 0) > 0)->values();
        $availableCredits = $quota->availableCredits($request->user()->id);
        $paymentGateways = $gateways->active();
        $packageOrders = PropertyListingPackageOrder::where('user_id', $request->user()->id)->latest()->limit(8)->get();

        return view('properties.mine', compact('listings', 'packages', 'availableCredits', 'paymentGateways', 'packageOrders'));
    }

    public function create(Request $request)
    {
        $units = $this->availableUnits($request);
        return view('properties.form', [
            'listing' => new PropertyListing(),
            'units' => $units,
            'unitAutofillData' => $this->unitAutofillData($units),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->authorizeUnitSelection($request, isset($data['unit_id']) ? (int) $data['unit_id'] : null);
        $data['user_id'] = $request->user()->id;
        $data['status'] = 'pending';

        $listing = DB::transaction(function () use ($request, $data) {
            $consumedCredit = app(PropertyListingQuota::class)->consumeForNewListing($request->user());
            $data['photos'] = $this->storePhotos($request);
            $listing = PropertyListing::create($data);

            if ($consumedCredit) {
                $consumedCredit->update(['property_listing_id' => $listing->id]);
            }

            return $listing;
        });

        return redirect()->route('properties.mine')->with('status', 'آگهی شما ثبت شد و پس از تأیید مدیریت در بازارچه نمایش داده می‌شود.');
    }

    public function edit(Request $request, PropertyListing $listing)
    {
        $this->authorizeOwner($request, $listing);

        $units = $this->availableUnits($request);
        return view('properties.form', [
            'listing' => $listing,
            'units' => $units,
            'unitAutofillData' => $this->unitAutofillData($units),
        ]);
    }

    public function update(Request $request, PropertyListing $listing)
    {
        $this->authorizeOwner($request, $listing);
        $data = $this->validated($request, $listing);
        $this->authorizeUnitSelection($request, isset($data['unit_id']) ? (int) $data['unit_id'] : null);
        $keptPhotos = array_values(array_intersect((array) $request->input('existing_photos', []), $listing->photos ?? []));
        $newUploads = $request->file('photos', []);
        if (count($keptPhotos) + count($newUploads) > 12) {
            abort(422, 'حداکثر ۱۲ تصویر برای هر آگهی مجاز است.');
        }
        $removedPhotos = array_diff($listing->photos ?? [], $keptPhotos);
        foreach ($removedPhotos as $photo) {
            Storage::disk('public')->delete($photo);
        }
        $data['photos'] = array_values(array_merge($keptPhotos, $this->storePhotos($request)));
        $data['status'] = $listing->status;
        $listing->update($data);

        return redirect()->route('properties.mine')->with('status', 'آگهی ویرایش شد.');
    }

    public function destroy(Request $request, PropertyListing $listing)
    {
        $this->authorizeOwner($request, $listing);
        foreach ($listing->photos ?? [] as $photo) {
            Storage::disk('public')->delete($photo);
        }
        $listing->delete();

        return redirect()->route('properties.mine')->with('status', 'آگهی حذف شد.');
    }

    public function close(Request $request, PropertyListing $listing)
    {
        $this->authorizeOwner($request, $listing);
        $listing->update(['status' => 'closed']);

        return back()->with('status', 'آگهی غیرفعال شد.');
    }

    private function authorizeOwner(Request $request, PropertyListing $listing): void
    {
        abort_unless((int) $listing->user_id === (int) $request->user()->id, 403);
    }

    private function validated(Request $request, ?PropertyListing $listing = null): array
    {
        $data = $request->validate([
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')],
            'listing_type' => ['required', Rule::in(['sale', 'rent', 'buy_request', 'rent_request'])],
            'property_type' => ['required', Rule::in(['residential', 'commercial', 'land'])],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'province' => ['nullable', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'neighborhood' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'hide_address' => ['sometimes', 'boolean'],
            'allow_phone_call' => ['sometimes', 'boolean'],
            'allow_sms' => ['sometimes', 'boolean'],
            'area' => ['nullable', 'numeric', 'min:1', 'max:1000000'],
            'parking_count' => ['nullable', 'integer', 'min:0', 'max:30'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:30'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'floor' => ['nullable', 'integer', 'min:-5', 'max:300'],
            'total_floors' => ['nullable', 'integer', 'min:1', 'max:300'],
            'built_year' => ['nullable', 'integer', 'min:1300', 'max:1500'],
            'document_type' => ['nullable', 'string', 'max:60'],
            'amenities' => ['nullable', 'array', 'max:30'],
            'amenities.*' => ['string', 'max:60'],
            'price' => ['nullable', 'integer', 'min:0'],
            'deposit' => ['nullable', 'integer', 'min:0'],
            'monthly_rent' => ['nullable', 'integer', 'min:0'],
            'allow_exchange' => ['sometimes', 'boolean'],
            'photos' => ['sometimes', 'array', 'max:12'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $type = $data['listing_type'];
        if ($type === 'sale' && empty($data['price'])) {
            abort(422, 'برای آگهی فروش، قیمت را وارد کنید.');
        }
        if ($type === 'rent' && empty($data['deposit']) && empty($data['monthly_rent'])) {
            abort(422, 'برای آگهی اجاره، ودیعه یا اجاره ماهانه را وارد کنید.');
        }

        unset($data['photos']);
        $data['hide_address'] = $request->boolean('hide_address');
        $data['allow_phone_call'] = $request->boolean('allow_phone_call');
        $data['allow_sms'] = $request->boolean('allow_sms');
        $data['allow_exchange'] = $request->boolean('allow_exchange');

        return $data;
    }


    /**
     * Building owners/managers can choose every unit in their building.
     * Unit owners can choose only units linked to them in active unit parties.
     * Creating a listing itself remains independent from building membership.
     */
    private function availableUnits(Request $request): Collection
    {
        $memberships = DB::table('building_user_roles as bur')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->where('bur.user_id', $request->user()->id)
            ->where('bur.status', 'active')
            ->get(['bur.building_id', 'r.key']);

        $managedBuildingIds = $memberships
            ->whereIn('key', ['owner', 'manager'])
            ->pluck('building_id')->unique()->values()->all();

        $unitOwnerBuildingIds = $memberships
            ->where('key', 'unit_owner')
            ->pluck('building_id')->unique()->values()->all();

        $ownedUnitIds = $unitOwnerBuildingIds === [] ? [] : UnitParty::withoutGlobalScopes()
            ->where('user_id', $request->user()->id)
            ->whereNull('to_date')
            ->where(fn ($query) => $query->whereNull('from_date')->orWhereDate('from_date', '<=', today()))
            ->whereIn('building_id', $unitOwnerBuildingIds)
            ->pluck('unit_id')->unique()->values()->all();

        if ($managedBuildingIds === [] && $ownedUnitIds === []) {
            return collect();
        }

        return Unit::withoutGlobalScopes()
            ->with([
                'building.city',
                'building.province',
                'block' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->whereNull('deleted_at')
            ->where(function ($query) use ($managedBuildingIds, $ownedUnitIds) {
                if ($managedBuildingIds !== []) {
                    $query->whereIn('building_id', $managedBuildingIds);
                }
                if ($ownedUnitIds !== []) {
                    $method = $managedBuildingIds === [] ? 'whereIn' : 'orWhereIn';
                    $query->{$method}('id', $ownedUnitIds);
                }
            })
            ->orderBy('building_id')->orderBy('id')->get();
    }

    private function authorizeUnitSelection(Request $request, ?int $unitId): void
    {
        if (! $unitId) {
            return;
        }

        abort_unless(
            $this->availableUnits($request)->contains(fn (Unit $unit) => (int) $unit->id === $unitId),
            403,
            'شما به اطلاعات این واحد دسترسی ندارید.'
        );
    }

    private function unitAutofillData(Collection $units): array
    {
        return $units->mapWithKeys(function (Unit $unit) {
            $building = $unit->building;

            return [(string) $unit->id => [
                'area' => $unit->area_m2,
                'floor' => $unit->getAttribute('floor') ?? $unit->getAttribute('floor_number'),
                'bedrooms' => $unit->getAttribute('bedrooms'),
                'bathrooms' => $unit->getAttribute('bathrooms'),
                'province' => $building?->province?->name,
                'city' => $building?->city?->name,
                'parking_count' => DB::table('parking_assignments')
                    ->where('unit_id', $unit->id)
                    ->whereNull('released_at')
                    ->count(),
            ]];
        })->all();
    }

    private function storePhotos(Request $request): array
    {
        $paths = [];
        foreach ($request->file('photos', []) as $photo) {
            $paths[] = $photo->store('property-listings/'.now()->format('Y/m'), 'public');
        }

        return $paths;
    }
}
