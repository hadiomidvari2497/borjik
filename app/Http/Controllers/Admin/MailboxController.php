<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\MailAccount;
use App\Models\MailServerSetting;
use App\Services\Mailbox\ImapMailbox;
use App\Services\Mailbox\MailboxProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Morilog\Jalali\Jalalian;
use Throwable;

class MailboxController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:120'], 'account' => ['nullable', 'integer']]);
        $admin = Auth::guard('admin')->user();
        $accountQuery = MailAccount::query()->where('is_active', true);
        if (! $admin->is_super) $accountQuery->where('admin_user_id', $admin->id);
        elseif ($request->filled('account')) $accountQuery->whereKey($request->integer('account'));
        $account = $accountQuery->orderBy('id')->first();

        if (! $account) {
            return view('admin.mailbox.index', [
                'account' => null, 'messages' => [], 'query' => (string) $request->query('q', ''),
                'connectionError' => null, 'settings' => MailServerSetting::current(),
            ]);
        }

        try {
            $settings = MailServerSetting::current();
            $messages = $this->client($account, $settings)->listMessages((string) $request->query('q', ''), 50);
            foreach ($messages as &$message) {
                $timestamp = strtotime((string) $message['date']);
                $message['jalali_date'] = $timestamp ? Jalalian::fromDateTime('@'.$timestamp)->format('Y/m/d H:i') : '—';
            }
            unset($message);
            return view('admin.mailbox.index', ['account' => $account, 'messages' => $messages, 'query' => (string) $request->query('q', ''), 'connectionError' => null, 'settings' => $settings]);
        } catch (Throwable $e) {
            Log::warning('Admin mailbox could not load inbox.', ['account_id' => $account->id, 'exception' => $e::class]);
            return view('admin.mailbox.index', [
                'account' => $account, 'messages' => [], 'query' => (string) $request->query('q', ''),
                'connectionError' => app()->hasDebugModeEnabled() ? $e->getMessage() : 'اتصال صندوق ایمیل برقرار نشد؛ میزبان و رمز صندوق را بررسی کنید.',
                'settings' => MailServerSetting::current(),
            ]);
        }
    }

    public function show(Request $request, int $uid)
    {
        $account = $this->selectedAccount($request);
        abort_unless($account, 404);
        try {
            $message = $this->client($account, MailServerSetting::current())->getMessage($uid);
            $timestamp = strtotime((string) $message['date']);
            $message['jalali_date'] = $timestamp ? Jalalian::fromDateTime('@'.$timestamp)->format('Y/m/d H:i') : '—';
            $message['account_id'] = $account->id;
            return view('admin.mailbox.show', compact('message', 'account'));
        } catch (Throwable $e) {
            Log::warning('Admin mailbox message could not be opened.', ['account_id' => $account->id, 'uid' => $uid, 'exception' => $e::class]);
            return redirect()->route('admin.mailbox.index', ['account' => $account->id])->with('error', 'بازکردن ایمیل ممکن نشد. اتصال سرور یا شناسه ایمیل را بررسی کنید.');
        }
    }

    public function settings()
    {
        $this->requireSuperAdmin();
        return view('admin.mailbox.settings', [
            'settings' => MailServerSetting::current(),
            'accounts' => MailAccount::with('adminUser')->orderBy('email')->get(),
            'admins' => AdminUser::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $this->requireSuperAdmin();
        $data = $request->validate([
            'imap_host' => ['required', 'string', 'max:190'],
            'imap_port' => ['required', 'integer', 'between:1,65535'],
            'smtp_host' => ['required', 'string', 'max:190'],
            'smtp_port' => ['required', 'integer', 'between:1,65535'],
            'smtp_encryption' => ['required', Rule::in(['tls', 'ssl'])],
        ]);
        $settings = MailServerSetting::current();
        $settings->fill($data)->save();
        return back()->with('success', 'تنظیمات اتصال ایمیل ذخیره شد.');
    }

    public function storeAccount(Request $request, MailboxProvisioner $provisioner)
    {
        $this->requireSuperAdmin();
        $data = $request->validate([
            'local_part' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9._+-]+$/'],
            'display_name' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:12', 'max:200'],
            'admin_user_id' => ['nullable', 'integer', 'exists:admin_users,id'],
        ]);
        $email = strtolower($data['local_part']).'@sabkhoneh.ir';
        validator(['email' => $email], ['email' => ['required', 'email:rfc', Rule::unique('mail_accounts', 'email')]])->validate();

        $account = MailAccount::create([
            'email' => $email, 'display_name' => $data['display_name'] ?? null,
            'password' => $data['password'], 'admin_user_id' => $data['admin_user_id'] ?? null, 'is_active' => true,
        ]);
        try {
            $provisioner->provision($email, $data['password']);
        } catch (Throwable $e) {
            $account->delete();
            return back()->withErrors(['mailbox' => $e->getMessage()])->withInput($request->except('password'));
        }
        return back()->with('success', 'صندوق '.$email.' ساخته و در صورت تنظیم ابزار سرور، آمادهٔ استفاده شد.');
    }

    public function assignAccount(Request $request, MailAccount $account)
    {
        $this->requireSuperAdmin();
        $data = $request->validate(['admin_user_id' => ['nullable', 'integer', 'exists:admin_users,id']]);
        $account->admin_user_id = $data['admin_user_id'] ?? null;
        $account->save();
        return back()->with('success', 'اختصاص صندوق به ادمین ذخیره شد.');
    }

    public function destroyAccount(MailAccount $account, MailboxProvisioner $provisioner)
    {
        $this->requireSuperAdmin();
        try {
            $provisioner->remove($account);
            $account->delete();
            return back()->with('success', 'صندوق حذف شد؛ پوشهٔ پیام‌ها در پشتیبان سرور نگهداری شده است.');
        } catch (Throwable $e) {
            Log::warning('Mailbox account deletion failed.', ['account_id' => $account->id, 'exception' => $e::class]);
            return back()->withErrors(['mailbox' => $e->getMessage()]);
        }
    }

    public function compose(Request $request)
    {
        $account = $this->selectedAccount($request);
        abort_unless($account, 404);
        return view('admin.mailbox.compose', compact('account'));
    }

    public function send(Request $request, MailAccount $account)
    {
        $this->assertCanUse($account);
        $data = $request->validate([
            'to' => ['required', 'email:rfc', 'max:254'],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:50000'],
        ]);

        $settings = MailServerSetting::current();
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $settings->smtp_host,
            'mail.mailers.smtp.port' => $settings->smtp_port,
            'mail.mailers.smtp.encryption' => $settings->smtp_encryption,
            'mail.mailers.smtp.username' => $account->email,
            'mail.mailers.smtp.password' => $account->password,
            'mail.mailers.smtp.timeout' => 15,
            'mail.from.address' => $account->email,
            'mail.from.name' => $account->display_name ?: 'صابخونه',
        ]);
        Mail::purge('smtp');
        try {
            Mail::mailer('smtp')->raw($data['body'], function ($message) use ($data) {
                $message->to($data['to'])->subject($data['subject']);
            });
        } catch (Throwable $e) {
            Log::warning('Admin mailbox email send failed.', ['account_id' => $account->id, 'exception' => $e::class]);
            return back()->withErrors(['send' => 'ارسال ایمیل ناموفق بود؛ تنظیمات SMTP و رمز صندوق را بررسی کنید.'])->withInput();
        }
        return redirect()->route('admin.mailbox.index', ['account' => $account->id])->with('success', 'ایمیل با موفقیت به سرویس SMTP تحویل داده شد.');
    }

    private function selectedAccount(Request $request): ?MailAccount
    {
        $admin = Auth::guard('admin')->user();
        $query = MailAccount::query()->where('is_active', true);
        if ($admin->is_super) {
            if ($request->filled('account')) $query->whereKey($request->integer('account'));
            else $query->orderBy('id');
        } else {
            $query->where('admin_user_id', $admin->id);
        }
        return $query->first();
    }

    private function client(MailAccount $account, MailServerSetting $settings): ImapMailbox
    {
        return new ImapMailbox([
            'host' => $settings->imap_host, 'port' => $settings->imap_port,
            'username' => $account->email, 'password' => $account->password, 'timeout' => 12,
        ]);
    }

    private function assertCanUse(MailAccount $account): void
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($account->is_active && ($admin->is_super || $account->admin_user_id === $admin->id), 403);
    }

    private function requireSuperAdmin(): void
    {
        abort_unless(Auth::guard('admin')->user()?->is_super, 403);
    }
}
