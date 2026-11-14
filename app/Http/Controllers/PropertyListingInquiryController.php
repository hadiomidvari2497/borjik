<?php

namespace App\Http\Controllers;

use App\Models\PropertyListing;
use App\Models\PropertyListingChatAttachment;
use App\Models\PropertyListingInquiry;
use App\Notifications\PropertyListingNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PropertyListingInquiryController extends Controller
{
    public function store(Request $request, int $listingId)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:2000'],
            'share_mobile' => ['sometimes', 'accepted'],
        ]);

        $listing = PropertyListing::query()->published()->with('user')->findOrFail($listingId);
        abort_if((int) $listing->user_id === (int) $request->user()->id, 403);

        // Keep one conversation thread per listing and interested user.
        $inquiry = PropertyListingInquiry::query()
            ->where('property_listing_id', $listing->id)
            ->where('sender_user_id', $request->user()->id)
            ->where('recipient_user_id', $listing->user_id)
            ->latest('id')
            ->first();

        if ($inquiry) {
            if ($inquiry->status === 'closed') {
                $inquiry->forceFill(['status' => 'new', 'updated_at' => now()])->save();
                $inquiry->messages()->create([
                    'sender_user_id' => $request->user()->id,
                    'body' => trim($data['message']),
                ]);

                $listing->user->notify(new PropertyListingNotification(
                    event: 'inquiry_received',
                    title: 'گفتگوی آگهی دوباره فعال شد',
                    message: $request->user()->display_name.' دوباره درباره آگهی «'.$listing->title.'» پیام فرستاده است.',
                    url: route('properties.chat.show', $inquiry),
                    level: 'info',
                    listingId: $listing->id,
                ));
            }

            return redirect()->route('properties.chat.show', $inquiry)
                ->with('status', 'گفتگوی قبلی شما باز شد. می‌توانید ادامه پیام‌ها را همان‌جا ارسال کنید.');
        }

        $inquiry = PropertyListingInquiry::create([
            'property_listing_id' => $listing->id,
            'sender_user_id' => $request->user()->id,
            'recipient_user_id' => $listing->user_id,
            'sender_name' => $request->user()->display_name,
            'sender_mobile' => $request->boolean('share_mobile') ? $request->user()->mobile : null,
            'message' => $data['message'],
            'status' => 'new',
        ]);

        $listing->user->notify(new PropertyListingNotification(
            event: 'inquiry_received',
            title: 'پیام جدید درباره آگهی',
            message: $request->user()->display_name.' برای آگهی «'.$listing->title.'» پیام فرستاده است.',
            url: route('properties.chat.show', $inquiry),
            level: 'info',
            listingId: $listing->id,
        ));

        return redirect()->route('properties.chat.show', $inquiry)
            ->with('status', 'گفتگوی شما آغاز شد. می‌توانید پیام‌های بیشتری ارسال کنید.');
    }

    public function index(Request $request)
    {
        $userId = (int) $request->user()->id;
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['new', 'contacted', 'agreed', 'closed'])],
            'listing_id' => ['nullable', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);
        $ownedListings = PropertyListing::query()->where('user_id', $userId)->orderByDesc('created_at')->get(['id', 'title']);
        $ownedListingIds = $ownedListings->pluck('id')->all();
        if (! empty($filters['listing_id'])) {
            abort_unless(in_array((int) $filters['listing_id'], $ownedListingIds, true), 403);
        }

        $inquiries = PropertyListingInquiry::query()
            ->where(fn ($query) => $query->where('recipient_user_id', $userId)->orWhere('sender_user_id', $userId))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['listing_id']), fn ($query) => $query->where('recipient_user_id', $userId)->where('property_listing_id', (int) $filters['listing_id']))
            ->when(! empty($filters['q']), function ($query) use ($filters) {
                $term = trim($filters['q']);
                $query->where(function ($nested) use ($term) {
                    $nested->where('sender_name', 'like', '%'.$term.'%')
                        ->orWhereHas('listing', fn ($listing) => $listing->where('title', 'like', '%'.$term.'%'));
                });
            })
            ->with(['listing', 'sender', 'recipient', 'latestMessage.attachments'])
            ->withMax('messages', 'created_at')
            ->withCount(['messages as unread_messages_count' => fn ($query) => $query
                ->where('sender_user_id', '!=', $userId)
                ->whereNull('read_at')])
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        $statusLabels = ['new' => 'جدید', 'contacted' => 'در حال پیگیری', 'agreed' => 'توافق‌شده', 'closed' => 'بسته‌شده'];

        return view('properties.inquiries', compact('inquiries', 'ownedListings', 'filters', 'statusLabels'));
    }

    public function show(Request $request, PropertyListingInquiry $inquiry)
    {
        $this->authorizeParticipant($request, $inquiry);

        if ((int) $inquiry->recipient_user_id === (int) $request->user()->id) {
            $inquiry->forceFill(['read_at' => now()])->save();
        }

        $inquiry->messages()
            ->where('sender_user_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $inquiry->load(['listing', 'sender', 'recipient']);
        $messages = $inquiry->messages()->with(['sender', 'attachments', 'replyTo.sender'])->oldest()->get();

        return view('properties.chat', compact('inquiry', 'messages'));
    }

    public function send(Request $request, PropertyListingInquiry $inquiry)
    {
        $this->authorizeParticipant($request, $inquiry);
        abort_if($inquiry->status === 'closed', 403, 'این گفتگو بسته شده است.');

        $data = $request->validate([
            'body' => ['required_without:attachments', 'nullable', 'string', 'max:2000'],
            'reply_to_message_id' => ['nullable', 'integer', 'min:1'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf'],
        ], [
            'body.required_without' => 'پیام یا فایلی برای ارسال انتخاب کنید.',
            'attachments.max' => 'در هر پیام حداکثر ۵ فایل می‌توانید بفرستید.',
            'attachments.*.max' => 'حجم هر فایل باید کمتر از ۱۰ مگابایت باشد.',
            'attachments.*.mimes' => 'فقط تصویر (JPG، PNG، WebP) و فایل PDF قابل ارسال است.',
            'attachments.*.file' => 'ارسال فایل انجام نشد. دوباره تلاش کنید.',
            'attachments.*.uploaded' => 'آپلود فایل انجام نشد؛ حجم آن بیش از حد مجاز است.',
        ]);

        $replyTo = !empty($data['reply_to_message_id']) ? $inquiry->messages()->with('sender')->findOrFail($data['reply_to_message_id']) : null;
        $message = $inquiry->messages()->create([
            'sender_user_id' => $request->user()->id,
            'body' => trim((string) ($data['body'] ?? '')),
            'reply_to_message_id' => $replyTo?->id,
        ]);
        $message->load(['sender', 'replyTo.sender']);

        foreach ($request->file('attachments', []) as $upload) {
            $message->attachments()->create([
                'inquiry_id' => $inquiry->id,
                'disk' => 'local',
                'path' => $upload->store('property-chat/'.$inquiry->id, 'local'),
                'original_name' => Str::limit(preg_replace('/[\x00-\x1F\x7F]/u', '', $upload->getClientOriginalName()) ?: 'file', 200, ''),
                'mime' => $upload->getMimeType(),
                'size' => $upload->getSize(),
            ]);
        }
        $message->load('attachments');

        $recipientId = (int) $request->user()->id === (int) $inquiry->sender_user_id
            ? $inquiry->recipient_user_id
            : $inquiry->sender_user_id;
        $recipient = $recipientId === (int) $inquiry->recipient_user_id ? $inquiry->recipient : $inquiry->sender;

        $inquiry->forceFill(['updated_at' => now()])->save();
        $recipient->notify(new PropertyListingNotification(
            event: 'chat_message_received',
            title: 'پیام جدید در گفتگوی آگهی',
            message: $request->user()->display_name.($message->attachments->isNotEmpty() && $message->body === '' ? ' فایل جدیدی برای شما فرستاد.' : ' پیام جدیدی برای شما فرستاد.'),
            url: route('properties.chat.show', $inquiry),
            level: 'info',
            listingId: $inquiry->property_listing_id,
        ));

        if ($request->expectsJson()) {
            return response()->json(['message' => $message->toPayload($request->user()->display_name)], 201);
        }

        return redirect()->route('properties.chat.show', $inquiry)->with('status', 'پیام ارسال شد.');
    }

    public function messages(Request $request, PropertyListingInquiry $inquiry)
    {
        $this->authorizeParticipant($request, $inquiry);

        $afterId = max(0, (int) $request->query('after_id', 0));
        $inquiry->messages()
            ->where('sender_user_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $inquiry->messages()
            ->with(['sender', 'attachments', 'replyTo.sender'])
            ->where('id', '>', $afterId)
            ->oldest('id')
            ->limit(100)
            ->get();

        return response()->json([
            'messages' => $messages->map(fn ($message) => $message->toPayload())->values(),
            'read_message_ids' => $inquiry->messages()->where('sender_user_id', $request->user()->id)->whereNotNull('read_at')->pluck('id'),
            'initial_message_read' => (int) $inquiry->sender_user_id === (int) $request->user()->id && (bool) $inquiry->read_at,
            'closed' => $inquiry->status === 'closed',
        ]);
    }

    public function attachment(Request $request, PropertyListingInquiry $inquiry, int $attachment)
    {
        $this->authorizeParticipant($request, $inquiry);

        $file = PropertyListingChatAttachment::query()
            ->where('inquiry_id', $inquiry->id)
            ->findOrFail($attachment);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        // تصویرها برای نمایش داخل چت inline هستند و بقیه (PDF) فقط دانلود می‌شوند.
        return Storage::disk($file->disk)->response($file->path, $file->original_name, [
            'Content-Type' => $file->isImage() ? $file->mime : 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, max-age=600',
        ], $file->isImage() ? 'inline' : 'attachment');
    }

    public function update(Request $request, PropertyListingInquiry $inquiry)
    {
        abort_unless((int) $inquiry->recipient_user_id === (int) $request->user()->id, 403);

        $data = $request->validate([
            'status' => ['sometimes', 'required', Rule::in(['new', 'contacted', 'agreed', 'closed'])],
            'follow_up_at' => ['sometimes', 'nullable', 'date'],
            'follow_up_note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        if (array_key_exists('status', $data)) {
            $data['read_at'] = $inquiry->read_at ?? now();
        }

        $inquiry->update($data);

        return back()->with('status', array_key_exists('status', $data)
            ? 'وضعیت گفتگو به‌روزرسانی شد.'
            : 'یادآوری پیگیری ذخیره شد.');
    }

    private function authorizeParticipant(Request $request, PropertyListingInquiry $inquiry): void
    {
        abort_unless(
            in_array((int) $request->user()->id, [(int) $inquiry->sender_user_id, (int) $inquiry->recipient_user_id], true),
            403
        );
    }
}
