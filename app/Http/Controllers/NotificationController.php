<?php

namespace App\Http\Controllers;

use App\Support\CurrentBuilding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, CurrentBuilding $current): View
    {
        $buildingId = $current->id();
        abort_unless($buildingId, 403);

        $notifications = $request->user()->notifications()
            ->where('data->building_id', $buildingId)
            ->latest()
            ->paginate(20);

        $unreadCount = $request->user()->unreadNotifications()
            ->where('data->building_id', $buildingId)
            ->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function read(Request $request, string $notification, CurrentBuilding $current): RedirectResponse
    {
        $buildingId = $current->id();
        abort_unless($buildingId, 403);

        $item = $request->user()->notifications()
            ->where('data->building_id', $buildingId)
            ->whereKey($notification)
            ->firstOrFail();

        if ($item->read_at === null) {
            $item->markAsRead();
        }

        $url = data_get($item->data, 'url');

        return $url && str_starts_with($url, url('/'))
            ? redirect()->to($url)
            : redirect()->route('notifications.index');
    }
}
