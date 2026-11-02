<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupportTicketController extends Controller
{
    private const STATUSES = [
        'open' => 'جدید',
        'in_progress' => 'در حال بررسی',
        'waiting_customer' => 'منتظر پاسخ مشتری',
        'resolved' => 'حل‌شده',
        'closed' => 'بسته',
    ];

    private const PRIORITIES = [
        'low' => 'کم',
        'normal' => 'عادی',
        'high' => 'زیاد',
        'urgent' => 'فوری',
    ];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_keys(self::STATUSES))],
            'priority' => ['nullable', Rule::in(array_keys(self::PRIORITIES))],
        ]);
        $term = trim((string) ($filters['q'] ?? ''));
        $ticketsQuery = SupportTicket::query()
            ->with(['user:id,first_name,last_name,company_name,mobile,person_type', 'building:id,name', 'assignedAdmin:id,name'])
            ->withCount('messages')
            // Keep all ticket search terms grouped so filters remain conjunctive.
            ->when($term !== '', fn ($query) => $query->where(function ($where) use ($term) {
                $where->where('number', 'like', '%'.$term.'%')
                    ->orWhere('subject', 'like', '%'.$term.'%')
                    ->orWhereHas('user', fn ($user) => $user->where('mobile', 'like', '%'.$term.'%')
                        ->orWhere('first_name', 'like', '%'.$term.'%')
                        ->orWhere('last_name', 'like', '%'.$term.'%')
                        ->orWhere('company_name', 'like', '%'.$term.'%'));
            }))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('priority', $priority))
            ->latest('updated_at');

        $stats = [
            'total' => SupportTicket::count(),
            'open' => SupportTicket::whereIn('status', ['open', 'in_progress'])->count(),
            'waiting' => SupportTicket::where('status', 'waiting_customer')->count(),
            'urgent' => SupportTicket::where('priority', 'urgent')->whereNotIn('status', ['resolved', 'closed'])->count(),
            'resolved' => SupportTicket::whereIn('status', ['resolved', 'closed'])->count(),
        ];

        return view('admin.support.index', [
            'tickets' => $ticketsQuery->paginate(20)->withQueryString(),
            'filters' => array_merge(['q' => '', 'status' => '', 'priority' => ''], $filters),
            'statuses' => self::STATUSES,
            'priorities' => self::PRIORITIES,
            'stats' => $stats,
        ]);
    }

    public function show(int $ticket)
    {
        $ticket = SupportTicket::query()
            ->with(['user', 'building:id,name', 'assignedAdmin:id,name', 'messages' => fn ($query) => $query->where('is_internal', false)->orderBy('created_at')])
            ->findOrFail($ticket);

        return view('admin.support.show', [
            'ticket' => $ticket,
            'statuses' => self::STATUSES,
            'priorities' => self::PRIORITIES,
        ]);
    }

    public function reply(Request $request, int $ticket)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:10000'],
            'status' => ['nullable', Rule::in(['in_progress', 'waiting_customer', 'resolved', 'closed'])],
        ]);
        $admin = $request->user('admin');
        $ticket = SupportTicket::findOrFail($ticket);

        DB::transaction(function () use ($ticket, $admin, $data) {
            $ticket->messages()->create([
                'sender_type' => 'admin',
                'sender_id' => $admin->id,
                'body' => trim($data['body']),
            ]);
            $status = $data['status'] ?? 'waiting_customer';
            $ticket->update([
                'status' => $status,
                'assigned_admin_id' => $ticket->assigned_admin_id ?: $admin->id,
                'last_reply_at' => now(),
                'closed_at' => in_array($status, ['resolved', 'closed'], true) ? now() : null,
            ]);
        });

        return redirect()->route('admin.support.show', $ticket)->with('success', 'پاسخ برای مشتری ثبت شد.');
    }

    public function update(Request $request, int $ticket)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(self::PRIORITIES))],
        ]);
        $record = SupportTicket::findOrFail($ticket);
        $record->update([
            ...$data,
            'assigned_admin_id' => $record->assigned_admin_id ?: $request->user('admin')->id,
            'closed_at' => in_array($data['status'], ['resolved', 'closed'], true) ? ($record->closed_at ?: now()) : null,
        ]);

        return redirect()->route('admin.support.show', $record)->with('success', 'وضعیت و اولویت درخواست به‌روزرسانی شد.');
    }
}
