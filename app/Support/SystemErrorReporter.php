<?php

namespace App\Support;

use App\Models\SystemErrorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

final class SystemErrorReporter
{
    /**
     * ثبت خطای مدیریت‌شده‌ای که به‌جای صفحهٔ خطا، به پیام قابل‌فهم برای کاربر تبدیل شده است.
     *
     * @param array<string, scalar|null> $context
     */
    public static function warning(
        Request $request,
        string $category,
        string $message,
        string $details,
        array $context = [],
        int $statusCode = 422,
    ): void {
        self::store($request, 'ApplicationWarning', $category, $message, $details, $context, 'warning', $statusCode);
    }

    /**
     * ثبت خطای فنی گرفته‌شده در try/catch؛ اطلاعات حساس از جزئیات حذف می‌شوند.
     *
     * @param array<string, scalar|null> $context
     */
    public static function caught(
        Request $request,
        Throwable $exception,
        string $category,
        string $message,
        array $context = [],
    ): void {
        $details = $exception::class.': '.$exception->getMessage()
            ."\nFile: ".$exception->getFile()
            ."\nLine: ".$exception->getLine()
            ."\nTrace:\n".$exception->getTraceAsString();

        self::store($request, $exception::class, $category, $message, $details, $context, 'error', 500);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    private static function store(
        Request $request,
        string $exceptionClass,
        string $category,
        string $message,
        string $details,
        array $context,
        string $severity,
        int $statusCode,
    ): void {
        try {
            $admin = $request->user('admin');
            $user = $request->user();
            $safeDetails = self::redact($details);

            SystemErrorLog::query()->create([
                'exception_class' => Str::limit($exceptionClass, 255, ''),
                'message' => Str::limit(self::redact($message), 4000, ''),
                'details' => Str::limit($safeDetails, 50000, ''),
                'context' => self::safeContext($context),
                'severity' => $severity,
                'category' => Str::limit($category, 100, ''),
                'file' => null,
                'line' => null,
                'method' => Str::limit($request->method(), 10, ''),
                'path' => Str::limit('/'.$request->path(), 500, ''),
                'route_name' => $request->route()?->getName() ? Str::limit($request->route()->getName(), 190, '') : null,
                'status_code' => $statusCode,
                'actor_type' => $admin ? 'admin' : ($user ? 'user' : 'guest'),
                'user_id' => $user?->getAuthIdentifier(),
                'admin_user_id' => $admin?->getAuthIdentifier(),
                'created_at' => now(),
            ]);
        } catch (Throwable $loggingException) {
            // Failure to log must never break the user's original workflow.
            error_log('SystemErrorReporter persistence failed: '.$loggingException::class);
        }
    }

    private static function redact(string $value): string
    {
        $value = preg_replace('/SQL:\\s.*$/is', 'SQL: [query redacted]', $value) ?? $value;
        $value = preg_replace('/((?:password|passwd|token|secret|api[_-]?key|authorization|cookie|merchant_id)\\s*[=:]\\s*)[^,\\s;]+/i', '$1[REDACTED]', $value) ?? $value;

        return $value;
    }

    /**
     * @param array<string, scalar|null> $context
     * @return array<string, scalar|null>
     */
    private static function safeContext(array $context): array
    {
        $safe = [];
        foreach ($context as $key => $value) {
            if (preg_match('/password|token|secret|key|cookie|auth|mobile|phone|email|card|iban/i', (string) $key)) {
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $safe[Str::limit((string) $key, 80, '')] = is_string($value) ? Str::limit($value, 250, '') : $value;
            }
        }

        return $safe;
    }
}
