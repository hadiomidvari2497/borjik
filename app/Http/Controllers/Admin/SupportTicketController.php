<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\SupportAttachment;
use App\Models\SupportTicket;
use App\Models\SupportTicketEvent;
use App\Models\User;
use App\Notifications\SupportTicketNotification;
use App\Support\SupportSla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SupportTicketController extends Controller
{
    private const STATUSES = ['open'=>'جدید','in_progress'=>'در حال بررسی','waiting_customer'=>'منتظر پاسخ مشتری','resolved'=>'حل‌شده','closed'=>'بسته'];
    private const PRIORITIES = ['low'=>'کم','normal'=>'عادی','high'=>'زیاد','urgent'=>'فوری'];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable','string','max:120'],
            'status' => ['nullable',Rule::in(array_keys(self::STATUSES))],
            'priority' => ['nullable',Rule::in(array_keys(self::PRIORITIES))],
            'sla' => ['nullable',Rule::in(['overdue','due_soon'])],
        ]);
        $term = trim((string) ($filters['q'] ?? ''));
        $query = SupportTicket::query()
            ->with(['user:id,first_name,last_name,company_name,mobile,person_type', 'building:id,name', 'assignedAdmin:id,name'])
            ->withCount('messages')
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($where) use ($term) {
                    $where->where('number', 'like', '%'.$term.'%')
                        ->orWhere('subject', 'like', '%'.$term.'%')
                        ->orWhereHas('user', function ($user) use ($term) {
                            $user->where('mobile', 'like', '%'.$term.'%')
                                ->orWhere('first_name', 'like', '%'.$term.'%')
                                ->orWhere('last_name', 'like', '%'.$term.'%')
                                ->orWhere('company_name', 'like', '%'.$term.'%');
                        });
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['priority'] ?? null, fn ($query, $value) => $query->where('priority', $value))
            ->when($filters['sla'] ?? null, function ($query, $value) {
                $query->whereIn('status', ['open', 'in_progress'])->where(function ($slaQuery) use ($value) {
                    if ($value === 'overdue') {
                        $slaQuery->where(function ($q) {
                            $q->whereNull('first_response_at')->whereNotNull('first_response_due_at')->where('first_response_due_at', '<=', now());
                        })->orWhere(function ($q) {
                            $q->whereNotNull('resolution_due_at')->where('resolution_due_at', '<=', now());
                        });
                    } else {
                        $limit = now()->addMinutes((int) config('support.sla.warning_minutes', 30));
                        $slaQuery->where(function ($q) use ($limit) {
                            $q->whereNull('first_response_at')->whereBetween('first_response_due_at', [now(), $limit]);
                        })->orWhere(function ($q) use ($limit) {
                            $q->whereBetween('resolution_due_at', [now(), $limit]);
                        });
                    }
                });
            })
            ->latest('updated_at');
        $stats = [
            'total'=>SupportTicket::count(),'open'=>SupportTicket::whereIn('status',['open','in_progress'])->count(),
            'waiting'=>SupportTicket::where('status','waiting_customer')->count(),
            'urgent'=>SupportTicket::where('priority','urgent')->whereNotIn('status',['resolved','closed'])->count(),
            'resolved'=>SupportTicket::whereIn('status',['resolved','closed'])->count(),
            'overdue_response'=>SupportTicket::whereIn('status',['open','in_progress'])->whereNull('first_response_at')->whereNotNull('first_response_due_at')->where('first_response_due_at','<=',now())->count(),
            'overdue_resolution'=>SupportTicket::whereIn('status',['open','in_progress'])->whereNotNull('resolution_due_at')->where('resolution_due_at','<=',now())->count(),
        ];
        $supportNotifications = $request->user('admin')->notifications()
            ->where('data->category', 'support')->latest()->limit(6)->get();

        return view('admin.support.index', [
            'tickets'=>$query->paginate(20)->withQueryString(),
            'filters'=>array_merge(['q'=>'','status'=>'','priority'=>'','sla'=>''],$filters),
            'statuses'=>self::STATUSES,'priorities'=>self::PRIORITIES,'stats'=>$stats,
            'supportNotifications'=>$supportNotifications,
        ]);
    }

    public function show(Request $request, int $ticket)
    {
        $request->user('admin')->unreadNotifications()
            ->where('data->category', 'support')->where('data->ticket_id', $ticket)->update(['read_at' => now()]);
        $ticket = SupportTicket::with(['user','building:id,name','assignedAdmin:id,name','messages'=>fn ($q) => $q->where('is_internal',false)->with('attachments')->orderBy('created_at'),'events'=>fn ($q) => $q->with(['actorAdmin:id,name','fromAdmin:id,name','toAdmin:id,name'])->orderBy('created_at')])->findOrFail($ticket);
        $admins = AdminUser::where('status','active')->orderBy('name')->get(['id','name']);
        return view('admin.support.show',['ticket'=>$ticket,'statuses'=>self::STATUSES,'priorities'=>self::PRIORITIES,'admins'=>$admins]);
    }

    public function reply(Request $request, int $ticket)
    {
        $data = $request->validate([
            'body'=>['required','string','min:2','max:10000'],
            'status'=>['nullable',Rule::in(['in_progress','waiting_customer','resolved','closed'])],
            'attachments'=>['nullable','array','max:5'],
            'attachments.*'=>['file','max:10240','mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt'],
        ]);
        $admin = $request->user('admin');
        $ticket = SupportTicket::findOrFail($ticket);
        DB::transaction(function () use ($ticket,$admin,$data,$request) {
            $wasFirstResponse = ! $ticket->first_response_at;
            $previousStatus = $ticket->status;
            $previousAssignee = $ticket->assigned_admin_id;
            $message = $ticket->messages()->create(['sender_type'=>'admin','sender_id'=>$admin->id,'body'=>trim($data['body'])]);
            $this->saveAttachments($request,$ticket,$message,'admin',(int)$admin->id);
            $status = $data['status'] ?? 'waiting_customer';
            $terminal = in_array($status,['resolved','closed'],true);
            $ticket->update([
                'status'=>$status,'assigned_admin_id'=>$ticket->assigned_admin_id ?: $admin->id,
                'resolved_by_admin_id'=>$terminal ? ($ticket->resolved_by_admin_id ?: $admin->id) : null,
                'last_reply_at'=>now(),
                'first_response_at'=>$ticket->first_response_at ?: now(),
                'first_response_breached_at'=>$wasFirstResponse && $ticket->first_response_due_at && now()->gt($ticket->first_response_due_at) ? ($ticket->first_response_breached_at ?: $ticket->first_response_due_at) : $ticket->first_response_breached_at,
                'resolution_breached_at'=>$terminal && $ticket->resolution_due_at && now()->gt($ticket->resolution_due_at) ? ($ticket->resolution_breached_at ?: $ticket->resolution_due_at) : $ticket->resolution_breached_at,
                'closed_at'=>$terminal ? now() : null,
            ]);
            if (! $previousAssignee && $ticket->assigned_admin_id) {
                SupportTicketEvent::create(['support_ticket_id'=>$ticket->id,'event_type'=>'assigned','actor_admin_id'=>$admin->id,'from_admin_id'=>null,'to_admin_id'=>$ticket->assigned_admin_id,'description'=>'تخصیص اولیه تیکت به کارشناس']);
            }
            SupportTicketEvent::create(['support_ticket_id'=>$ticket->id,'event_type'=>$wasFirstResponse ? 'first_response' : 'reply','actor_admin_id'=>$admin->id,'description'=>$wasFirstResponse ? 'ثبت اولین پاسخ توسط کارشناس' : 'ثبت پاسخ جدید توسط کارشناس']);
            if ($previousStatus !== $status) {
                SupportTicketEvent::create(['support_ticket_id'=>$ticket->id,'event_type'=>'status_changed','actor_admin_id'=>$admin->id,'from_status'=>$previousStatus,'to_status'=>$status,'description'=>'تغییر وضعیت تیکت']);
            }
            if (! in_array($previousStatus,['resolved','closed'],true) && $terminal) {
                SupportTicketEvent::create(['support_ticket_id'=>$ticket->id,'event_type'=>'resolved','actor_admin_id'=>$admin->id,'description'=>'پایان رسیدگی و حل تیکت توسط کارشناس']);
            }
        });
        $ticket->user->notify(new SupportTicketNotification($ticket,'پاسخ پشتیبانی','تیم پشتیبانی صابخونه به تیکت '.$ticket->number.' پاسخ داده است.',route('support.show',$ticket)));
        return redirect()->route('admin.support.show',$ticket)->with('success','پاسخ برای مشتری ثبت شد.');
    }

    public function update(Request $request, int $ticket)
    {
        $data = $request->validate([
            'status'=>['required',Rule::in(array_keys(self::STATUSES))],
            'priority'=>['required',Rule::in(array_keys(self::PRIORITIES))],
            'assigned_admin_id'=>['nullable',Rule::exists('admin_users','id')->where('status','active')],
        ]);
        $record = SupportTicket::findOrFail($ticket);
        $admin = $request->user('admin');
        $previousAssignee = $record->assigned_admin_id;
        $previousStatus = $record->status;
        $slaUpdates = [];
        if ($record->priority !== $data['priority']) {
            $slaUpdates = SupportSla::deadlines($data['priority']);
            $slaUpdates += [
                'first_response_warning_sent_at' => null,
                'first_response_breached_at' => null,
                'resolution_warning_sent_at' => null,
                'resolution_breached_at' => null,
            ];
        }
        $effectiveFirstResponseDue = $slaUpdates['first_response_due_at'] ?? $record->first_response_due_at;
        $effectiveResolutionDue = $slaUpdates['resolution_due_at'] ?? $record->resolution_due_at;
        $terminalStatus = in_array($data['status'], ['resolved', 'closed'], true);
        $record->update([
            ...$data,
            ...$slaUpdates,
            'assigned_admin_id'=>$data['assigned_admin_id'] ?? null,
            'resolved_by_admin_id'=>$terminalStatus ? ($record->resolved_by_admin_id ?: $admin->id) : null,
            'first_response_breached_at'=>!$record->first_response_at && $terminalStatus && $effectiveFirstResponseDue && now()->gt($effectiveFirstResponseDue) ? ($record->first_response_breached_at ?: $effectiveFirstResponseDue) : ($slaUpdates['first_response_breached_at'] ?? $record->first_response_breached_at),
            'resolution_breached_at'=>$terminalStatus && $effectiveResolutionDue && now()->gt($effectiveResolutionDue) ? ($record->resolution_breached_at ?: $effectiveResolutionDue) : ($slaUpdates['resolution_breached_at'] ?? $record->resolution_breached_at),
            'closed_at'=>$terminalStatus ? ($record->closed_at ?: now()) : null,
        ]);
        $newAssignee = $data['assigned_admin_id'] ?? null;
        if ((string) $previousAssignee !== (string) $newAssignee) {
            SupportTicketEvent::create(['support_ticket_id'=>$record->id,'event_type'=>'assigned','actor_admin_id'=>$admin->id,'from_admin_id'=>$previousAssignee,'to_admin_id'=>$newAssignee,'description'=>'تغییر مسئول رسیدگی تیکت']);
        }
        if ($previousStatus !== $data['status']) {
            SupportTicketEvent::create(['support_ticket_id'=>$record->id,'event_type'=>'status_changed','actor_admin_id'=>$admin->id,'from_status'=>$previousStatus,'to_status'=>$data['status'],'description'=>'تغییر وضعیت تیکت']);
            if (! in_array($previousStatus,['resolved','closed'],true) && $terminalStatus) {
                SupportTicketEvent::create(['support_ticket_id'=>$record->id,'event_type'=>'resolved','actor_admin_id'=>$admin->id,'description'=>'پایان رسیدگی و حل تیکت توسط کارشناس']);
            }
        }
        $record->user->notify(new SupportTicketNotification($record,'به‌روزرسانی تیکت','وضعیت درخواست '.$record->number.' به «'.self::STATUSES[$record->status].'» تغییر کرد.',route('support.show',$record)));
        return redirect()->route('admin.support.show',$record)->with('success','وضعیت، اولویت و مسئول رسیدگی ذخیره شد.');
    }

    public function download(Request $request, int $attachment)
    {
        $file = SupportAttachment::with('ticket')->findOrFail($attachment);
        abort_unless($request->user('admin')?->hasPermission('support.manage'),403);
        return $this->streamAttachment($file);
    }

    private function saveAttachments(Request $request, SupportTicket $ticket, $message, string $type, int $senderId): void
    {
        foreach ($request->file('attachments',[]) as $upload) {
            $path = $upload->store('support/'.$ticket->id,'local');
            $message->attachments()->create([
                'support_ticket_id'=>$ticket->id,'disk'=>'local','path'=>$path,
                'original_name'=>Str::limit($upload->getClientOriginalName(),250,''),
                'mime'=>$upload->getMimeType(),'size'=>$upload->getSize(),
                'uploader_type'=>$type,'uploader_id'=>$senderId,
            ]);
        }
    }

    private function streamAttachment(SupportAttachment $file)
    {
        abort_unless(Storage::disk($file->disk)->exists($file->path),404);
        return Storage::disk($file->disk)->download($file->path,$file->original_name,[
            'Content-Type'=>$file->mime ?: 'application/octet-stream',
            'X-Content-Type-Options'=>'nosniff','Cache-Control'=>'private, no-store',
        ]);
    }
}
