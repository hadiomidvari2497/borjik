<?php

namespace App\Http\Controllers\Concerns;

use App\Support\MobileNumber;
use Illuminate\Http\Request;

trait NormalizesNumbers
{
    /** ارقام فارسی و عربی و جداکننده‌ی هزارگان را از فیلدهای عددی فرم برمی‌دارد تا اعتبارسنجی درست کار کند. */
    protected function normalizeNumbers(Request $request, array $keys): void
    {
        $merge = [];
        foreach ($keys as $key) {
            if ($request->has($key) && $request->input($key) !== null) {
                $merge[$key] = str_replace([',', '٬', '،', ' '], '', MobileNumber::toLatinDigits((string) $request->input($key)));
            }
        }
        $request->merge($merge);
    }
}
