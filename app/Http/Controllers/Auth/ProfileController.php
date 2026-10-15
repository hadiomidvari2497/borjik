<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\IranianNationalId;
use App\Support\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $destinations = DB::table('building_user_roles as bur')
            ->join('buildings as b', 'b.id', '=', 'bur.building_id')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->where('bur.user_id', $request->user()->id)
            ->where('bur.status', 'active')->whereNull('b.deleted_at')
            ->orderBy('b.name')->orderBy('r.name_fa')
            ->get(['bur.id as membership_id', 'b.name as building_name', 'r.name_fa as role_name']);

        return view($request->user()->profile_completed_at ? 'auth.profile' : 'auth.profile-complete', ['user' => $request->user(), 'loginDestinations' => $destinations]);
    }

    public function updateLoginDestination(Request $request)
    {
        $data = $request->validate([
            'login_destination_mode' => ['required', Rule::in(['fixed', 'last'])],
            'preferred_membership_id' => ['nullable', 'integer'],
        ]);

        $preferredId = null;
        if ($data['login_destination_mode'] === 'fixed') {
            $preferredId = (int) ($data['preferred_membership_id'] ?? 0);
            $valid = $preferredId > 0 && DB::table('building_user_roles as bur')
                ->join('buildings as b', 'b.id', '=', 'bur.building_id')
                ->where('bur.id', $preferredId)->where('bur.user_id', $request->user()->id)
                ->where('bur.status', 'active')->whereNull('b.deleted_at')->exists();
            if (! $valid) {
                return back()->withErrors(['preferred_membership_id' => 'برای مقصد ثابت، یک ساختمان و نقش معتبر انتخاب کنید.'])->withInput();
            }
        }

        $request->user()->forceFill([
            'login_destination_mode' => $data['login_destination_mode'],
            'preferred_building_user_role_id' => $preferredId,
        ])->save();

        return redirect()->route('profile.edit')->with('status', 'تنظیمات ورود ذخیره شد.');
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
        $profileWasComplete = (bool) $user->profile_completed_at;
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

        return $profileWasComplete
            ? redirect()->route('profile.edit')->with('status', 'تغییرات پروفایل ذخیره شد.')
            : redirect()->route('dashboard')->with('status', 'پروفایل ذخیره شد.');
    }
}
