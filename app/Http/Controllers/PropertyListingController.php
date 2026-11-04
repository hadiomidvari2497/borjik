<?php

namespace App\Http\Controllers;

use App\Models\PropertyListing;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PropertyListingController extends Controller
{
    public function index(Request $request)
    {
        $listings = PropertyListing::query()->published()
            ->when($request->filled('listing_type'), fn ($q) => $q->where('listing_type', (string) $request->input('listing_type')))
            ->when($request->filled('property_type'), fn ($q) => $q->where('property_type', (string) $request->input('property_type')))
            ->when($request->filled('city'), fn ($q) => $q->where('city', 'like', '%'.$request->input('city').'%'))
            ->when($request->filled('neighborhood'), fn ($q) => $q->where('neighborhood', 'like', '%'.$request->input('neighborhood').'%'))
            ->when($request->filled('min_area'), fn ($q) => $q->where('area', '>=', (float) $request->input('min_area')))
            ->when($request->filled('max_area'), fn ($q) => $q->where('area', '<=', (float) $request->input('max_area')))
            ->when($request->filled('min_price'), fn ($q) => $q->where('price', '>=', (int) $request->input('min_price')))
            ->when($request->filled('max_price'), fn ($q) => $q->where('price', '<=', (int) $request->input('max_price')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('properties.index', compact('listings'));
    }

    public function show(PropertyListing $listing)
    {
        abort_unless($listing->status === 'active' && (! $listing->expires_at || $listing->expires_at->isFuture()), 404);

        return view('properties.show', compact('listing'));
    }

    public function mine(Request $request)
    {
        $listings = PropertyListing::where('user_id', $request->user()->id)->latest()->paginate(15);

        return view('properties.mine', compact('listings'));
    }

    public function create()
    {
        return view('properties.form', ['listing' => new PropertyListing()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->enforceFreeLimit($request);
        $data['user_id'] = $request->user()->id;
        $data['photos'] = $this->storePhotos($request);
        $data['status'] = 'active';

        $listing = PropertyListing::create($data);

        return redirect()->route('properties.mine')->with('status', 'آگهی شما با موفقیت ثبت و منتشر شد.');
    }

    public function edit(Request $request, PropertyListing $listing)
    {
        $this->authorizeOwner($request, $listing);

        return view('properties.form', compact('listing'));
    }

    public function update(Request $request, PropertyListing $listing)
    {
        $this->authorizeOwner($request, $listing);
        $data = $this->validated($request, $listing);
        $data['photos'] = array_values(array_merge(
            array_intersect((array) $request->input('existing_photos', []), $listing->photos ?? []),
            $this->storePhotos($request)
        ));
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

    private function enforceFreeLimit(Request $request): void
    {
        $userId = $request->user()->id;
        $hasActiveSubscription = Subscription::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->exists();

        if (! $hasActiveSubscription && PropertyListing::where('user_id', $userId)->where('status', 'active')->count() >= 3) {
            abort(403, 'سقف ۳ آگهی فعال رایگان شما تکمیل شده است. برای ثبت آگهی بیشتر اشتراک خود را فعال کنید.');
        }
    }

    private function validated(Request $request, ?PropertyListing $listing = null): array
    {
        $data = $request->validate([
            'listing_type' => ['required', Rule::in(['sale', 'rent', 'buy_request', 'rent_request'])],
            'property_type' => ['required', Rule::in(['residential', 'commercial', 'land'])],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'province' => ['nullable', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'neighborhood' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'hide_address' => ['sometimes', 'boolean'],
            'area' => ['nullable', 'numeric', 'min:1', 'max:1000000'],
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
        $data['hide_address'] = $request->boolean('hide_address', true);
        $data['allow_exchange'] = $request->boolean('allow_exchange');

        return $data;
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
