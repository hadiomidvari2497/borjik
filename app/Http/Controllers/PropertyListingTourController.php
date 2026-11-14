<?php

namespace App\Http\Controllers;

use App\Models\PropertyListing;
use App\Models\PropertyListingTourRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PropertyListingTourController extends Controller
{
    public function store(Request $request, PropertyListing $listing)
    {
        $this->authorizeOwner($request, $listing);
        abort_if($listing->tourRooms()->count() >= 8, 422, 'برای هر آگهی حداکثر ۸ فضای ۳۶۰ درجه می‌توانید ثبت کنید.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'panorama' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
        ], [
            'name.required' => 'نام فضا را وارد کنید.',
            'name.max' => 'نام فضا نباید بیشتر از ۸۰ نویسه باشد.',
            'panorama.required' => 'تصویر پانورامای ۳۶۰ درجه را انتخاب کنید.',
            'panorama.image' => 'فایل انتخاب‌شده تصویر معتبر نیست.',
            'panorama.mimes' => 'فرمت تصویر باید JPG، PNG یا WebP باشد.',
            'panorama.max' => 'حجم تصویر باید حداکثر ۱۵ مگابایت باشد.',
            'panorama.uploaded' => 'بارگذاری تصویر ناموفق بود؛ حجم فایل را بررسی کنید.',
        ]);

        $path = $request->file('panorama')->store('property-tours/'.$listing->id, 'public');
        $listing->tourRooms()->create([
            'name' => trim($data['name']),
            'image_path' => $path,
            'sort_order' => (int) $listing->tourRooms()->max('sort_order') + 1,
        ]);

        return back()->with('status', 'تصویر ۳۶۰ درجه فضا با موفقیت اضافه شد.');
    }

    public function destroy(Request $request, PropertyListing $listing, PropertyListingTourRoom $room)
    {
        $this->authorizeOwner($request, $listing);
        abort_unless((int) $room->property_listing_id === (int) $listing->id, 404);

        Storage::disk('public')->delete($room->image_path);
        $room->delete();

        return back()->with('status', 'فضا از خانه‌گردی مجازی حذف شد.');
    }

    private function authorizeOwner(Request $request, PropertyListing $listing): void
    {
        abort_unless((int) $listing->user_id === (int) $request->user()->id, 403);
    }
}
