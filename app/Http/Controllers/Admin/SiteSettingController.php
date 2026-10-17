<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** تنظیمات سراسری سایت: روش‌های ورود و اطلاعات عمومی/پشتیبانی (بند ۴.۲.۲ و ۴.۲۹ سند). */
class SiteSettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings.index', [
            'otp' => SiteSetting::otpLoginEnabled(),
            'password' => SiteSetting::passwordLoginEnabled(),
            'siteName' => (string) SiteSetting::get('site.name'),
            'supportPhone' => (string) SiteSetting::get('support.phone'),
            'supportEmail' => (string) SiteSetting::get('support.email'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:60'],
            'support_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9۰-۹+\-\s()]+$/u'],
            'support_email' => ['nullable', 'email', 'max:120'],
        ], [
            'support_phone.regex' => 'شماره‌ی پشتیبانی فقط می‌تواند شامل عدد و علامت‌های + - ( ) باشد.',
            'support_email.email' => 'ایمیل پشتیبانی معتبر نیست.',
        ]);

        $otp = $request->boolean('otp_enabled');
        $password = $request->boolean('password_enabled');

        if (! $otp && ! $password) {
            throw ValidationException::withMessages([
                'otp_enabled' => 'حداقل یکی از روش‌های ورود باید فعال بماند.',
            ]);
        }

        SiteSetting::set('auth.otp_enabled', $otp);
        SiteSetting::set('auth.password_enabled', $password);
        SiteSetting::set('site.name', $data['site_name']);
        SiteSetting::set('support.phone', $data['support_phone'] ?? '');
        SiteSetting::set('support.email', $data['support_email'] ?? '');

        return redirect()->route('admin.settings.edit')->with('status', 'تنظیمات سایت ذخیره شد.');
    }
}
