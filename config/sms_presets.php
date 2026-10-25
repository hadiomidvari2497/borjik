<?php

/**
 * نمونه‌های آماده برای پر کردن سریع فرم «اتصال پیامک». این‌ها فقط نقطهٔ شروع‌اند؛
 * مشخصات هر اپراتور را با مستندات خودش و با «تست ارسال» در پنل تأیید کنید.
 * متغیرها: {{mobile}} {{message}} و پارامترهای تعریف‌شده مثل {{api_key}} {{sender}}.
 */
return [
    'kavenegar' => [
        'label' => 'کاوه‌نگار',
        'note' => 'مشخصات از SDK رسمی کاوه‌نگار گرفته شده است. «کلید API» و «شماره خط فرستنده» را در پارامترها وارد کنید.',
        'data' => [
            'name' => 'کاوه‌نگار', 'method' => 'POST', 'number_format' => 'local',
            'url' => 'https://api.kavenegar.com/v1/{{api_key}}/sms/send.json',
            'body_type' => 'form', 'body' => "receptor={{mobile}}\nsender={{sender}}\nmessage={{message}}",
            'headers' => [],
            'params' => [
                ['name' => 'api_key', 'value' => '', 'secret' => true],
                ['name' => 'sender', 'value' => '', 'secret' => false],
            ],
            'success_json_path' => 'return.status', 'success_expected' => '200', 'success_contains' => '', 'ref_path' => 'entries.0.messageid',
        ],
    ],
    'melipayamak' => [
        'label' => 'ملی‌پیامک',
        'note' => 'درخواست طبق مستندات ملی‌پیامک است؛ پاسخ REST در مستندات مشخص نشده. بعد از «تست ارسال» پاسخ واقعی را ببینید و «شرط موفقیت» را بر اساس آن تنظیم کنید.',
        'data' => [
            'name' => 'ملی‌پیامک', 'method' => 'POST', 'number_format' => 'local',
            'url' => 'https://rest.payamak-panel.com/api/SendSMS/SendSMS',
            'body_type' => 'form', 'body' => "username={{username}}\npassword={{password}}\nto={{mobile}}\nfrom={{sender}}\ntext={{message}}",
            'headers' => [],
            'params' => [
                ['name' => 'username', 'value' => '', 'secret' => false],
                ['name' => 'password', 'value' => '', 'secret' => true],
                ['name' => 'sender', 'value' => '', 'secret' => false],
            ],
            'success_json_path' => '', 'success_expected' => '', 'success_contains' => '', 'ref_path' => '',
        ],
    ],
    'melipayamak_shared' => [
        'label' => 'ملی‌پیامک — خط خدماتی اشتراکی (الگو)',
        'note' => 'ارسال از خط خدماتی اشتراکی با الگوی تأییدشده. body_id را از پنل ملی‌پیامک بگیرید و متغیرهای الگو را به ترتیب در text[0]، text[1] و ... تعریف کنید. قبل از فعال‌سازی نهایی حتماً با شماره مجاز تست کنید.',
        'data' => [
            'name' => 'ملی‌پیامک خدماتی اشتراکی',
            'method' => 'GET',
            'number_format' => 'local',
            'url' => 'https://api.payamak-panel.com/post/Send.asmx/SendByBaseNumber',
            'body_type' => 'form',
            'body' => "username={{username}}\npassword={{password}}\ntext[0]={{message}}\nto={{mobile}}\nbodyId={{body_id}}",
            'headers' => [
                ['name' => 'Accept', 'value' => 'text/plain'],
            ],
            'params' => [
                ['name' => 'username', 'value' => '', 'secret' => false],
                ['name' => 'password', 'value' => '', 'secret' => true],
                ['name' => 'body_id', 'value' => '', 'secret' => false],
            ],
            'success_json_path' => '',
            'success_expected' => '',
            'success_contains' => '',
            'ref_path' => '',
        ],
    ],
    'smsir' => [
        'label' => 'SMS.ir',
        'note' => 'ساختار از SDK رسمی SMS.ir گرفته شده است. شماره خط را فقط با ارقام وارد کنید. شرط موفقیت پاسخ را بعد از «تست ارسال» تنظیم کنید.',
        'data' => [
            'name' => 'SMS.ir', 'method' => 'POST', 'number_format' => 'local',
            'url' => 'https://api.sms.ir/v1/send/bulk',
            'body_type' => 'json', 'body' => '{"lineNumber": {{sender}}, "MessageText": "{{message}}", "Mobiles": ["{{mobile}}"]}',
            'headers' => [
                ['name' => 'X-API-KEY', 'value' => '{{api_key}}'],
                ['name' => 'Accept', 'value' => 'application/json'],
            ],
            'params' => [
                ['name' => 'api_key', 'value' => '', 'secret' => true],
                ['name' => 'sender', 'value' => '', 'secret' => false],
            ],
            'success_json_path' => '', 'success_expected' => '', 'success_contains' => '', 'ref_path' => '',
        ],
    ],
];
