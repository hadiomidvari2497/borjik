<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Mailbox\ImapMailbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;
use Throwable;

class MailboxController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        try {
            $messages = app(ImapMailbox::class)->listMessages((string) $request->query('q', ''), 50);
            foreach ($messages as &$message) {
                $timestamp = strtotime((string) $message['date']);
                $message['jalali_date'] = $timestamp ? Jalalian::fromDateTime('@'.$timestamp)->format('Y/m/d H:i') : '—';
            }
            unset($message);
            return view('admin.mailbox.index', ['messages' => $messages, 'query' => (string) $request->query('q', ''), 'connectionError' => null]);
        } catch (Throwable $e) {
            Log::warning('Admin mailbox could not load inbox.', ['exception' => $e::class]);
            return view('admin.mailbox.index', [
                'messages' => [], 'query' => (string) $request->query('q', ''),
                'connectionError' => app()->hasDebugModeEnabled() ? $e->getMessage() : 'اتصال صندوق ایمیل برقرار نشد. تنظیمات MAILBOX را در محیط سرور بررسی کنید.',
            ]);
        }
    }

    public function show(int $uid)
    {
        try {
            $message = app(ImapMailbox::class)->getMessage($uid);
            $timestamp = strtotime((string) $message['date']);
            $message['jalali_date'] = $timestamp ? Jalalian::fromDateTime('@'.$timestamp)->format('Y/m/d H:i') : '—';
            return view('admin.mailbox.show', compact('message'));
        } catch (Throwable $e) {
            Log::warning('Admin mailbox message could not be opened.', ['uid' => $uid, 'exception' => $e::class]);
            return redirect()->route('admin.mailbox.index')->with('error', 'بازکردن ایمیل ممکن نشد. اتصال سرور یا شناسه ایمیل را بررسی کنید.');
        }
    }
}
