<?php

namespace App\Http\Controllers;

use App\Models\PropertyListing;
use App\Models\PropertyListingReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PropertyListingReportController extends Controller
{
    public function store(Request $request, int $listingId)
    {
        $data = $request->validate([
            'reason' => ['required', 'in:fraud,incorrect,duplicate,inappropriate,prohibited,other'],
            'details' => ['nullable', 'string', 'max:1500'],
        ]);

        $listing = PropertyListing::query()->findOrFail($listingId);
        abort_unless($listing->status === 'active' && (! $listing->expires_at || $listing->expires_at->isFuture()), 404);
        abort_if((int) $listing->user_id === (int) $request->user()->id, 403, 'امکان گزارش آگهی خودتان وجود ندارد.');

        DB::transaction(function () use ($request, $listingId, $data) {
            PropertyListing::query()->whereKey($listingId)->lockForUpdate()->firstOrFail();

            $alreadyReported = PropertyListingReport::query()
                ->where('property_listing_id', $listingId)
                ->where('reporter_user_id', $request->user()->id)
                ->where('status', 'pending')
                ->exists();

            if ($alreadyReported) {
                throw ValidationException::withMessages([
                    'report' => 'گزارش در حال بررسی دیگری برای این آگهی ثبت کرده‌اید.',
                ]);
            }

            PropertyListingReport::create([
                'property_listing_id' => $listingId,
                'reporter_user_id' => $request->user()->id,
                'reason' => $data['reason'],
                'details' => $data['details'] ?? null,
                'status' => 'pending',
            ]);
        });

        return redirect()->route('properties.show', $listingId)
            ->with('status', 'گزارش شما ثبت شد و توسط تیم صابخونه بررسی می‌شود.');
    }
}
