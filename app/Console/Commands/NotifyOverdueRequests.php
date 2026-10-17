<?php

namespace App\Console\Commands;

use App\Models\Building;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\RequestAlertNotification;
use App\Support\CurrentBuilding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NotifyOverdueRequests extends Command
{
    protected $signature = 'requests:notify-overdue';

    protected $description = 'Notify relevant users about overdue building requests';

    public function handle(CurrentBuilding $current): int
    {
        $sent = 0;
        $today = now()->startOfDay();

        Building::query()->where('status', 'active')->orderBy('id')->each(function (Building $building) use ($current, &$sent, $today) {
            $current->actingAs((int) $building->id, function () use ($building, &$sent, $today) {
                Ticket::query()
                    ->where('kind', 'request')
                    ->whereNotNull('deadline')
                    ->where('deadline', '<', now())
                    ->whereNotIn('status', ['done', 'closed'])
                    ->with(['createdBy', 'acceptedBy'])
                    ->orderBy('id')
                    ->get()
                    ->each(function (Ticket $ticket) use ($building, &$sent, $today) {
                        $managerIds = DB::table('building_user_roles as bur')
                            ->join('roles as r', 'r.id', '=', 'bur.role_id')
                            ->where('bur.building_id', $building->id)
                            ->where('bur.status', 'active')
                            ->whereIn('r.key', ['owner', 'manager'])
                            ->pluck('bur.user_id');

                        $recipients = collect([$ticket->createdBy, $ticket->acceptedBy])
                            ->filter()
                            ->merge(User::query()->whereIn('id', $managerIds)->get())
                            ->unique('id');

                        foreach ($recipients as $recipient) {
                            $alreadySentToday = $recipient->notifications()
                                ->where('type', RequestAlertNotification::class)
                                ->where('data->building_id', $building->id)
                                ->where('data->ticket_id', $ticket->id)
                                ->where('data->event', 'overdue')
                                ->where('created_at', '>=', $today)
                                ->exists();

                            if ($alreadySentToday) {
                                continue;
                            }

                            $isCreator = (int) $recipient->id === (int) $ticket->created_by;
                            $url = $isCreator
                                ? route('my.requests.show', $ticket->id)
                                : route('requests.show', $ticket->id);

                            $recipient->notify(new RequestAlertNotification(
                                buildingId: (int) $building->id,
                                ticketId: (int) $ticket->id,
                                event: 'overdue',
                                title: 'درخواست از موعد رسیدگی گذشته است',
                                message: 'مهلت رسیدگی به درخواست «'.$ticket->title.'» به پایان رسیده است.',
                                url: $url,
                            ));
                            $sent++;
                        }
                    });
            });
        });

        $this->info("{$sent} overdue request notifications sent.");

        return self::SUCCESS;
    }
}
