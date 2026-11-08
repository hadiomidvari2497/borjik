<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyListingChatAttachment extends Model
{
    /** فقط همین نوع‌ها داخل مرورگر نمایش داده می‌شوند؛ بقیه همیشه دانلود می‌شوند. */
    public const INLINE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    protected $fillable = ['inquiry_id', 'message_id', 'disk', 'path', 'original_name', 'mime', 'size'];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(PropertyListingInquiry::class, 'inquiry_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(PropertyListingChatMessage::class, 'message_id');
    }

    public function isImage(): bool
    {
        return in_array($this->mime, self::INLINE_MIMES, true);
    }

    /** نمایش حجم فایل به فارسی؛ مثال «۲٫۴ مگابایت». */
    public function sizeLabel(): string
    {
        $bytes = (int) $this->size;
        if ($bytes >= 1048576) {
            return \App\Support\Fa::digits(str_replace('.', '٫', rtrim(rtrim(number_format($bytes / 1048576, 1, '.', ''), '0'), '.'))).' مگابایت';
        }

        return \App\Support\Fa::digits(max(1, (int) round($bytes / 1024))).' کیلوبایت';
    }

    /** ساختار مشترک برای رندر سمت سرور و پاسخ‌های JSON. */
    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->original_name,
            'mime' => $this->mime,
            'is_image' => $this->isImage(),
            'size' => $this->sizeLabel(),
            'url' => route('properties.chat.attachment', [$this->inquiry_id, $this->id]),
        ];
    }
}
