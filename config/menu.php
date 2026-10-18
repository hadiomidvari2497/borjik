<?php

/**
 * منوی کناری. هر آیتم فقط وقتی نمایش داده می‌شود که کاربر در ساختمان فعال حداقل یکی از مجوزهای «perm» را داشته باشد
 * (perm = null یعنی همیشه). اگر route هنوز تعریف نشده باشد لینک «#» می‌شود.
 * badge: کلیدی که MenuBuilder برایش شمارنده می‌سازد.
 */
return [
    [
        'title' => 'اصلی',
        'items' => [
            ['label' => 'داشبورد', 'icon' => 'grid', 'route' => 'dashboard', 'perm' => null, 'badge' => null],
            ['label' => 'ساختمان‌ها', 'icon' => 'bld', 'route' => 'buildings.index', 'perm' => 'building.manage', 'badge' => null],
            ['label' => 'بلوک‌ها', 'icon' => 'blk', 'route' => 'blocks.index', 'perm' => 'block.manage', 'badge' => null],
            ['label' => 'واحدها', 'icon' => 'grid', 'route' => 'units.index', 'perm' => ['unit.manage', 'unit.view'], 'badge' => null],
            ['label' => 'واحد من', 'icon' => 'grid', 'route' => 'my.units', 'perm' => 'unit.view.own', 'badge' => null],
            ['label' => 'پارکینگ‌ها', 'icon' => 'park', 'route' => 'parkings.index', 'perm' => 'parking.manage', 'badge' => null],
            ['label' => 'انبارها', 'icon' => 'box', 'route' => 'storages.index', 'perm' => 'storage.manage', 'badge' => null],
        ],
    ],
    [
        'title' => 'افراد و قراردادها',
        'items' => [
            ['label' => 'پرسنل', 'icon' => 'shield', 'route' => 'staff.index', 'perm' => 'staff.manage', 'badge' => null],
            ['label' => 'اعضای ساختمان', 'icon' => 'users', 'route' => 'members.index', 'perm' => 'member.manage', 'badge' => null],
            ['label' => 'قراردادها', 'icon' => 'doc', 'route' => 'contracts.index', 'perm' => 'contract.manage', 'badge' => null],
            ['label' => 'قرارداد من', 'icon' => 'doc', 'route' => 'my.contracts', 'perm' => 'contract.view.own', 'badge' => null],
        ],
    ],
    [
        'title' => 'مالی',
        'items' => [
            ['label' => 'هزینه‌های ماهانه', 'icon' => 'usd', 'route' => 'expenses.index', 'perm' => 'expense.manage', 'badge' => null],
            ['label' => 'محاسبه شارژ', 'icon' => 'coin', 'route' => 'charges.index', 'perm' => 'charge.calculate', 'badge' => null],
            ['label' => 'پرداخت‌ها', 'icon' => 'card', 'route' => 'payments.index', 'perm' => 'payment.review', 'badge' => 'payments'],
            ['label' => 'تنظیمات شارژ', 'icon' => 'set', 'route' => 'charge-settings.edit', 'perm' => 'charge.settings', 'badge' => null],
            ['label' => 'صورت‌حساب‌های من', 'icon' => 'coin', 'route' => 'my.invoices', 'perm' => 'invoice.view.own', 'badge' => null],
            ['label' => 'ثبت پرداخت', 'icon' => 'card', 'route' => 'my.payments', 'perm' => 'payment.submit', 'badge' => null],
        ],
    ],
    [
        'title' => 'عملیات',
        'items' => [
            ['label' => 'تیکت و تسک', 'icon' => 'kan', 'route' => 'tickets.index', 'perm' => ['ticket.view.all', 'ticket.view.assigned'], 'badge' => 'tickets'],
            ['label' => 'درخواست‌های ساکنین', 'icon' => 'inbox', 'route' => 'requests.index', 'perm' => ['request.view.all', 'request.view.domain'], 'badge' => 'requests'],
            ['label' => 'گزارش عملکرد درخواست‌ها', 'icon' => 'poll', 'route' => 'requests.reports', 'perm' => 'request.view.all', 'badge' => null],
            ['label' => 'درخواست‌های من', 'icon' => 'inbox', 'route' => 'my.requests', 'perm' => ['request.create', 'request.view.own'], 'badge' => null],
            ['label' => 'شیفت حراست', 'icon' => 'clk', 'route' => 'shifts.index', 'perm' => ['shift.manage', 'shift.view'], 'badge' => null],
            ['label' => 'موجودی ملزومات', 'icon' => 'box', 'route' => 'supplies.index', 'perm' => 'supply.manage', 'badge' => null],
        ],
    ],
    [
        'title' => 'ارتباطات',
        'items' => [
            ['label' => 'تقویم', 'icon' => 'cal', 'route' => 'events.index', 'perm' => ['event.manage', 'event.view'], 'badge' => null],
            ['label' => 'نظرسنجی', 'icon' => 'poll', 'route' => 'polls.index', 'perm' => ['poll.manage', 'poll.vote'], 'badge' => null],
            ['label' => 'اعلان‌ها', 'icon' => 'bell', 'route' => 'notifications.index', 'perm' => 'notification.view', 'badge' => 'notifications'],
            ['label' => 'قالب‌های پیامک', 'icon' => 'msg', 'route' => 'sms-templates.index', 'perm' => 'sms.templates', 'badge' => null],
        ],
    ],
    [
        'title' => 'بایگانی و گزارش',
        'items' => [
            ['label' => 'اسناد', 'icon' => 'folder', 'route' => 'documents.index', 'perm' => ['document.manage', 'document.view'], 'badge' => null],
            ['label' => 'دفترچه تلفن', 'icon' => 'book', 'route' => 'phonebook.index', 'perm' => ['phonebook.building', 'phonebook.personal'], 'badge' => null],
            ['label' => 'خروجی داده‌ها', 'icon' => 'dl', 'route' => 'export.index', 'perm' => 'export.data', 'badge' => null],
            ['label' => 'تاریخچه تغییرات', 'icon' => 'hist', 'route' => 'audit.index', 'perm' => 'audit.view', 'badge' => null],
        ],
    ],
    [
        'title' => 'حساب',
        'items' => [
            ['label' => 'اشتراک', 'icon' => 'crown', 'route' => 'subscription.show', 'perm' => 'subscription.manage', 'badge' => null],
            ['label' => 'تنظیمات ساختمان', 'icon' => 'set', 'route' => 'building.settings', 'perm' => 'building.manage', 'badge' => null],
        ],
    ],
];
