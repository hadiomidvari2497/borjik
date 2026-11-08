<?php

namespace App\\Http\\Controllers;

use App\\Models\\PropertyListing;
use App\\Models\\PropertyListingInquiry;
use App\\Notifications\\PropertyListingNotification;
use Illuminate\\Http\\Request;
use Illuminate\\Validation\\Rule;

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
        $inquiries = PropertyListingInquiry::query()
            ->where(fn ($query) => $query->where('recipient_user_id', $userId)->orWhere('sender_user_id', $userId))
            ->with(['listing', 'sender', 'recipient'])
            ->withCount([
                'messages as unread_messages_count' => fn ($query) => $query
                    ->where('sender_user_id', '!=', $userId)
                    ->whereNull('read_at'),
            ])
            ->withMax('messages', 'created_at')
            ->latest('updated_at')
            ->paginate(15);

        return view('properties.inquiries', compact('inquiries'));
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
        $messages = $inquiry->messages()->with('sender')->oldest()->get();

        return view('properties.chat', compact('inquiry', 'messages'));
    }

    public function send(Request $request, PropertyListingInquiry $inquiry)
    {
        $this->authorizeParticipant($request, $inquiry);
        abort_if($inquiry->status === 'closed', 403, 'این گفتگو بسته شده است.');

        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $message = $inquiry->messages()->create([
            'sender_user_id' => $request->user()->id,
            'body' => trim($data['body']),
        ]);

        $recipientId = (int) $request->user()->id === (int) $inquiry->sender_user_id
            ? $inquiry->recipient_user_id
            : $inquiry->sender_user_id;
        $recipient = $recipientId === (int) $inquiry->recipient_user_id ? $inquiry->recipient : $inquiry->sender;

        $inquiry->forceFill(['updated_at' => now()])->save();
        $recipient->notify(new PropertyListingNotification(
            event: 'chat_message_received',
            title: 'پیام جدید در گفتگوی آگهی',
            message: $request->user()->display_name.' پیام جدیدی برای شما فرستاد.',
            url: route('properties.chat.show', $inquiry),
            level: 'info',
            listingId: $inquiry->property_listing_id,
        ));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'sender_user_id' => $message->sender_user_id,
                    'sender_name' => $request->user()->display_name,
                    'time' => \\App\\Support\\Fa::date($message->created_at, 'Y/m/d H:i'),
                ],
            ], 201);
        }

        return redirect()->route('properties.chat.show', $inquiry)->with('status', 'پیام ارسال شد.');
    }

    public function messages(Request $request, PropertyListingInquiry $inquiry)
    {
        $this->authorizeParticipant($request, $inquiry);

        $afterId = max(0, (int) $request->query('after_id', 0));
        $messages = $inquiry->messages()
            ->with('sender')
            ->where('id', '>', $afterId)
            ->oldest('id')
            ->limit(100)
            ->get();

        $messages->where('sender_user_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->each(fn ($message) => $message->forceFill(['read_at' => now()])->save());

        return response()->json([
            'messages' => $messages->map(fn ($message) => [
                'id' => $message->id,
                'body' => $message->body,
                'sender_user_id' => $message->sender_user_id,
                'sender_name' => $message->sender?->display_name ?? '',
                'time' => \\App\\Support\\Fa::date($message->created_at, 'Y/m/d H:i'),
            ])->values(),
            'closed' => $inquiry->status === 'closed',
        ]);
    }

    public function update(Request $request, PropertyListingInquiry $inquiry)
    {
        abort_unless((int) $inquiry->recipient_user_id === (int) $request->user()->id, 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'contacted', 'closed'])],
        ]);

        $inquiry->update(['status' => $data['status'], 'read_at' => $inquiry->read_at ?? now()]);

        return back()->with('status', 'وضعیت گفتگو به‌روزرسانی شد.');
    }

    private function authorizeParticipant(Request $request, PropertyListingInquiry $inquiry): void
    {
        abort_unless(
            in_array((int) $request->user()->id, [(int) $inquiry->sender_user_id, (int) $inquiry->recipient_user_id], true),
            403
        );
    }
}
