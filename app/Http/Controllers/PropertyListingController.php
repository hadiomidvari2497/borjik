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
use Illuminate\Validation\ValidationException;
use App\Support\MobileNumber;

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

        $activeFilters = array_values(array_filter([
            $request->filled('q') ? '«'.\Illuminate\Support\Str::limit($request->input('q'), 18).'»' : null,
            ! empty($filters['listing_type']) ? (PropertyListing::LISTING_TYPES[$filters['listing_type']] ?? null) : null,
            $request->filled('province') ? $request->input('province') : null,
            $request->filled('city') ? $request->input('city') : null,
            $request->filled('neighborhood') ? $request->input('neighborhood') : null,
            ! empty($filters['property_type']) ? (PropertyListing::PROPERTY_TYPES[$filters['property_type']] ?? null) : null,
            $request->filled('min_area') ? 'از '.\App\Support\Fa::digits($request->input('min_area')).' متر' : null,
            $request->filled('max_area') ? 'تا '.\App\Support\Fa::digits($request->input('max_area')).' متر' : null,
            $request->filled('min_price') ? 'از '.\App\Support\Fa::short((int) $request->input('min_price')) : null,
            $request->filled('max_price') ? 'تا '.\App\Support\Fa::short((int) $request->input('max_price')) : null,
            $request->filled('bedrooms') ? 'حداقل '.\App\Support\Fa::digits($request->input('bedrooms')).' خواب' : null,
            $request->boolean('parking') ? 'پارکینگ‌دار' : null,
        ]));

        return view('properties.index', [
            'listings' => $listings,
            'activeFilters' => $activeFilters,
            'mapApiKey' => config('services.neshan.map_key'),
        ]);
    }

    /**
     * Public map search. Coordinates are offset unless the listing owner explicitly
     * opted into showing the exact location.
     */
    public function mapSearch(Request $request)
    {
        $bounds = $request->validate([
            'south' => ['nullable', 'numeric', 'between:-85,85'],
            'west' => ['nullable', 'numeric', 'between:-180,180'],
            'north' => ['nullable', 'numeric', 'between:-85,85'],
            'east' => ['nullable', 'numeric', 'between:-180,180'],
            'q' => ['nullable', 'string', 'max:120'],
            'listing_type' => ['nullable', Rule::in(['sale', 'rent', 'buy_request', 'rent_request'])],
            'property_type' => ['nullable', Rule::in(['residential', 'commercial', 'land'])],
            'province' => ['nullable', 'string', 'max:100'], 'city' => ['nullable', 'string', 'max:100'],
            'neighborhood' => ['nullable', 'string', 'max:120'], 'min_area' => ['nullable', 'numeric', 'min:1', 'max:1000000'],
            'max_area' => ['nullable', 'numeric', 'min:1', 'max:1000000'], 'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'], 'bedrooms' => ['nullable', 'integer', 'min:0', 'max:30'],
            'parking' => ['nullable', 'boolean'],
        ]);
        $keys = ['south', 'west', 'north', 'east'];
        $hasAnyBounds = collect($keys)->contains(fn ($key) => isset($bounds[$key]));
        $hasBounds = collect($keys)->every(fn ($key) => isset($bounds[$key]));
        abort_if($hasAnyBounds && ! $hasBounds, 422, 'محدوده نقشه ناقص است.');
        abort_if($hasBounds && ($bounds['south'] >= $bounds['north'] || $bounds['west'] >= $bounds['east']), 422, 'محدوده نقشه معتبر نیست.');

        $query = PropertyListing::query()->published()->whereNotNull('latitude')->whereNotNull('longitude')
            ->when($hasBounds, fn ($q) => $q->whereBetween('latitude', [$bounds['south'] - 0.015, $bounds['north'] + 0.015])->whereBetween('longitude', [$bounds['west'] - 0.015, $bounds['east'] + 0.015]))
            ->when(! empty($bounds['q']), function ($q) use ($bounds) {
                $term = trim($bounds['q']);
                $q->where(fn ($nested) => $nested->where('title', 'like', '%'.$term.'%')->orWhere('description', 'like', '%'.$term.'%')->orWhere('neighborhood', 'like', '%'.$term.'%'));
            })
            ->when(! empty($bounds['listing_type']), fn ($q) => $q->where('listing_type', $bounds['listing_type']))
            ->when(! empty($bounds['property_type']), fn ($q) => $q->where('property_type', $bounds['property_type']))
            ->when(! empty($bounds['province']), fn ($q) => $q->where('province', 'like', '%'.$bounds['province'].'%'))
            ->when(! empty($bounds['city']), fn ($q) => $q->where('city', 'like', '%'.$bounds['city'].'%'))
            ->when(! empty($bounds['neighborhood']), fn ($q) => $q->where('neighborhood', 'like', '%'.$bounds['neighborhood'].'%'))
            ->when(isset($bounds['min_area']), fn ($q) => $q->where('area', '>=', (float) $bounds['min_area']))
            ->when(isset($bounds['max_area']), fn ($q) => $q->where('area', '<=', (float) $bounds['max_area']))
            ->when(isset($bounds['min_price']), fn ($q) => $q->whereRaw('COALESCE(monthly_rent, price, deposit) >= ?', [(int) $bounds['min_price']]))
            ->when(isset($bounds['max_price']), fn ($q) => $q->whereRaw('COALESCE(monthly_rent, price, deposit) <= ?', [(int) $bounds['max_price']]))
            ->when(isset($bounds['bedrooms']), fn ($q) => $q->where('bedrooms', '>=', (int) $bounds['bedrooms']))
            ->when(! empty($bounds['parking']), fn ($q) => $q->where('parking_count', '>', 0))->latest()->limit(600);

        $points = $query->get(['id', 'title', 'listing_type', 'price', 'deposit', 'monthly_rent', 'latitude', 'longitude', 'location_visibility'])
            ->map(function (PropertyListing $listing) {
                $amount = $listing->listing_type === 'rent' ? ($listing->monthly_rent ?: $listing->deposit) : ($listing->price ?: $listing->deposit);
                return array_merge(['id' => $listing->id, 'title' => $listing->title, 'listing_type' => $listing->listing_type, 'price' => $amount ? \App\Support\Fa::short((int) $amount) : 'توافقی', 'url' => route('properties.show', $listing), 'location_visibility' => $listing->location_visibility ?: 'approximate'], $this->publicMapPoint($listing));
            })
            ->filter(fn (array $point) => ! $hasBounds || ($point['latitude'] >= $bounds['south'] && $point['latitude'] <= $bounds['north'] && $point['longitude'] >= $bounds['west'] && $point['longitude'] <= $bounds['east']))
            ->values();
        return response()->json(['data' => $points]);
    }

    private function publicMapPoint(PropertyListing $listing): array
    {
        if ($listing->location_visibility === 'exact') {
            return ['latitude' => (float) $listing->latitude, 'longitude' => (float) $listing->longitude];
        }

        $seed = hash('sha256', (string) $listing->id);
        return [
            'latitude' => round((float) $listing->latitude + ((hexdec(substr($seed, 0, 4)) / 65535) - 0.5) * 0.006, 4),
            'longitude' => round((float) $listing->longitude + ((hexdec(substr($seed, 4, 4)) / 65535) - 0.5) * 0.006, 4),
        ];
    }

    public function show(PropertyListing $listing)
    {
        abort_unless($listing->status === 'active' && (! $listing->expires_at || $listing->expires_at->isFuture()), 404);
        $listing->loadMissing('user');
        if (! auth()->check() || (int) auth()->id() !== (int) $listing->user_id) {
            DB::table('property_listings')->where('id', $listing->id)->increment('views_count');
            DB::table('property_listing_daily_stats')->insertOrIgnore([
                'property_listing_id' => $listing->id,
                'stat_date' => now()->toDateString(),
                'views_count' => 0,
                'phone_clicks_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('property_listing_daily_stats')->where('property_listing_id', $listing->id)
                ->where('stat_date', now()->toDateString())->increment('views_count');
        }
        $mapPoint = $listing->latitude !== null && $listing->longitude !== null ? $this->publicMapPoint($listing) : null;
        $mapApiKey = config('services.neshan.map_key');
        $similar = PropertyListing::published()->whereKeyNot($listing->getKey())->where('listing_type', $listing->listing_type)
            ->orderByRaw('CASE WHEN city = ? THEN 0 ELSE 1 END', [(string) $listing->city])->latest()->limit(4)->get();
        return view('properties.show', compact('listing', 'similar', 'mapPoint', 'mapApiKey'));
    }

    public function recordPhoneClick(Request $request, PropertyListing $listing)
    {
        abort_unless($listing->status === 'active' && (! $listing->expires_at || $listing->expires_at->isFuture()), 404);
        abort_if((int) $request->user()?->id === (int) $listing->user_id, 403);

        $sessionKey = 'property-phone-click-'.$listing->id.'-'.now()->toDateString();
        if (! $request->session()->has($sessionKey)) {
            DB::table('property_listing_daily_stats')->insertOrIgnore([
                'property_listing_id' => $listing->id,
                'stat_date' => now()->toDateString(),
                'views_count' => 0,
                'phone_clicks_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('property_listing_daily_stats')->where('property_listing_id', $listing->id)
                ->where('stat_date', now()->toDateString())->increment('phone_clicks_count');
            $request->session()->put($sessionKey, true);
        }

        return response()->json(['ok' => true]);
    }

    public function mine(Request $request, PropertyListingQuota $quota, PaymentGatewayRegistry $gateways)
    {
        $listings = PropertyListing::where('user_id', $request->user()->id)->withCount('inquiries')->latest()->paginate(15);
        $listingIds = PropertyListing::where('user_id', $request->user()->id)->pluck('id');
        $phoneClicksByListing = DB::table('property_listing_daily_stats')
            ->whereIn('property_listing_id', $listingIds)
            ->selectRaw('property_listing_id, SUM(phone_clicks_count) as clicks')
            ->groupBy('property_listing_id')->pluck('clicks', 'property_listing_id');
        $listings->getCollection()->each(function (PropertyListing $listing) use ($phoneClicksByListing) {
            $listing->setAttribute('phone_clicks_count', (int) ($phoneClicksByListing[$listing->id] ?? 0));
        });
        $dailyStats = DB::table('property_listing_daily_stats')
            ->whereIn('property_listing_id', $listingIds)
            ->where('stat_date', '>=', now()->subDays(13)->toDateString())
            ->selectRaw('stat_date, SUM(views_count) as views, SUM(phone_clicks_count) as phone_clicks')
            ->groupBy('stat_date')->orderBy('stat_date')->get();
        $performance = [
            'views' => (int) PropertyListing::where('user_id', $request->user()->id)->sum('views_count'),
            'phone_clicks' => (int) DB::table('property_listing_daily_stats')->whereIn('property_listing_id', $listingIds)->sum('phone_clicks_count'),
            'inquiries' => (int) DB::table('property_listing_inquiries')->whereIn('property_listing_id', $listingIds)->count(),
        ];
        $packages = collect(SiteSetting::get('property_listings.packages', []))->filter(fn ($package) => is_array($package) && ! empty($package['active']) && (int) ($package['price'] ?? 0) > 0)->values();
        $availableCredits = $quota->availableCredits($request->user()->id);
        $paymentGateways = $gateways->active();
        $packageOrders = PropertyListingPackageOrder::where('user_id', $request->user()->id)->latest()->limit(8)->get();

        return view('properties.mine', compact('listings', 'packages', 'availableCredits', 'paymentGateways', 'packageOrders', 'dailyStats', 'performance'));
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
            throw ValidationException::withMessages(['photos' => 'حداکثر ۱۲ تصویر برای هر آگهی مجاز است.']);
        }
        $removedPhotos = array_diff($listing->photos ?? [], $keptPhotos);
        foreach ($removedPhotos as $photo) {
            Storage::disk('public')->delete($photo);
        }
        $data['photos'] = $this->orderPhotos($request, $keptPhotos, $this->storePhotos($request));
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

    public function renew(Request $request, PropertyListing $listing, PropertyListingQuota $quota)
    {
        $this->authorizeOwner($request, $listing);

        $durationDays = max(1, min(365, (int) SiteSetting::get('property_listings.duration_days', 30)));
        DB::transaction(function () use ($request, $listing, $quota, $durationDays) {
            $locked = PropertyListing::query()->whereKey($listing->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, ['active', 'expired'], true), 422, 'فقط آگهی فعال یا منقضی‌شده قابل تمدید است.');

            $quota->consumeForRenewal($request->user(), $locked);
            $baseDate = $locked->expires_at && $locked->expires_at->isFuture()
                ? $locked->expires_at->copy()
                : now();

            $locked->update([
                'status' => 'active',
                'expires_at' => $baseDate->addDays($durationDays),
            ]);
        });

        return redirect()->route('properties.mine')->with('status', 'آگهی برای '.$durationDays.' روز دیگر تمدید شد.');
    }

    private function authorizeOwner(Request $request, PropertyListing $listing): void
    {
        abort_unless((int) $listing->user_id === (int) $request->user()->id, 403);
    }

    /** ارقام فارسی/عربی را لاتین و جداکننده‌ی هزارگان را از فیلدهای عددی حذف می‌کند. */
    private function normalizeNumericInput(Request $request): void
    {
        $merge = [];
        foreach (['price', 'deposit', 'monthly_rent', 'parking_count', 'bedrooms', 'bathrooms', 'floor', 'total_floors', 'built_year', 'area'] as $key) {
            if (! $request->has($key) || ! is_string($request->input($key))) {
                continue;
            }
            $value = MobileNumber::toLatinDigits(trim($request->input($key)));
            $value = str_replace(['٬', '،', ',', ' ', "\u{00A0}", '٫'], ['', '', '', '', '', '.'], $value);
            $merge[$key] = $value === '' ? null : $value;
        }
        $request->merge($merge);
    }

    private function validated(Request $request, ?PropertyListing $listing = null): array
    {
        $request->merge(['location_visibility' => $request->input('location_visibility', $listing?->location_visibility ?? 'approximate')]);
        $this->normalizeNumericInput($request);

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
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'location_visibility' => ['required', Rule::in(['approximate', 'exact'])],
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
        ], [
            'required' => 'این فیلد را پر کنید.',
            'integer' => 'فقط عدد صحیح وارد کنید (مثلاً ۲).',
            'numeric' => 'فقط عدد وارد کنید.',
            'min.numeric' => 'مقدار واردشده کمتر از حد مجاز است.',
            'max.numeric' => 'مقدار واردشده بیشتر از حد مجاز است.',
            'min.integer' => 'مقدار واردشده کمتر از حد مجاز است.',
            'max.integer' => 'مقدار واردشده بیشتر از حد مجاز است.',
            'max.string' => 'متن واردشده بیش از حد طولانی است.',
            'in' => 'گزینه انتخاب‌شده معتبر نیست.',
            'exists' => 'واحد انتخاب‌شده معتبر نیست.',
            'built_year.min' => 'سال ساخت شمسی باید بین ۱۳۰۰ و ۱۵۰۰ باشد.',
            'built_year.max' => 'سال ساخت شمسی باید بین ۱۳۰۰ و ۱۵۰۰ باشد.',
            'photos.max' => 'حداکثر ۱۲ تصویر می‌توانید انتخاب کنید.',
            'photos.*.image' => 'فایل انتخاب‌شده تصویر معتبر نیست.',
            'photos.*.mimes' => 'فقط تصویر JPG، PNG یا WebP مجاز است.',
            'photos.*.max' => 'حجم هر تصویر باید حداکثر ۵ مگابایت باشد.',
            'photos.*.uploaded' => 'بارگذاری تصویر ناموفق بود؛ حجم یا فرمت فایل را بررسی کنید.',
        ]);

        $type = $data['listing_type'];
        if ($type === 'sale' && empty($data['price'])) {
            throw ValidationException::withMessages(['price' => 'برای آگهی فروش، قیمت را وارد کنید.']);
        }
        if ($type === 'rent' && empty($data['deposit']) && empty($data['monthly_rent'])) {
            throw ValidationException::withMessages([
                'deposit' => 'برای آگهی اجاره، ودیعه یا اجاره ماهانه را وارد کنید.',
                'monthly_rent' => 'برای آگهی اجاره، ودیعه یا اجاره ماهانه را وارد کنید.',
            ]);
        }

        // فقط فیلدهای قیمتِ مرتبط با نوع آگهی ذخیره می‌شود.
        if (in_array($type, ['sale', 'buy_request'], true)) {
            $data['deposit'] = null;
            $data['monthly_rent'] = null;
        } else {
            $data['price'] = null;
        }

        unset($data['photos']);
        $data['latitude'] = $request->filled('latitude') ? (float) $request->input('latitude') : null;
        $data['longitude'] = $request->filled('longitude') ? (float) $request->input('longitude') : null;
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

    /**
     * عکس اصلی همیشه اولین عکس آرایه است. فرم، انتخاب کاربر را در cover_photo می‌فرستد:
     * «existing:<مسیر>» برای عکس فعلی، یا «new» برای اولین فایلِ تازه‌آپلودشده.
     */
    private function orderPhotos(Request $request, array $kept, array $uploaded): array
    {
        $cover = (string) $request->input('cover_photo', '');

        if (str_starts_with($cover, 'existing:')) {
            $path = substr($cover, 9);
            if (in_array($path, $kept, true)) {
                $kept = array_values(array_diff($kept, [$path]));
                array_unshift($kept, $path);
            }

            return array_values(array_merge($kept, $uploaded));
        }

        if ($cover === 'new' && $uploaded !== []) {
            return array_values(array_merge($uploaded, $kept));
        }

        return array_values(array_merge($kept, $uploaded));
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
