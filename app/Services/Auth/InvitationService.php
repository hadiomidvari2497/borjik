<?php

namespace App\Services\Auth;

use App\Enums\MembershipStatus;
use App\Exceptions\InvitationException;
use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use App\Services\Sms\SmsSender;
use App\Support\CurrentBuilding;
use App\Support\MobileNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * دعوت عضو یا پرسنل با پیامک (بند ۴.۸ و ۴.۹ سند).
 * invite() باید داخل context یک ساختمان (CurrentBuilding) صدا زده شود.
 */
class InvitationService
{
    public const TTL_DAYS = 7;

    public function __construct(private readonly SmsSender $sms)
    {
    }

    /** @return array{0: Invitation, 1: string} رکورد دعوت و توکن خام (فقط در پیامک می‌رود و ذخیره نمی‌شود) */
    public function invite(string $mobile, string $roleKey, User $inviter): array
    {
        $mobile = MobileNumber::normalize($mobile) ?? throw new InvitationException('شماره‌ی موبایل معتبر نیست.');
        $buildingId = app(CurrentBuilding::class)->id() ?? throw new InvitationException('ساختمان فعالی انتخاب نشده است.');
        $role = Role::where('key', $roleKey)->firstOr(fn () => throw new InvitationException('نقش نامعتبر است.'));

        $user = User::firstOrCreate(['mobile' => $mobile], ['status' => 'invited']);

        $membership = BuildingUserRole::where(['building_id' => $buildingId, 'user_id' => $user->id, 'role_id' => $role->id])->first();
        if ($membership?->status === MembershipStatus::Active) {
            throw new InvitationException('این شخص قبلاً با همین نقش عضو ساختمان است.');
        }

        $token = Str::random(40);

        $invitation = DB::transaction(function () use ($buildingId, $user, $role, $mobile, $inviter, $token) {
            BuildingUserRole::updateOrCreate(
                ['building_id' => $buildingId, 'user_id' => $user->id, 'role_id' => $role->id],
                ['status' => 'invited', 'invited_by' => $inviter->id, 'invited_at' => now()],
            );

            return Invitation::create([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'mobile' => $mobile,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(self::TTL_DAYS),
                'created_by' => $inviter->id,
            ]);
        });

        $this->sms->sendTemplate('invitation', $mobile, [
            'inviter' => $inviter->display_name,
            'building' => Building::find($buildingId)->name,
            'role' => $role->name_fa,
            'link' => route('invite.show', $token),
        ], $user->id, $buildingId);

        return [$invitation, $token];
    }

    /** دعوت معتبر را با توکن پیدا می‌کند (بدون نیاز به ساختمان فعال). */
    public function find(string $token): Invitation
    {
        $invitation = app(CurrentBuilding::class)->withoutScope(
            fn () => Invitation::where('token_hash', hash('sha256', $token))->first()
        );

        if (! $invitation || $invitation->accepted_at || $invitation->expires_at->isPast()) {
            throw new InvitationException('لینک دعوت نامعتبر یا منقضی شده است.');
        }

        return $invitation;
    }

    /** دعوت را برای کاربر واردشده می‌پذیرد؛ شماره‌ی کاربر باید همان شماره‌ی دعوت‌شده باشد. */
    public function accept(string $token, User $user): Invitation
    {
        $invitation = $this->find($token);

        if ($invitation->mobile !== $user->mobile) {
            throw new InvitationException('این دعوت برای شماره‌ی دیگری ارسال شده است.');
        }

        app(CurrentBuilding::class)->actingAs((int) $invitation->building_id, function () use ($invitation, $user) {
            DB::transaction(function () use ($invitation, $user) {
                $invitation->forceFill(['accepted_at' => now()])->save();

                // همه‌ی نقش‌های «دعوت‌شده»‌ی این شخص در این ساختمان فعال می‌شود (ممکن است بعد از دعوت نقش دیگری هم اضافه شده باشد)
                BuildingUserRole::where(['building_id' => $invitation->building_id, 'user_id' => $user->id, 'status' => 'invited'])
                    ->update(['status' => 'active', 'joined_at' => now()]);

                if ($user->status === 'invited') {
                    $user->update(['status' => 'active']);
                }
            });
        });

        return $invitation;
    }
}
