<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Models\SmsProvider;
use App\Services\Sms\HttpSmsGateway;
use App\Services\Sms\SmsException;
use App\Services\Sms\SmsProviderConfig;
use App\Support\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** اتصال به اپراتورهای پیامکی (بند ۴.۱۷ سند): تنظیمات هر اپراتور از پنل، بدون تغییر کد. */
class SmsProviderController extends Controller
{
    private const DEFAULT_TEST_TEXT = 'تست اتصال پیامک برجیک';

    public function index()
    {
        return view('admin.sms.providers.index', [
            'providers' => SmsProvider::query()->orderByDesc('is_default')->orderBy('name')->get(),
            'current' => SmsProvider::current(),
            'driver' => config('sms.driver'),
            'defaultText' => self::DEFAULT_TEST_TEXT,
        ]);
    }

    public function create()
    {
        return view('admin.sms.providers.form', ['provider' => null, 'presets' => config('sms_presets'), 's' => (new SmsProvider)->settings(), 'messageTypes' => $this->messageTypes(), 'fallbackProviders' => SmsProvider::query()->where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        [$attrs, $config] = $this->validated($request, null);

        $provider = DB::transaction(function () use ($attrs, $config) {
            $provider = SmsProvider::create($attrs + ['driver' => 'http', 'config' => $config]);
            $this->applyDefault($provider, $attrs['is_default']);

            return $provider;
        });

        return redirect()->route('admin.sms.providers.index')
            ->with('status', "اتصال «{$provider->name}» ذخیره شد. برای اطمینان یک «تست ارسال» انجام دهید.");
    }

    public function edit(int $id)
    {
        $provider = SmsProvider::findOrFail($id);

        return view('admin.sms.providers.form', ['provider' => $provider, 'presets' => config('sms_presets'), 's' => $provider->settings(), 'messageTypes' => $this->messageTypes(), 'fallbackProviders' => SmsProvider::query()->where('is_active', true)->where('id', '!=', $provider->id)->orderBy('name')->get()]);
    }

    public function update(Request $request, int $id)
    {
        $provider = SmsProvider::findOrFail($id);
        [$attrs, $config] = $this->validated($request, $provider);

        DB::transaction(function () use ($provider, $attrs, $config) {
            $provider->update($attrs + ['config' => $config, 'last_test_ok' => null, 'last_test_note' => null]);
            $this->applyDefault($provider, $attrs['is_default']);
        });

        return redirect()->route('admin.sms.providers.index')->with('status', 'اتصال پیامکی به‌روز شد؛ تست قبلی باطل شد، دوباره «تست ارسال» کنید.');
    }

    public function destroy(int $id)
    {
        $provider = SmsProvider::findOrFail($id);
        $provider->delete();

        return redirect()->route('admin.sms.providers.index')->with('status', 'اتصال حذف شد.');
    }

    public function makeDefault(int $id)
    {
        $provider = SmsProvider::findOrFail($id);

        if (! $provider->is_active) {
            return back()->with('error', 'ابتدا اتصال را فعال کنید.');
        }

        DB::transaction(fn () => $this->applyDefault($provider, true));

        return back()->with('status', "از این پس پیامک‌ها از طریق «{$provider->name}» ارسال می‌شوند.");
    }

    public function toggle(int $id)
    {
        $provider = SmsProvider::findOrFail($id);
        $active = ! $provider->is_active;

        $provider->update(['is_active' => $active, 'is_default' => $active ? $provider->is_default : false]);

        return back()->with('status', $active ? 'اتصال فعال شد.' : 'اتصال غیرفعال شد'.($provider->wasChanged('is_default') ? ' و از پیش‌فرض خارج شد.' : '.'));
    }

    /** ارسال تستی؛ نتیجه (و نمونهٔ پاسخ اپراتور برای تنظیم «شرط موفقیت») در صفحه نمایش داده می‌شود. */
    public function test(Request $request, int $id)
    {
        $provider = SmsProvider::findOrFail($id);

        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
            'message' => ['nullable', 'string', 'max:300'],
        ]);

        $mobile = MobileNumber::normalize($data['mobile']);
        if (! $mobile) {
            throw ValidationException::withMessages(['mobile' => 'شماره‌ی موبایل معتبر نیست.']);
        }
        $text = ($data['message'] ?? null) ?: self::DEFAULT_TEST_TEXT;

        $log = SmsLog::create(['mobile' => $mobile, 'template_key' => 'admin_test', 'body' => $text, 'status' => 'queued', 'gateway' => $provider->name]);

        try {
            $result = (new HttpSmsGateway($provider))->sendWithResponse($mobile, $text);
            $log->update(['status' => 'sent', 'provider_ref' => $result['ref'], 'sent_at' => now()]);
            $provider->update(['last_test_at' => now(), 'last_test_ok' => true, 'last_test_note' => null]);

            return back()->with('status', 'ارسال تستی انجام شد.')
                ->with('sms_test', ['ok' => true, 'provider' => $provider->name, 'mobile' => $mobile, 'http' => $result['status'], 'ref' => $result['ref'], 'body' => $result['body']]);
        } catch (SmsException $e) {
            $log->update(['status' => 'failed', 'error' => Str::limit($e->getMessage(), 250, '…')]);
            $provider->update(['last_test_at' => now(), 'last_test_ok' => false, 'last_test_note' => Str::limit($e->getMessage(), 250, '…')]);

            return back()->with('error', 'ارسال تستی ناموفق بود.')
                ->with('sms_test', ['ok' => false, 'provider' => $provider->name, 'mobile' => $mobile, 'message' => $e->getMessage()]);
        }
    }


    /** انواع پیامک شناخته‌شده از قالب‌های پیش‌فرض و قالب‌های تعریف‌شده در پنل. */
    private function messageTypes(): array
    {
        $types = collect(array_keys(config('sms.templates', [])))
            ->merge(\App\Models\SmsTemplate::query()->distinct()->pluck('key'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return $types->mapWithKeys(function ($key): array {
            $label = match ($key) {
                'otp' => 'کد ورود',
                'expense_reminder' => 'یادآوری ثبت هزینه',
                'receipt_approved' => 'تأیید فیش پرداخت',
                'receipt_rejected' => 'رد فیش پرداخت',
                'rent_invoice_issued' => 'صدور صورت‌حساب اجاره',
                'invitation' => 'دعوت به ساختمان',
                'task_created', 'task_assigned' => 'ارجاع تسک',
                'task_status_changed' => 'تغییر وضعیت تسک',
                default => str_replace('_', ' ', $key),
            };

            return [$key => ['label' => $label]];
        })->all();
    }

    /** فقط یک اتصال پیش‌فرض می‌ماند. */
    private function applyDefault(SmsProvider $provider, bool $wantDefault): void
    {
        if ($wantDefault && $provider->is_active) {
            SmsProvider::query()->where('id', '!=', $provider->id)->update(['is_default' => false]);
            $provider->update(['is_default' => true]);
        }
    }

    /** @return array{0: array{name:string,is_active:bool,is_default:bool}, 1: array} */
    private function validated(Request $request, ?SmsProvider $existing): array
    {
        $r = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('sms_providers', 'name')->ignore($existing?->id)],
            'method' => ['required', Rule::in(['GET', 'POST'])],
            'url' => ['required', 'string', 'max:500'],
            'number_format' => ['required', Rule::in(['local', 'intl', 'plus'])],
            'body_type' => ['required', Rule::in(['none', 'json', 'form', 'raw'])],
            'body' => ['nullable', 'string', 'max:5000'],
            'template_body_ids' => ['nullable', 'array', 'max:100'],
            'template_body_ids.*' => ['nullable', 'string', 'max:40'],
            'body_id_fallback_provider_id' => ['nullable', 'integer', 'exists:sms_providers,id'],
            'headers' => ['nullable', 'array', 'max:15'],
            'headers.*.name' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9\-]+$/'],
            'headers.*.value' => ['nullable', 'string', 'max:500'],
            'params' => ['nullable', 'array', 'max:20'],
            'params.*.name' => ['nullable', 'string', 'max:40', 'regex:/^[a-z][a-z0-9_]*$/'],
            'params.*.value' => ['nullable', 'string', 'max:500'],
            'params.*.secret' => ['nullable', 'boolean'],
            'success_json_path' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_.\-]*$/'],
            'success_expected' => ['nullable', 'string', 'max:100'],
            'success_contains' => ['nullable', 'string', 'max:200'],
            'ref_path' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_.\-]*$/'],
            'timeout' => ['required', 'integer', 'min:1', 'max:60'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ], [
            'headers.*.name.regex' => 'نام هدر فقط می‌تواند شامل حروف انگلیسی، عدد و - باشد.',
            'params.*.name.regex' => 'نام پارامتر باید با حرف کوچک انگلیسی شروع شود و فقط شامل حروف کوچک، عدد و _ باشد.',
            'success_json_path.regex' => 'مسیر JSON فقط می‌تواند شامل حروف، عدد، نقطه و - باشد (مثلاً return.status).',
            'ref_path.regex' => 'مسیر JSON فقط می‌تواند شامل حروف، عدد، نقطه و - باشد (مثلاً entries.0.messageid).',
        ]);

        $headers = collect($r['headers'] ?? [])->filter(fn ($h) => trim((string) ($h['name'] ?? '')) !== '')
            ->map(fn ($h) => ['name' => trim($h['name']), 'value' => (string) ($h['value'] ?? '')])->values()->all();

        $params = $this->mergeParams($r['params'] ?? [], $existing);

        $this->assertUrl($r['url']);

        $fallbackId = (int) ($r['body_id_fallback_provider_id'] ?? 0);
        if ($fallbackId > 0) {
            if ($existing && $fallbackId === (int) $existing->id) {
                throw ValidationException::withMessages(['body_id_fallback_provider_id' => 'اتصال جایگزین باید با اتصال فعلی متفاوت باشد.']);
            }

            $fallback = SmsProvider::query()->whereKey($fallbackId)->where('is_active', true)->first();
            if (! $fallback) {
                throw ValidationException::withMessages(['body_id_fallback_provider_id' => 'اتصال جایگزین باید وجود داشته و فعال باشد.']);
            }
        }
        if ($r['method'] === 'GET' && ! in_array($r['body_type'], ['none', 'form'], true)) {
            throw ValidationException::withMessages(['body_type' => 'در روش GET فقط «بدون بدنه» یا «فرم» (به‌صورت کوئری) معنا دارد.']);
        }

        $this->assertPlaceholders($r, $headers, $params);
        $this->assertJsonBody($r, $params);

        $isActive = (bool) ($r['is_active'] ?? false);
        $isDefault = (bool) ($r['is_default'] ?? false);
        if ($isDefault && ! $isActive) {
            throw ValidationException::withMessages(['is_default' => 'اتصال پیش‌فرض باید فعال باشد.']);
        }

        $config = [
            'method' => $r['method'], 'url' => trim($r['url']), 'number_format' => $r['number_format'], 'headers' => $headers,
            'body_type' => $r['body_type'], 'body' => (string) ($r['body'] ?? ''), 'params' => $params,
            'template_body_ids' => collect($r['template_body_ids'] ?? [])->map(fn ($id) => trim((string) $id))->filter(fn ($id) => $id !== '')->all(),
            'body_id_fallback_provider_id' => filled($r['body_id_fallback_provider_id'] ?? null) ? (int) $r['body_id_fallback_provider_id'] : null,
            'success' => [
                'json_path' => trim((string) ($r['success_json_path'] ?? '')),
                'expected' => (string) ($r['success_expected'] ?? ''),
                'contains' => (string) ($r['success_contains'] ?? ''),
            ],
            'ref_path' => trim((string) ($r['ref_path'] ?? '')), 'timeout' => (int) $r['timeout'],
        ];

        return [['name' => trim($r['name']), 'is_active' => $isActive, 'is_default' => $isDefault], $config];
    }

    /**
     * پارامتر محرمانهٔ بدون مقدار جدید، مقدار قبلی همان نام را نگه می‌دارد (مقدار محرمانه هرگز به فرم برنمی‌گردد).
     *
     * @return list<array{name:string,value:string,secret:bool}>
     */
    private function mergeParams(array $rows, ?SmsProvider $existing): array
    {
        $old = collect($existing?->settings()['params'] ?? [])->keyBy('name');
        $out = [];
        $seen = [];

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            if (in_array($name, SmsProviderConfig::BUILTIN, true) || isset($seen[$name])) {
                throw ValidationException::withMessages(['params' => "نام پارامتر «{$name}» رزرو یا تکراری است."]);
            }
            $seen[$name] = true;

            $secret = (bool) ($row['secret'] ?? false);
            $value = (string) ($row['value'] ?? '');
            if ($secret && $value === '') {
                $prev = $old->get($name);
                if (! $prev || ($prev['value'] ?? '') === '') {
                    throw ValidationException::withMessages(['params' => "مقدار پارامتر محرمانهٔ «{$name}» را وارد کنید."]);
                }
                $value = (string) $prev['value'];
            }

            $out[] = ['name' => $name, 'value' => $value, 'secret' => $secret];
        }

        return $out;
    }

    private function assertUrl(string $url): void
    {
        // میزبان نباید متغیر باشد؛ وگرنه مقصد ارسال را می‌شد با مقدار پارامتر عوض کرد.
        if (preg_match('#^[A-Za-z][A-Za-z0-9+.\-]*://[^/?\#]*\{\{#', trim($url))) {
            throw ValidationException::withMessages(['url' => 'نام میزبان نباید متغیر باشد.']);
        }

        // ساختار آدرس بعد از جایگزینی متغیرها بررسی می‌شود؛ مسدودسازی میزبان داخلی هنگام ارسال انجام می‌شود.
        $parts = parse_url((string) preg_replace('/\{\{[^}]*\}\}/', 'x', $url));
        if (! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true) || empty($parts['host'])) {
            throw ValidationException::withMessages(['url' => 'آدرس باید با http:// یا https:// شروع شود و معتبر باشد.']);
        }
    }

    private function assertPlaceholders(array $r, array $headers, array $params): void
    {
        $known = array_merge(SmsProviderConfig::BUILTIN, array_column($params, 'name'));
        $texts = [$r['url'], (string) ($r['body'] ?? ''), ...array_column($headers, 'value')];

        foreach ($texts as $text) {
            foreach (SmsProviderConfig::placeholdersIn((string) $text) as $name) {
                if (! in_array($name, $known, true)) {
                    throw ValidationException::withMessages(['url' => "متغیر «{{{$name}}}» تعریف نشده است. آن را در بخش پارامترها اضافه کنید."]);
                }
            }
        }
    }

    private function assertJsonBody(array $r, array $params): void
    {
        if ($r['body_type'] !== 'json') {
            return;
        }

        $vars = ['mobile' => '09120000000', 'mobile_local' => '09120000000', 'mobile_intl' => '989120000000', 'mobile_plus' => '+989120000000', 'message' => 'تست "نمونه"'];
        foreach ($params as $p) {
            $vars[$p['name']] = $p['secret'] ? '0' : (string) $p['value'];
        }

        if (json_decode(SmsProviderConfig::render((string) ($r['body'] ?? ''), $vars, 'json')) === null) {
            throw ValidationException::withMessages(['body' => 'بدنهٔ JSON معتبر نیست. ساختار آن را بررسی کنید (متغیرهای متنی باید داخل "..." باشند).']);
        }
    }
}
