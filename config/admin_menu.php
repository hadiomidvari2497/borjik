<?php

/**
 * منوی کناری پنل مدیریت سیستم. هر آیتم فقط وقتی دیده می‌شود که ادمین یکی از مجوزهای «perm» را داشته باشد
 * (perm = null یعنی برای همه‌ی ادمین‌ها). آیتمی که route آن هنوز تعریف نشده نمایش داده نمی‌شود؛
 * با اضافه‌شدن هر بخش جدید فقط یک خط به همین فایل اضافه می‌شود.
 * desc: توضیح کوتاه برای کارت «دسترسی سریع» داشبورد (اختیاری).
 */
return [
    [
        'title' => 'اصلی',
        'items' => [
            ['label' => 'داشبورد', 'icon' => 'grid', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'perm' => null],
        ],
    ],
    [
        'title' => 'اشتراک و پرداخت',
        'items' => [
            ['label' => 'پلن‌های اشتراک', 'icon' => 'crown', 'route' => 'admin.plans.index', 'active' => 'admin.plans.*', 'perm' => 'plans.manage',
                'desc' => 'قیمت، مدت و سقف واحد پلن‌ها'],
        ],
    ],
    [
        'title' => 'پیامک',
        'items' => [
            ['label' => 'اتصال پیامک', 'icon' => 'msg', 'route' => 'admin.sms.providers.index', 'active' => 'admin.sms.providers.*', 'perm' => 'sms.settings',
                'desc' => 'اتصال به هر اپراتور پیامکی و تست ارسال'],
            ['label' => 'آرشیو پیامک', 'icon' => 'inbox', 'route' => 'admin.sms.logs.index', 'active' => 'admin.sms.logs.*', 'perm' => 'sms.logs',
                'desc' => 'پیامک‌های ارسال‌شده به تفکیک تاریخ'],
        ],
    ],
    [
        'title' => 'ادمین‌ها و دسترسی‌ها',
        'items' => [
            ['label' => 'کاربران ادمین', 'icon' => 'shield', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'perm' => 'admin.users.manage',
                'desc' => 'ساخت و مدیریت کاربران پنل مدیریت'],
            ['label' => 'نقش‌ها و دسترسی‌ها', 'icon' => 'lock', 'route' => 'admin.roles.index', 'active' => 'admin.roles.*', 'perm' => 'admin.roles.manage',
                'desc' => 'تعریف نقش و سطح دسترسی هر بخش'],
        ],
    ],
    [
        'title' => 'تنظیمات',
        'items' => [
            ['label' => 'تنظیمات سایت', 'icon' => 'set', 'route' => 'admin.settings.edit', 'active' => 'admin.settings.*', 'perm' => 'site.settings',
                'desc' => 'روش‌های ورود، نام سایت و پشتیبانی'],
            ['label' => 'حساب من', 'icon' => 'users', 'route' => 'admin.account', 'active' => 'admin.account*', 'perm' => null,
                'desc' => 'رمز عبور و تأیید دومرحله‌ای'],
        ],
    ],
];
