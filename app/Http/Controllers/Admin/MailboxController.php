<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailAccount;
use App\Models\MailServerSetting;
use App\Services\Mailbox\ImapMailbox;
use App\Services\Mailbox\MailboxProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Morilog\Jalali\Jalalian;
use Throwable;

class MailboxController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $account = $this->currentMailbox();
        if (! $account) {
            return view('admin.mailbox.index', ['account' => null, 'messages' => [], 'query' => (string) $request->query('q', ''), 'connectionError' => null]);
        }

        try {
            $messages = $this->client($account)->listMessages((string) $request->query('q', ''), 50);
            foreach ($messages as &$message) {
                $timestamp = strtotime((string) $message['date']);
                $message['jalali_date'] = $timestamp ? Jalalian::fromDateTime('@'.$timestamp)->format('Y/m/d H:i') : '—';
            }
            unset($message);
            return view('admin.mailbox.index', ['account' => $account, 'messages' => $messages, 'query' => (string) $request->query('q', ''), 'connectionError' => null]);
        } catch (Throwable $e) {
            Log::warning('Admin mailbox could not load inbox.', ['email' => $account->email, 'exception' => $e::class]);
            return view('admin.mailbox.index', ['account' => $account, 'messages' => [], 'query' => (string) $request->query('q', ''), 'connectionError' => app()->hasDebugModeEnabled() ? $e->getMessage() : 'اتصال صندوق ایمیل برقرار نشد؛ میزبان و رمز را بررسی کنید.']);
        }
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254', 'regex:/^[^@]+@sabkhoneh\.ir$/i'],
            'password' => ['required', 'string', 'max:200'],
        ]);
        $settings = MailServerSetting::current();
        try {
            // Authenticating against IMAP means existing server mailboxes can sign in
            // even if they were created outside this panel.
            $client = new ImapMailbox([
                'host' => $settings->imap_host, 'port' => $settings->imap_port,
                'username' => strtolower($data['email']), 'password' => $data['password'], 'timeout' => 12,
            ]);
            unset($client);
            $request->session()->put('admin_mailbox', [
                'email' => strtolower($data['email']),
                'password' => Crypt::encryptString($data['password']),
            ]);
            $request->session()->regenerate();
            return redirect()->route('admin.mailbox.index')->with('success', 'با موفقیت وارد صندوق ایمیل شدید.');
        } catch (Throwable $e) {
            Log::notice('Mailbox sign-in failed.', ['email' => strtolower($data['email']), 'exception' => $e::class]);
            return back()->withErrors(['mailbox_login' => 'ورود ناموفق بود؛ آدرس و رمز صندوق را بررسی کنید و مطمئن شوید صندوق روی سرور وجود دارد.'])->withInput($request->only('email'));
        }
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin_mailbox');
        return redirect()->route('admin.mailbox.index')->with('success', 'از صندوق ایمیل خارج شدید.');
    }

    public function show(Request $request, int $uid)
    {
        $account = $this->currentMailbox();
        abort_unless($account, 403, 'ابتدا با آدرس و رمز صندوق ایمیل وارد شوید.');
        try {
            $message = $this->client($account)->getMessage($uid);
            $timestamp = strtotime((string) $message['date']);
            $message['jalali_date'] = $timestamp ? Jalalian::fromDateTime('@'.$timestamp)->format('Y/m/d H:i') : '—';
            return view('admin.mailbox.show', compact('message', 'account'));
        } catch (Throwable $e) {
            Log::warning('Admin mailbox message could not be opened.', ['email' => $account->email, 'uid' => $uid, 'exception' => $e::class]);
            return redirect()->route('admin.mailbox.index')->withErrors(['mailbox' => 'بازکردن ایمیل ممکن نشد؛ اتصال یا شناسه ایمیل را بررسی کنید.']);
        }
    }

    public function settings()
    {
        $this->requireSuperAdmin();
        return view('admin.mailbox.settings', ['settings' => MailServerSetting::current(), 'accounts' => MailAccount::orderBy('email')->get()]);
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
        MailServerSetting::current()->fill($data)->save();
        return back()->with('success', 'تنظیمات اتصال ایمیل ذخیره شد.');
    }

    public function storeAccount(Request $request, MailboxProvisioner $provisioner)
    {
        $this->requireSuperAdmin();
        $data = $request->validate([
            'local_part' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9](?:[a-zA-Z0-9._+-]{0,62}[a-zA-Z0-9])?$/'],
            'display_name' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:12', 'max:200'],
        ]);
        $email = strtolower($data['local_part']).'@sabkhoneh.ir';
        validator(['email' => $email], ['email' => ['required', 'email:rfc', Rule::unique('mail_accounts', 'email')]])->validate();
        $account = MailAccount::create(['email' => $email, 'display_name' => $data['display_name'] ?? null, 'password' => $data['password'], 'is_active' => true]);
        try {
            $provisioner->provision($email, $data['password']);
        } catch (Throwable $e) {
            $account->delete();
            return back()->withErrors(['mailbox' => $e->getMessage()])->withInput($request->except('password'));
        }
        return back()->with('success', 'صندوق '.$email.' روی سرور ساخته شد. مدیر می‌تواند با همین آدرس و رمز وارد بخش ایمیل شود.');
    }

    public function destroyAccount(MailAccount $account, MailboxProvisioner $provisioner)
    {
        $this->requireSuperAdmin();
        try {
            $provisioner->remove($account);
            $account->delete();
            return back()->with('success', 'صندوق حذف شد؛ پوشهٔ پیام‌ها در پشتیبان سرور نگهداری شده است.');
        } catch (Throwable $e) {
            Log::warning('Mailbox account deletion failed.', ['email' => $account->email, 'exception' => $e::class]);
            return back()->withErrors(['mailbox' => $e->getMessage()]);
        }
    }

    public function compose()
    {
        $account = $this->currentMailbox();
        abort_unless($account, 403, 'ابتدا با آدرس و رمز صندوق ایمیل وارد شوید.');
        return view('admin.mailbox.compose', compact('account'));
    }

    public function send(Request $request)
    {
        $account = $this->currentMailbox();
        abort_unless($account, 403, 'ابتدا با آدرس و رمز صندوق ایمیل وارد شوید.');
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
            'mail.mailers.smtp.scheme' => $settings->smtp_encryption === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.username' => $account->email,
            'mail.mailers.smtp.password' => $account->password,
            'mail.mailers.smtp.timeout' => 15,
            'mail.from.address' => $account->email,
            'mail.from.name' => $account->display_name ?: $account->email,
        ]);
        Mail::purge('smtp');
        try {
            Mail::mailer('smtp')->raw($data['body'], function ($message) use ($data) {
                $message->to($data['to'])->subject($data['subject']);
            });
        } catch (Throwable $e) {
            Log::warning('Admin mailbox email send failed.', ['email' => $account->email, 'exception' => $e::class]);
            return back()->withErrors(['send' => 'ارسال ناموفق بود؛ تنظیمات SMTP و رمز صندوق را بررسی کنید.'])->withInput();
        }
        return redirect()->route('admin.mailbox.index')->with('success', 'ایمیل به سرویس SMTP تحویل داده شد.');
    }

    private function currentMailbox(): ?MailAccount
    {
        $credentials = session('admin_mailbox');
        if (! is_array($credentials) || empty($credentials['email']) || empty($credentials['password'])) return null;
        try {
            $password = Crypt::decryptString($credentials['password']);
        } catch (Throwable) {
            session()->forget('admin_mailbox');
            return null;
        }
        $account = new MailAccount();
        $account->setRawAttributes(['email' => $credentials['email'], 'password' => $password, 'display_name' => $credentials['email'], 'is_active' => true]);
        return $account;
    }

    private function client(MailAccount $account): ImapMailbox
    {
        $settings = MailServerSetting::current();
        return new ImapMailbox(['host' => $settings->imap_host, 'port' => $settings->imap_port, 'username' => $account->email, 'password' => $account->password, 'timeout' => 12]);
    }

    private function requireSuperAdmin(): void
    {
        abort_unless(Auth::guard('admin')->user()?->is_super, 403);
    }
}
