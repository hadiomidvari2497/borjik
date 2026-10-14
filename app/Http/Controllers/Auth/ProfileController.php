<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\IranianNationalId;
use App\Support\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Morilog\Jalali\Jalalian;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('auth.profile', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $type = $request->input('person_type') === 'legal' ? 'legal' : 'real';
        $request->merge([
            'national_code' => MobileNumber::toLatinDigits((string) $request->input('national_code')),
            'birth_date' => MobileNumber::toLatinDigits((string) $request->input('birth_date')),
        ]);

        $data = $request->validate([
            'person_type' => ['required', Rule::in(['real', 'legal'])],
            'first_name' => [$type === 'real' ? 'required' : 'nullable', 'string', 'max:100'],
            'last_name' => [$type === 'real' ? 'required' : 'nullable', 'string', 'max:100'],
            'company_name' => [$type === 'legal' ? 'required' : 'nullable', 'string', 'max:200'],
            'national_code' => ['required', new IranianNationalId($type)],
            'birth_date' => ['required', 'regex:/^\d{4}\/\d{1,2}\/\d{1,2}$/'], // تاریخ شمسی: ۱۳۷۰/۰۵/۱۲
            'address' => ['nullable', 'string', 'max:1000'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        [$jy, $jm, $jd] = array_map('intval', explode('/', $data['birth_date']));
        try {
            $birth = (new Jalalian($jy, $jm, $jd))->toCarbon()->startOfDay();
            $back = Jalalian::fromCarbon($birth);
            $valid = $back->getYear() === $jy && $back->getMonth() === $jm && $back->getDay() === $jd && $birth->isPast();
        } catch (\Throwable) {
            $valid = false;
        }
        if (! $valid) {
            return back()->withErrors(['birth_date' => 'تاریخ واردشده معتبر نیست (نمونه: ۱۳۷۰/۰۵/۱۲).'])->withInput();
        }

        $user = $request->user();
        $user->fill([
            'person_type' => $data['person_type'],
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'national_code' => $data['national_code'],
            'birth_date' => $birth,
            'address' => $data['address'] ?? null,
        ]);
        if (! empty($data['password'])) {
            $user->password = $data['password']; // cast «hashed»
        }
        // عکس پروفایل اختیاری است و شرط تکمیل نیست.
        $user->profile_completed_at ??= now();
        $user->save();

        return redirect()->route('dashboard')->with('status', 'پروفایل ذخیره شد.');
    }
}
