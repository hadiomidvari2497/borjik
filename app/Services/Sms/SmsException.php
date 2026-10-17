<?php

namespace App\Services\Sms;

/** خطای ارسال پیامک؛ پیام آن همیشه از مقادیر محرمانه پاک‌سازی شده و برای نمایش به ادمین امن است. */
class SmsException extends \RuntimeException
{
}
