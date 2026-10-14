<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\InvitationException;
use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Invitation;
use App\Models\Role;
use App\Services\Auth\InvitationService;
use App\Support\CurrentBuilding;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    /** فهرست دعوت‌های باز برای کاربر واردشده؛ مستقل از ساختمان فعال. */
    public function index(Request $request)
    {
        $invitations = Invitation::withoutGlobalScopes()
            ->where('user_id', $request->user()->id)
            ->whereNull('accepted_at')
            ->whereNull('rejected_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        $invitations->each(function (Invitation $invitation) {
            $invitation->setRelation('building', Building::withoutGlobalScopes()->find($invitation->building_id));
            $invitation->setRelation('role', Role::find($invitation->role_id));
            $invitation->setRelation('createdBy', $invitation->createdBy()->withoutGlobalScopes()->first());
        });

        return view('invitations.index', compact('invitations'));
    }

    public function acceptExisting(Request $request, int $invitationId, InvitationService $service)
    {
        $invitation = Invitation::withoutGlobalScopes()->where('user_id', $request->user()->id)->findOrFail($invitationId);

        try {
            $service->acceptInvitation($invitation, $request->user());
        } catch (InvitationException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }

        $request->session()->put('current_building_id', $invitation->building_id);

        return redirect()->route('dashboard')->with('status', 'دعوت‌نامه پذیرفته شد و عضویت شما فعال شد.');
    }

    public function rejectExisting(Request $request, int $invitationId, InvitationService $service)
    {
        $invitation = Invitation::withoutGlobalScopes()->where('user_id', $request->user()->id)->findOrFail($invitationId);

        try {
            $service->reject($invitation, $request->user());
        } catch (InvitationException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }

        return redirect()->route('invitations.index')->with('status', 'دعوت‌نامه رد شد.');
    }

    /** صفحه‌ی لینک دعوت (برای مهمان و کاربر واردشده). */
    public function show(Request $request, string $token, InvitationService $service)
    {
        try {
            $invitation = $service->find($token);
        } catch (InvitationException $e) {
            return view('invite.show', ['error' => $e->getMessage()]);
        }

        if ($request->user() && $request->user()->mobile === $invitation->mobile) {
            $service->accept($token, $request->user());
            $request->session()->put('current_building_id', $invitation->building_id);

            return redirect()->route('dashboard');
        }

        return view('invite.show', [
            'token' => $token,
            'mobile' => $invitation->mobile,
            'building' => Building::withoutGlobalScopes()->find($invitation->building_id)?->name,
            'role' => Role::find($invitation->role_id)?->name_fa,
        ]);
    }

    /** ارسال دعوت جدید توسط مالک یا مدیر (داخل ساختمان فعال). */
    public function store(Request $request, InvitationService $service)
    {
        $data = $request->validate(['mobile' => ['required', 'string'], 'role' => ['required', 'string']]);
        $current = app(CurrentBuilding::class);

        $isStaffRole = in_array($data['role'], ['owner', 'manager', 'security', 'cleaning', 'maintenance'], true);
        $permission = $isStaffRole ? 'staff.manage' : 'member.manage';
        abort_unless($request->user()->can($permission), 403);
        abort_if(in_array($data['role'], ['owner', 'manager'], true) && ! $current->hasRole('owner'), 403);

        try {
            $service->invite($data['mobile'], $data['role'], $request->user());
        } catch (InvitationException $e) {
            return back()->withErrors(['mobile' => $e->getMessage()])->withInput();
        }

        return back()->with('status', 'دعوت‌نامه با پیامک ارسال شد.');
    }
}
