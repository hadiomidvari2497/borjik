<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyListingChatMessage extends Model
{
    protected $fillable = ['inquiry_id', 'sender_user_id', 'body', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(PropertyListingInquiry::class, 'inquiry_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PropertyListingChatAttachment::class, 'message_id');
    }

    /** ساختار مشترک پیام برای پاسخ‌های JSON (ارسال و دریافت پیام‌های جدید). */
    public function toPayload(?string $senderName = null): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'sender_user_id' => $this->sender_user_id,
            'sender_name' => $senderName ?? $this->sender?->display_name ?? '',
            'time' => \App\Support\Fa::date($this->created_at, 'Y/m/d H:i'),
            'attachments' => $this->attachments->map->toPayload()->values()->all(),
        ];
    }
}
