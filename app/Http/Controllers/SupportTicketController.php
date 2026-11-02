<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Support\CurrentBuilding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $tickets = SupportTicket::query()
            ->where('user_id', $request->user()->id)
            ->with('building:id,name')
            ->withCount('messages')
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

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
        ]);

        $ticket = DB::transaction(function () use ($data, $request, $current) {
            $ticket = SupportTicket::create([
                'user_id' => $request->user()->id,
                'building_id' => $current->id(),
                'subject' => trim($data['subject']),
                'category' => $data['category'],
                'priority' => 'normal',
                'status' => 'open',
                'last_reply_at' => now(),
            ]);
            $ticket->update(['number' => 'SP-'.now()->format('ym').'-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT)]);
            $ticket->messages()->create([
                'sender_type' => 'user',
                'sender_id' => $request->user()->id,
                'body' => trim($data['body']),
            ]);

            return $ticket;
        });

        return redirect()->route('support.show', $ticket)->with('success', 'درخواست پشتیبانی شما ثبت شد. شماره پیگیری: '.$ticket->number);
    }

    public function show(Request $request, int $ticket)
    {
        $ticket = SupportTicket::query()
            ->where('user_id', $request->user()->id)
            ->with(['building:id,name', 'messages' => fn ($query) => $query->where('is_internal', false)->orderBy('created_at')])
            ->findOrFail($ticket);

        return view('support.show', compact('ticket'));
    }

    public function reply(Request $request, int $ticket)
    {
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:10000']]);
        $ticket = SupportTicket::query()->where('user_id', $request->user()->id)->findOrFail($ticket);
        abort_if(in_array($ticket->status, ['resolved', 'closed'], true), 403, 'این درخواست بسته شده است.');

        DB::transaction(function () use ($ticket, $request, $data) {
            $ticket->messages()->create([
                'sender_type' => 'user',
                'sender_id' => $request->user()->id,
                'body' => trim($data['body']),
            ]);
            $ticket->update(['status' => 'open', 'last_reply_at' => now(), 'closed_at' => null]);
        });

        return redirect()->route('support.show', $ticket)->with('success', 'پاسخ شما ثبت شد.');
    }
}
