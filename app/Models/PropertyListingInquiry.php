<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PropertyListingInquiry extends Model
{
    protected $fillable = [
        'property_listing_id', 'sender_user_id', 'recipient_user_id',
        'sender_name', 'sender_mobile', 'message', 'status', 'read_at',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(PropertyListingChatMessage::class, 'inquiry_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(PropertyListingChatMessage::class, 'inquiry_id')->latestOfMany();
    }

    /** نام طرف مقابل گفتگو از دید کاربر داده‌شده. */
    public function counterpartName(int $userId): string
    {
        return $userId === (int) $this->sender_user_id
            ? (string) ($this->recipient?->display_name ?? '')
            : (string) ($this->sender_name ?: $this->sender?->display_name);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    /** تعداد پیام‌های خوانده‌نشده‌ی کاربر در همه‌ی گفتگوهای آگهی (برای نشان منو). */
    public static function unreadCountFor(int $userId): int
    {
        $initial = static::query()->where('recipient_user_id', $userId)->whereNull('read_at')->count();
        $replies = PropertyListingChatMessage::query()
            ->whereNull('read_at')
            ->where('sender_user_id', '!=', $userId)
            ->whereHas('inquiry', fn ($q) => $q->where(fn ($w) => $w->where('recipient_user_id', $userId)->orWhere('sender_user_id', $userId)))
            ->count();

        return $initial + $replies;
    }
}
