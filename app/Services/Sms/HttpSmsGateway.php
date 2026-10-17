<?php

namespace App\Services\Sms;

use App\Models\SmsProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * درایور عمومی HTTP: آدرس، هدرها، قالب بدنه و شرط موفقیت از پنل مدیریت تنظیم می‌شود، پس هر اپراتوری
 * که وب‌سرویس HTTP/REST دارد بدون تغییر کد قابل اتصال است.
 */
class HttpSmsGateway implements SmsGateway
{
    private array $s;

    public function __construct(private readonly SmsProvider $provider)
    {
        $this->s = $provider->settings();
    }

    public function label(): string
    {
        return $this->provider->name;
    }

    public function send(string $mobile, string $text): string
    {
        return $this->sendWithResponse($mobile, $text)['ref'];
    }

    /**
     * ارسال و برگرداندن شناسهٔ پیگیری به‌همراه نمونهٔ پاسخ اپراتور (برای تست ارسال در پنل).
     *
     * @return array{ref:string, status:int, body:string}
     */
    public function sendWithResponse(string $mobile, string $text): array
    {
        $secrets = $this->provider->secretValues();

        try {
            $vars = $this->variables($mobile, $text);
            $url = SmsProviderConfig::render((string) $this->s['url'], $vars, 'url');
            $this->assertAllowedUrl($url);

            $response = $this->request($url, $vars);
        } catch (SmsException $e) {
            throw new SmsException($this->mask($e->getMessage(), $secrets), 0, $e);
        } catch (Throwable $e) {
            throw new SmsException('خطا در ارتباط با اپراتور: '.$this->mask($e->getMessage(), $secrets), 0, $e);
        }

        $body = (string) $response->body();
        $snippet = $this->mask(Str::limit($body, 300, '…'), $secrets);

        if (! $response->successful()) {
            throw new SmsException("اپراتور خطا برگرداند (HTTP {$response->status()}): {$snippet}");
        }

        $this->assertSuccessRule($response->json(), $body, $snippet);

        $refPath = trim((string) $this->s['ref_path']);
        $ref = $refPath !== '' ? data_get($response->json(), $refPath) : null;
        $ref = is_scalar($ref) && (string) $ref !== '' ? (string) $ref : 'http-'.Str::lower(Str::random(10));

        return ['ref' => Str::limit($ref, 100, ''), 'status' => $response->status(), 'body' => $snippet];
    }

    private function variables(string $mobile, string $text): array
    {
        $vars = SmsProviderConfig::mobileForms($mobile, (string) $this->s['number_format']) + ['message' => $text];

        foreach ($this->s['params'] as $p) {
            $name = (string) ($p['name'] ?? '');
            if ($name !== '' && ! array_key_exists($name, $vars)) {
                $vars[$name] = (string) ($p['value'] ?? '');
            }
        }

        return $vars;
    }

    private function request(string $url, array $vars)
    {
        $timeout = max(1, min(60, (int) $this->s['timeout']));
        $headers = [];
        foreach ($this->s['headers'] as $h) {
            if (! empty($h['name'])) {
                $headers[$h['name']] = SmsProviderConfig::render((string) ($h['value'] ?? ''), $vars, 'raw');
            }
        }

        // ریدایرکت دنبال نمی‌شود: اپراتور نباید بتواند درخواست را به آدرس داخلی هدایت کند.
        $http = Http::timeout($timeout)->withOptions(['allow_redirects' => false])->withHeaders($headers);
        $type = (string) $this->s['body_type'];
        $body = (string) $this->s['body'];

        if (strtoupper((string) $this->s['method']) === 'GET') {
            $query = $type === 'form' ? SmsProviderConfig::parseFormLines($body, $vars) : [];

            // گزینهٔ query در Guzzle کوئری خود آدرس را حذف می‌کند؛ پس دستی به انتهای آدرس اضافه می‌کنیم.
            return $http->get($this->appendQuery($url, $query));
        }

        return match ($type) {
            'json' => $http->withBody(SmsProviderConfig::render($body, $vars, 'json'), 'application/json')->post($url),
            'form' => $http->asForm()->post($url, SmsProviderConfig::parseFormLines($body, $vars)),
            'raw' => $http->withBody(SmsProviderConfig::render($body, $vars, 'raw'), $this->contentType($headers))->post($url),
            default => $http->post($url),
        };
    }

    /** @param array<string,string> $query */
    private function appendQuery(string $url, array $query): string
    {
        if ($query === []) {
            return $url;
        }

        $pairs = [];
        foreach ($query as $k => $v) {
            $pairs[] = rawurlencode((string) $k).'='.rawurlencode($v);
        }

        return $url.(str_contains($url, '?') ? '&' : '?').implode('&', $pairs);
    }

    private function contentType(array $headers): string
    {
        foreach ($headers as $k => $v) {
            if (strtolower($k) === 'content-type') {
                return $v;
            }
        }

        return 'text/plain';
    }

    /** علاوه بر HTTP 2xx، در صورت تعریف، مقدار فیلد JSON یا وجود متن در پاسخ هم باید با انتظار بخواند. */
    private function assertSuccessRule(mixed $json, string $body, string $snippet): void
    {
        $rule = $this->s['success'] ?? [];
        $path = trim((string) ($rule['json_path'] ?? ''));
        $expected = (string) ($rule['expected'] ?? '');
        $contains = (string) ($rule['contains'] ?? '');

        if ($path !== '') {
            $actual = data_get($json, $path);
            $ok = $expected === ''
                ? ($actual !== null && $actual !== '' && $actual !== false)
                : (is_scalar($actual) && (string) $actual === $expected);
            if (! $ok) {
                throw new SmsException("اپراتور ارسال را تأیید نکرد (مقدار «{$path}» مطابق انتظار نبود): {$snippet}");
            }
        }

        if ($contains !== '' && ! str_contains($body, $contains)) {
            throw new SmsException("اپراتور ارسال را تأیید نکرد (متن «{$contains}» در پاسخ نبود): {$snippet}");
        }
    }

    /**
     * جلوگیری از SSRF: فقط http/https و (مگر با تنظیم صریح) فقط میزبان‌های عمومی.
     * اگر نام دامنه حل نشود، خود درخواست خطا می‌دهد و نیازی به رد جداگانه نیست.
     */
    public static function assertAllowedUrl(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new SmsException('آدرس وب‌سرویس معتبر نیست.');
        }
        if (config('sms.allow_private_hosts')) {
            return;
        }

        $host = trim($host, '[]');
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new SmsException('ارسال به آدرس‌های داخلی/خصوصی مجاز نیست.');
            }
        }
    }

    private function mask(string $text, array $secrets): string
    {
        foreach ($secrets as $secret) {
            if (strlen($secret) >= 3) {
                $text = str_replace([$secret, rawurlencode($secret)], '***', $text);
            }
        }

        return $text;
    }
}
