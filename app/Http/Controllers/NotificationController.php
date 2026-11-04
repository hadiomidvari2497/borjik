<?php

namespace App\Http\Controllers;

use App\Support\CurrentBuilding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** اعلان‌های درون‌سامانه‌ای کاربر در ساختمانِ فعال (بند ۴.۱۸): مشاهده، علامت‌گذاری به‌عنوان خوانده‌شده و تاریخچه. */
class NotificationController extends Controller
{
    public function index(Request $request, CurrentBuilding $current): View
    {
        $buildingId = $current->id();
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';
        $categories = [
            'invoice' => 'صورت‌حساب',
            'payment' => 'پرداخت',
            'request' => 'درخواست‌ها',
            'contract' => 'قرارداد',
            'expense' => 'هزینه‌ها',
            'system' => 'سایر',
            'support' => 'پشتیبانی',
            'marketplace' => 'بازارچه املاک',
        ];
        $category = array_key_exists((string) $request->query('category'), $categories)
            ? (string) $request->query('category')
            : '';

        $base = $request->user()->notifications()->where(function ($query) use ($buildingId) {
            $query->whereNull('data->building_id');
            if ($buildingId) {
                $query->orWhere('data->building_id', $buildingId);
            }
        });
        $query = clone $base;
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }
        if ($category !== '') {
            $query->where('data->category', $category);
        }
        $notifications = $query->latest()->paginate(20)->withQueryString();

        $unreadCount = $request->user()->unreadNotifications()
            ->where('data->building_id', $buildingId)
            ->count();
        $categoryCounts = [];
        foreach ($categories as $key => $label) {
            $categoryCounts[$key] = (clone $base)->where('data->category', $key)->count();
        }

        return view('notifications.index', compact('notifications', 'unreadCount', 'filter', 'category', 'categories', 'categoryCounts'));
    }

    public function read(Request $request, string $notification, CurrentBuilding $current): RedirectResponse
    {
        $buildingId = $current->id();
        $item = $request->user()->notifications()
            ->where(function ($query) use ($buildingId) {
                $query->whereNull('data->building_id');
                if ($buildingId) {
                    $query->orWhere('data->building_id', $buildingId);
                }
            })
            ->whereKey($notification)
            ->firstOrFail();

        if ($item->read_at === null) {
            $item->markAsRead();
        }

        $url = data_get($item->data, 'url');

        $host = is_string($url) ? parse_url($url, PHP_URL_HOST) : null;
        $scheme = is_string($url) ? parse_url($url, PHP_URL_SCHEME) : null;

        return $host === $request->getHost() && in_array($scheme, ['http', 'https'], true)
            ? redirect()->to($url)
            : redirect()->route('notifications.index');
    }

    /** همه‌ی اعلان‌های خوانده‌نشده‌ی همین ساختمان را خوانده‌شده می‌کند (ساختمان‌های دیگر دست‌نخورده می‌مانند). */
    public function readAll(Request $request, CurrentBuilding $current): RedirectResponse
    {
        $buildingId = $current->id();
        $query = $request->user()->unreadNotifications()->where(function ($scope) use ($buildingId) {
            $scope->whereNull('data->building_id');
            if ($buildingId) {
                $scope->orWhere('data->building_id', $buildingId);
            }
        });
        $categories = ['invoice', 'payment', 'request', 'contract', 'expense', 'system', 'support', 'marketplace'];
        $category = (string) $request->input('category', '');
        if (in_array($category, $categories, true)) {
            $query->where('data->category', $category);
        }
        $query->update(['read_at' => now()]);

        return redirect()->route('notifications.index', array_filter(['filter' => $request->input('filter'), 'category' => $category]))
            ->with('status', 'همه‌ی اعلان‌ها خوانده‌شده علامت خورد.');
    }
}
