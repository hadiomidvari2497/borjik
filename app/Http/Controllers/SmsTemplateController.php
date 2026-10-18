<?php

namespace App\Http\Controllers;

use App\Models\SmsTemplate;
use App\Support\CurrentBuilding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SmsTemplateController extends Controller
{
    public function index(CurrentBuilding $current): View
    {
        $buildingId = $current->id();
        abort_unless($buildingId, 403);

        $templates = SmsTemplate::query()
            ->where('building_id', $buildingId)
            ->orderBy('key')
            ->get();

        $defaults = collect(config('sms.templates', []))
            ->map(fn ($body, $key) => [
                'key' => $key,
                'body' => $body,
                'is_overridden' => $templates->contains('key', $key),
            ]);

        $samples = [
            'code' => '۱۲۳۴۵۶', 'name' => 'علی رضایی', 'building' => 'برج آفتاب',
            'month' => 'مهر ۱۴۰۵', 'date' => '۱۴۰۵/۰۷/۳۰', 'amount' => '۲۵۰٬۰۰۰',
            'unit' => '۱۰۲', 'reason' => 'اطلاعات ناقص', 'inviter' => 'مدیر ساختمان',
            'role' => 'مالک واحد', 'link' => 'https://example.com/invite/demo',
            'title' => 'تعمیر آسانسور', 'number' => '۱۲۴', 'status' => 'در حال انجام',
        ];

        return view('sms-templates.index', compact('templates', 'defaults', 'samples'));
    }

    public function store(Request $request, CurrentBuilding $current): RedirectResponse
    {
        $buildingId = $current->id();
        abort_unless($buildingId, 403);

        $data = $request->validate([
            'key' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/', Rule::in(array_keys(config('sms.templates', [])))],
            'body' => ['required', 'string', 'min:3', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'key.in' => 'نوع قالب انتخاب‌شده معتبر نیست.',
            'key.regex' => 'شناسه قالب باید انگلیسی و با حروف کوچک باشد.',
        ]);

        $exists = SmsTemplate::query()->where('building_id', $buildingId)->where('key', $data['key'])->exists();
        if ($exists) {
            return back()->withErrors(['key' => 'برای این نوع پیامک قبلاً قالب اختصاصی ساخته شده است؛ همان قالب را ویرایش کن.'])->withInput();
        }

        SmsTemplate::create([
            'building_id' => $buildingId,
            'key' => $data['key'],
            'body' => $data['body'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('sms-templates.index')->with('status', 'قالب اختصاصی پیامک ذخیره شد.');
    }

    public function update(Request $request, int $id, CurrentBuilding $current): RedirectResponse
    {
        $buildingId = $current->id();
        abort_unless($buildingId, 403);
        $template = SmsTemplate::query()->where('building_id', $buildingId)->findOrFail($id);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $template->update([
            'body' => $data['body'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('sms-templates.index')->with('status', 'قالب پیامک به‌روزرسانی شد.');
    }

    public function destroy(int $id, CurrentBuilding $current): RedirectResponse
    {
        $buildingId = $current->id();
        abort_unless($buildingId, 403);

        SmsTemplate::query()->where('building_id', $buildingId)->findOrFail($id)->delete();

        return redirect()->route('sms-templates.index')->with('status', 'قالب اختصاصی حذف شد؛ پیامک‌های این نوع دوباره از متن پیش‌فرض استفاده می‌کنند.');
    }
}
