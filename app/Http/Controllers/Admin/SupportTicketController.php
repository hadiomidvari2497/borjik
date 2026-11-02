<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\SupportAttachment;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketNotification;
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
        ]);
        $term = trim((string) ($filters['q'] ?? ''));
        $query = SupportTicket::query()->with(['user:id,first_name,last_name,company_name,mobile,person_type','building:id,name','assignedAdmin:id,name'])
            ->withCount('messages')
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('number','like','%'.$term.'%')->orWhere('subject','like','%'.$term.'%')
                ->orWhereHas('user',fn ($u) => $u->where('mobile','like','%'.$term.'%')->orWhere('first_name','like','%'.$term.'%')->orWhere('last_name','like','%'.$term.'%')->orWhere('company_name','like','%'.$term.'%')))
            ->when($filters['status'] ?? null, fn ($q,$v) => $q->where('status',$v))
            ->when($filters['priority'] ?? null, fn ($q,$v) => $q->where('priority',$v))->latest('updated_at');
        $stats = [
            'total'=>SupportTicket::count(),'open'=>SupportTicket::whereIn('status',['open','in_progress'])->count(),
            'waiting'=>SupportTicket::where('status','waiting_customer')->count(),
            'urgent'=>SupportTicket::where('priority','urgent')->whereNotIn('status',['resolved','closed'])->count(),
            'resolved'=>SupportTicket::whereIn('status',['resolved','closed'])->count(),
        ];
        $supportNotifications = $request->user('admin')->notifications()
            ->where('data->category', 'support')->latest()->limit(6)->get();

        return view('admin.support.index', [
            'tickets'=>$query->paginate(20)->withQueryString(),
            'filters'=>array_merge(['q'=>'','status'=>'','priority'=>''],$filters),
            'statuses'=>self::STATUSES,'priorities'=>self::PRIORITIES,'stats'=>$stats,
            'supportNotifications'=>$supportNotifications,
        ]);
    }

    public function show(Request $request, int $ticket)
    {
        $request->user('admin')->unreadNotifications()
            ->where('data->category', 'support')->where('data->ticket_id', $ticket)->update(['read_at' => now()]);
        $ticket = SupportTicket::with(['user','building:id,name','assignedAdmin:id,name','messages'=>fn ($q) => $q->where('is_internal',false)->with('attachments')->orderBy('created_at')])->findOrFail($ticket);
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
            $message = $ticket->messages()->create(['sender_type'=>'admin','sender_id'=>$admin->id,'body'=>trim($data['body'])]);
            $this->saveAttachments($request,$ticket,$message,'admin',(int)$admin->id);
            $status = $data['status'] ?? 'waiting_customer';
            $ticket->update([
                'status'=>$status,'assigned_admin_id'=>$ticket->assigned_admin_id ?: $admin->id,
                'last_reply_at'=>now(),'closed_at'=>in_array($status,['resolved','closed'],true) ? now() : null,
            ]);
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
        $record->update([
            ...$data,
            'assigned_admin_id'=>$data['assigned_admin_id'] ?? null,
            'closed_at'=>in_array($data['status'],['resolved','closed'],true) ? ($record->closed_at ?: now()) : null,
        ]);
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
