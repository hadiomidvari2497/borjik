<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PropertyListingSettingsController extends Controller
{
    public function edit()
    {
        return view('admin.property-listings.settings', [
            'freeLimit' => (int) SiteSetting::get('property_listings.free_limit', 3),
            'packages' => SiteSetting::get('property_listings.packages', [
                ['quantity' => 1, 'price' => 50000, 'active' => true],
                ['quantity' => 3, 'price' => 120000, 'active' => true],
                ['quantity' => 10, 'price' => 350000, 'active' => true],
            ]),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'free_limit' => ['required', 'integer', 'min:0', 'max:100'],
            'packages' => ['required', 'array', 'min:1', 'max:10'],
            'packages.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'packages.*.price' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'packages.*.active' => ['nullable', 'boolean'],
        ]);

        $packages = collect($data['packages'])->map(function (array $package) {
            return [
                'quantity' => (int) $package['quantity'],
                'price' => (int) $package['price'],
                'active' => (bool) ($package['active'] ?? false),
            ];
        })->values();

        if ($packages->pluck('quantity')->unique()->count() !== $packages->count()) {
            throw ValidationException::withMessages([
                'packages' => 'تعداد آگهی در هر بسته باید یکتا باشد.',
            ]);
        }

        SiteSetting::set('property_listings.free_limit', (int) $data['free_limit']);
        SiteSetting::set('property_listings.packages', $packages->all());

        return redirect()->route('admin.property-listings.settings')
            ->with('status', 'تنظیمات بازارچه آگهی‌های ملکی ذخیره شد.');
    }
}
