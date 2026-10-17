<?php

return [
    // log = فقط در لاگ می‌نویسد (توسعه). برای کاوه‌نگار، ملی‌پیامک و ... کلاسی بنویس که SmsGateway را پیاده کند.
    'driver' => env('SMS_DRIVER', 'log'),

    // پیامک‌هایی که متن‌شان در sms_logs نباید ذخیره شود (کد یکبارمصرف).
    'sensitive' => ['otp'],

    // متن پیش‌فرض قالب‌ها؛ با رکورد sms_templates (سیستمی یا مخصوص ساختمان) قابل جایگزینی است.
    'templates' => [
        'otp' => 'برجیک: کد ورود شما {code} است. اعتبار ۲ دقیقه.',
        'expense_reminder' => 'برجیک: هزینه‌های {month} ساختمان «{building}» هنوز ثبت نشده است. تاریخ محاسبه‌ی شارژ: {date}',
        'receipt_approved' => 'برجیک: فیش پرداخت شما به مبلغ {amount} تومان برای واحد {unit} ساختمان «{building}» تأیید شد.',
        'receipt_rejected' => 'برجیک: فیش پرداخت شما برای واحد {unit} ساختمان «{building}» رد شد. دلیل: {reason}',
        'rent_invoice_issued' => 'برجیک: صورت‌حساب اجاره‌ی {month} واحد {unit} ساختمان «{building}» به مبلغ {amount} تومان صادر شد. مهلت پرداخت: {date}',
        'invitation' => 'شما از طرف {inviter} به ساختمان «{building}» با نقش {role} دعوت شده‌اید. ورود: {link}',
    ],
];
