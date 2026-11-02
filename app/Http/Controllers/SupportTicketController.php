<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Notifications\SupportTicketNotification;
use App\Support\CurrentBuilding;
use App\Support\SupportSla;
use App\Support\SupportTicketIntelligence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SupportTicketController extends Controller
{
    private const CATEGORIES = [
        'general' => 'پرسش عمومی',
        'technical' => 'مشکل فنی',
        'billing' => 'اشتراک و پرداخت',
        'account' => 'حساب کاربری',
        'suggestion' => 'پیشنهاد و بازخورد',
    ];

    public function index(Request $request)
    {
        $tickets = SupportTicket::query()->where('user_id', $request->user()->id)
            ->with('building:id,name')->withCount(['messages' => fn ($query) => $query->where('is_internal', false)])->latest('updated_at')->paginate(15)->withQueryString();

        return view('support.index', ['tickets' => $tickets, 'categories' => self::CATEGORIES]);
    }

    public function create()
    {
        return view('support.create', ['categories' => self::CATEGORIES]);
    }

    public function store(Request $request, CurrentBuilding $current)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'min:5', 'max:180'],
            'category' => ['required', Rule::in(array_keys(self::CATEGORIES))],
            'body' => ['required', 'string', 'min:15', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt'],
        ]);

        $ticket = DB::transaction(function () use ($data, $request, $current) {
            $ticket = SupportTicket::create([
                'user_id' => $request->user()->id,
                'building_id' => $current->id(),
                'number' => 'TMP'.Str::upper(Str::random(18)),
                'subject' => trim($data['subject']),
                'category' => $data['category'],
                'priority' => 'normal',
                'status' => 'open',
                'last_reply_at' => now(),
                ...SupportSla::deadlines('normal'),
            ]);
            $ticket->update(['number' => 'SP-'.now()->format('ym').'-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT)]);
            $message = $ticket->messages()->create([
                'sender_type' => 'user', 'sender_id' => $request->user()->id, 'body' => trim($data['body']),
            ]);
            $this->saveAttachments($request, $ticket, $message, 'user', (int) $request->user()->id);
            $ticket->update(app(SupportTicketIntelligence::class)->analyze($ticket));
            return $ticket->fresh();
        });

        $this->notifySupportAdmins($ticket, 'تیکت جدید', 'یک درخواست پشتیبانی جدید ثبت شده است.');

        return redirect()->route('support.show', $ticket)->with('success', 'درخواست پشتیبانی شما ثبت شد. شماره پیگیری: '.$ticket->number);
    }

    public function show(Request $request, int $ticket)
    {
        $ticket = SupportTicket::query()->where('user_id', $request->user()->id)
            ->with(['building:id,name', 'messages' => fn ($q) => $q->where('is_internal', false)->with('attachments')->orderBy('created_at')])
            ->findOrFail($ticket);

        return view('support.show', compact('ticket'));
    }

    public function reply(Request $request, int $ticket)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt'],
        ]);
        $ticket = SupportTicket::query()->where('user_id', $request->user()->id)->findOrFail($ticket);
        abort_if(in_array($ticket->status, ['resolved', 'closed'], true), 403, 'این درخواست بسته شده است.');

        DB::transaction(function () use ($ticket, $request, $data) {
            $message = $ticket->messages()->create([
                'sender_type' => 'user', 'sender_id' => $request->user()->id, 'body' => trim($data['body']),
            ]);
            $this->saveAttachments($request, $ticket, $message, 'user', (int) $request->user()->id);
            $ticket->update([
                'status' => 'open',
                'last_reply_at' => now(),
                'closed_at' => null,
                'resolution_due_at' => SupportSla::resolutionDeadline($ticket->priority),
                'resolution_warning_sent_at' => null,
                'resolution_breached_at' => null,
            ]);
        });

        $this->notifySupportAdmins($ticket, 'پاسخ جدید مشتری', 'مشتری در تیکت '.$ticket->number.' پیام جدیدی ارسال کرده است.');

        return redirect()->route('support.show', $ticket)->with('success', 'پاسخ شما ثبت شد.');
    }

    public function feedback(Request $request, int $ticket)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $record = DB::transaction(function () use ($request, $ticket, $data) {
            $record = SupportTicket::query()
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->findOrFail($ticket);

            abort_unless(in_array($record->status, ['resolved', 'closed'], true), 403, 'امتیازدهی پس از پایان رسیدگی امکان‌پذیر است.');
            abort_if($record->satisfaction_submitted_at !== null, 409, 'قبلاً برای این درخواست امتیاز ثبت کرده‌اید.');

            $record->update([
                'satisfaction_rating' => (int) $data['rating'],
                'satisfaction_comment' => isset($data['comment']) ? trim($data['comment']) : null,
                'satisfaction_submitted_at' => now(),
            ]);

            return $record;
        });

        return redirect()->route('support.show', $record)->with('success', 'از ثبت نظر و ارزیابی شما سپاسگزاریم.');
    }

    public function download(Request $request, int $attachment)
    {
        $file = SupportAttachment::with('ticket')->findOrFail($attachment);
        abort_unless((int) $file->ticket->user_id === (int) $request->user()->id, 404);
        return $this->streamAttachment($file);
    }

    private function saveAttachments(Request $request, SupportTicket $ticket, SupportMessage $message, string $type, int $senderId): void
    {
        foreach ($request->file('attachments', []) as $upload) {
            $path = $upload->store('support/'.$ticket->id, 'local');
            $message->attachments()->create([
                'support_ticket_id' => $ticket->id, 'disk' => 'local', 'path' => $path,
                'original_name' => Str::limit($upload->getClientOriginalName(), 250, ''),
                'mime' => $upload->getMimeType(), 'size' => $upload->getSize(),
                'uploader_type' => $type, 'uploader_id' => $senderId,
            ]);
        }
    }

    private function notifySupportAdmins(SupportTicket $ticket, string $title, string $message): void
    {
        $admins = $ticket->assigned_admin_id
            ? AdminUser::whereKey($ticket->assigned_admin_id)->get()
            : AdminUser::where('status', 'active')->get()->filter(fn ($admin) => $admin->hasPermission('support.manage'));
        foreach ($admins as $admin) {
            $admin->notify(new SupportTicketNotification($ticket, $title, $message, route('admin.support.show', $ticket)));
        }
    }

    private function streamAttachment(SupportAttachment $file)
    {
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);
        return Storage::disk($file->disk)->download($file->path, $file->original_name, [
            'Content-Type' => $file->mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
