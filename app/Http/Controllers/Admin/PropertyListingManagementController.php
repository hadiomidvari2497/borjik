<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PropertyListing;
use App\Models\PropertyListingCreditTransaction;
use App\Models\PropertyListingPackageOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PropertyListingManagementController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:active,closed,rejected'],
            'listing_type' => ['nullable', 'in:sale,rent,buy_request,rent_request'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $listingsQuery = PropertyListing::query()->with(['user', 'unit.building'])
            ->when($filters['status'] ?? null, fn (Builder $query, $status) => $query->where('status', $status))
            ->when($filters['listing_type'] ?? null, fn (Builder $query, $type) => $query->where('listing_type', $type))
            ->when(($filters['q'] ?? '') !== '', function (Builder $query) use ($filters) {
                $like = '%'.addcslashes($filters['q'], '%_\').'%';
                $query->where(function (Builder $where) use ($like) {
                    $where->where('title', 'like', $like)
                        ->orWhere('city', 'like', $like)
                        ->orWhere('neighborhood', 'like', $like)
                        ->orWhereHas('user', fn (Builder $user) => $user
                            ->where('mobile', 'like', $like)
                            ->orWhere('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('company_name', 'like', $like));
                });
            });

        $listings = (clone $listingsQuery)->latest('id')->paginate(25)->withQueryString();

        $listingStats = PropertyListing::query()->selectRaw("
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'active' AND (expires_at IS NULL OR expires_at > ?) THEN 1 ELSE 0 END) AS active,
            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) AS closed,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected
        ", [now()])->first();

        $orderStats = PropertyListingPackageOrder::query()->selectRaw("
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_count,
            SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) AS revenue
        ")->first();

        $creditStats = PropertyListingCreditTransaction::query()->selectRaw("
            COALESCE(SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END), 0) AS purchased,
            COALESCE(SUM(CASE WHEN quantity < 0 THEN ABS(quantity) ELSE 0 END), 0) AS consumed,
            COALESCE(SUM(quantity), 0) AS available
        ")->first();

        $recentOrders = PropertyListingPackageOrder::query()->with('user')->latest('id')->limit(12)->get();

        return view('admin.property-listings.index', compact(
            'filters', 'listings', 'listingStats', 'orderStats', 'creditStats', 'recentOrders'
        ));
    }

    public function show(int $id)
    {
        $listing = PropertyListing::query()->with(['user', 'unit.building'])->findOrFail($id);

        return view('admin.property-listings.show', compact('listing'));
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,closed,rejected'],
            'moderation_note' => ['required_if:status,rejected', 'nullable', 'string', 'max:2000'],
        ]);

        $listing = PropertyListing::query()->findOrFail($id);
        $listing->update([
            'status' => $data['status'],
            'moderation_note' => $data['moderation_note'] ?? null,
            'moderated_by' => $request->user('admin')->id,
            'moderated_at' => now(),
        ]);

        return redirect()->route('admin.property-listings.show', $listing->id)
            ->with('status', 'وضعیت آگهی با موفقیت به‌روزرسانی شد.');
    }
}
