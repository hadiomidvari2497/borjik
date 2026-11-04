<?php

namespace App\Http\Controllers;

use App\Models\PropertyListing;
use App\Models\PropertyListingInquiry;
use App\Notifications\PropertyListingNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PropertyListingInquiryController extends Controller
{
    public function store(Request $request, int $listingId)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'share_mobile' => ['nullable', 'accepted'],
        ]);

        $listing = PropertyListing::query()->published()->findOrFail($listingId);
        abort_if((int) $listing->user_id === (int) $request->user()->id, 403, 'برای آگهی خودتان نمی‌توانید درخواست تماس ثبت کنید.');

        $inquiry = PropertyListingInquiry::create([
            'property_listing_id' => $listing->id,
            'sender_user_id' => $request->user()->id,
            'recipient_user_id' => $listing->user_id,
            'sender_name' => $request->user()->display_name,
            'sender_mobile' => $request->boolean('share_mobile') ? $request->user()->mobile : null,
            'message' => $data['message'],
            'status' => 'new',
        ]);

        $listing->user->notify(new PropertyListingNotification(
            event: 'inquiry_received',
            title: 'درخواست تماس جدید برای آگهی',
            message: $inquiry->sender_name.' برای آگهی «'.$listing->title.'» پیام فرستاده است.',
            url: route('properties.inquiries.index'),
            level: 'info',
            listingId: $listing->id,
        ));

        return redirect()->route('properties.show', $listing)
            ->with('status', 'درخواست شما برای ثبت‌کننده آگهی ارسال شد.');
    }

    public function index(Request $request)
    {
        $inquiries = PropertyListingInquiry::query()
            ->where('recipient_user_id', $request->user()->id)
            ->with(['listing', 'sender'])
            ->latest()
            ->paginate(15);

        PropertyListingInquiry::query()
            ->where('recipient_user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('properties.inquiries', compact('inquiries'));
    }

    public function update(Request $request, PropertyListingInquiry $inquiry)
    {
        abort_unless((int) $inquiry->recipient_user_id === (int) $request->user()->id, 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'contacted', 'closed'])],
        ]);

        $inquiry->update(['status' => $data['status'], 'read_at' => $inquiry->read_at ?? now()]);

        return back()->with('status', 'وضعیت درخواست به‌روزرسانی شد.');
    }
}
