<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAccessToken;
use App\Models\AiActionRequest;
use App\Services\Operations\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use Throwable;

class AiConnectorController extends Controller
{
    private const ABILITIES = ['read:overview', 'read:diagnostics', 'actions:request'];

    public function index()
    {
        return view('admin.ai-connector.index', [
            'tokens' => AiAccessToken::query()->orderByDesc('id')->paginate(15),
            'actions' => AiActionRequest::query()->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->orderByDesc('id')->paginate(15, ['*'], 'actions_page'),
            'abilities' => self::ABILITIES,
        ]);
    }

    public function storeToken(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['required', Rule::in(self::ABILITIES)],
            'expires_in_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $plain = 'bj_ai_'.bin2hex(random_bytes(32));
        AiAccessToken::query()->create([
            'name' => trim($data['name']),
            'token_hash' => hash('sha256', $plain),
            'token_prefix' => substr($plain, 0, 15),
            'abilities' => array_values(array_unique($data['abilities'])),
            'created_by' => auth('admin')->id(),
            'expires_at' => now()->addDays((int) $data['expires_in_days']),
        ]);

        return redirect()->route('admin.ai-connector.index')->with('issued_token', $plain)->with('status', 'توکن ساخته شد؛ مقدار کامل فقط همین یک بار نمایش داده می‌شود.');
    }

    public function revokeToken(AiAccessToken $token)
    {
        if ($token->revoked_at === null) {
            $token->forceFill(['revoked_at' => now()])->save();
        }

        return redirect()->route('admin.ai-connector.index')->with('status', 'توکن باطل شد و دیگر قابل استفاده نیست.');
    }

    public function approveAction(Request $request, AiActionRequest $action)
    {
        $request->validate(['confirm_execution' => ['required', 'accepted']]);

        $claimed = AiActionRequest::query()->whereKey($action->id)->where('status', 'pending')->update([
            'status' => 'running',
            'approved_by' => auth('admin')->id(),
            'approved_at' => now(),
            'updated_at' => now(),
        ]);
        if ($claimed !== 1) {
            return redirect()->route('admin.ai-connector.index')->with('error', 'این درخواست قبلاً بررسی شده یا دیگر در انتظار تأیید نیست.');
        }

        $action->refresh();
        try {
            $result = match ($action->action) {
                'run_health_check' => $this->runHealthCheck(),
                'run_backup' => $this->runBackup(),
                default => throw new \RuntimeException('نوع عملیات در فهرست مجاز نیست.'),
            };
            $action->forceFill(['status' => 'success', 'result' => $result, 'finished_at' => now()])->save();

            return redirect()->route('admin.ai-connector.index')->with('status', 'عملیات تأیید و اجرا شد.');
        } catch (Throwable $exception) {
            report($exception);
            $action->forceFill([
                'status' => 'failed',
                'result' => ['message' => mb_substr($exception->getMessage(), 0, 500)],
                'finished_at' => now(),
            ])->save();

            return redirect()->route('admin.ai-connector.index')->with('error', 'اجرای عملیات ناموفق بود؛ جزئیات در مرکز خطا ثبت شد.');
        }
    }

    public function rejectAction(AiActionRequest $action)
    {
        $updated = AiActionRequest::query()->whereKey($action->id)->where('status', 'pending')->update([
            'status' => 'rejected',
            'approved_by' => auth('admin')->id(),
            'approved_at' => now(),
            'finished_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.ai-connector.index')->with(
            $updated === 1 ? 'status' : 'warning',
            $updated === 1 ? 'درخواست عملیات رد شد.' : 'این درخواست قبلاً بررسی شده است.'
        );
    }

    private function runHealthCheck(): array
    {
        $exit = Artisan::call('borjik:health-check');
        $output = mb_substr(trim(Artisan::output()), 0, 1500);
        if ($exit !== 0) {
            throw new \\RuntimeException('بررسی سلامت با کد خطا پایان یافت: '.$output);
        }

        return ['exit_code' => $exit, 'output' => $output];
    }

    private function runBackup(): array
    {
        $run = app(BackupService::class)->run();

        if ($run->status !== 'success') {
            throw new \RuntimeException('ساخت بکاپ ناموفق بود: '.(string) ($run->message ?? 'خطای نامشخص'));
        }

        return [
            'backup_run_id' => $run->id,
            'status' => $run->status,
            'filename' => $run->filename,
            'message' => $run->message,
        ];
    }
}
