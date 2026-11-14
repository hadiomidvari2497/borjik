<?php

namespace App\\Http\\Controllers;

use App\\Models\\PropertyListing;
use App\\Models\\PropertyListingTourRoom;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Storage;
use Illuminate\\Validation\\ValidationException;

class PropertyListingTourController extends Controller
{
    public function store(Request $request, PropertyListing $listing)
    {
        $this->authorizeOwner($request, $listing);
        abort_if($listing->tourRooms()->count() >= 8, 422, 'برای هر آگهی حداکثر ۸ فضای خانه‌گردی می‌توانید ثبت کنید.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'projection' => ['required', 'in:equirectangular,flat'],
            'panorama' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
        ], [
            'name.required' => 'نام فضا را وارد کنید.',
            'name.max' => 'نام فضا نباید بیشتر از ۸۰ نویسه باشد.',
            'projection.required' => 'نوع پانوراما را انتخاب کنید.',
            'projection.in' => 'نوع پانوراما معتبر نیست.',
            'panorama.required' => 'تصویر پانوراما را انتخاب کنید.',
            'panorama.image' => 'فایل انتخاب‌شده تصویر معتبر نیست.',
            'panorama.mimes' => 'فرمت تصویر باید JPG، PNG یا WebP باشد.',
            'panorama.max' => 'حجم تصویر باید حداکثر ۱۵ مگابایت باشد.',
            'panorama.uploaded' => 'بارگذاری تصویر ناموفق بود؛ حجم فایل را بررسی کنید.',
        ]);

        $image = $request->file('panorama');
        $width = (int) getimagesize($image->getRealPath())[0];
        $height = (int) getimagesize($image->getRealPath())[1];
        $ratio = $width / max(1, $height);

        if ($data['projection'] === 'equirectangular' && abs($ratio - 2.0) > 0.015) {
            throw ValidationException::withMessages([
                'panorama' => 'برای پانورامای حرفه‌ای ۳۶۰ درجه، تصویر باید نسبت دقیق ۲ به ۱ داشته باشد. برای عکس پانورامای گوشی، گزینه پانورامای موبایل را انتخاب کنید.',
            ]);
        }

        if ($data['projection'] === 'flat' && ($width < 1000 || $ratio < 1.2)) {
            throw ValidationException::withMessages([
                'panorama' => 'تصویر پانورامای موبایل باید عریض باشد؛ حداقل عرض ۱۰۰۰ پیکسل و نسبت عرض به ارتفاع حداقل ۱٫۲ لازم است.',
            ]);
        }

        $path = $image->store('property-tours/'.$listing->id, 'public');
        $listing->tourRooms()->create([
            'name' => trim($data['name']),
            'image_path' => $path,
            'projection' => $data['projection'],
            'sort_order' => (int) $listing->tourRooms()->max('sort_order') + 1,
        ]);

        return back()->with('status', 'فضا به خانه‌گردی مجازی اضافه شد.');
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
