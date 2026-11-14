<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyListing extends Model
{
    public const LISTING_TYPES = [
        'sale' => 'فروش',
        'rent' => 'اجاره',
        'buy_request' => 'درخواست خرید',
        'rent_request' => 'درخواست اجاره',
    ];

    public const PROPERTY_TYPES = [
        'residential' => 'مسکونی',
        'commercial' => 'تجاری',
        'land' => 'زمین',
    ];

    protected $fillable = [
        'user_id', 'unit_id', 'latitude', 'longitude', 'location_visibility', 'parking_count', 'show_phone', 'allow_phone_call', 'allow_sms', 'listing_type', 'property_type', 'title', 'description',
        'province', 'city', 'neighborhood', 'address', 'hide_address', 'area',
        'bedrooms', 'bathrooms', 'floor', 'total_floors', 'built_year',
        'document_type', 'amenities', 'price', 'deposit', 'monthly_rent',
        'allow_exchange', 'photos', 'status', 'expires_at', 'moderation_note', 'moderated_by', 'moderated_at',
    ];

    protected function casts(): array
    {
        return [
            'hide_address' => 'boolean',
            'show_phone' => 'boolean',
            'allow_phone_call' => 'boolean',
            'allow_sms' => 'boolean',
            'allow_exchange' => 'boolean',
            'amenities' => 'array',
            'photos' => 'array',
            'expires_at' => 'datetime',
            'moderated_at' => 'datetime',
            'area' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (self $listing): void {
            foreach ($listing->tourRooms as $room) {
                \\Illuminate\\Support\\Facades\\Storage::disk('public')->delete($room->image_path);
            }
        });
    }

    public function tourRooms(): HasMany
    {
        return $this->hasMany(PropertyListingTourRoom::class, 'property_listing_id')->orderBy('sort_order')->orderBy('id');
    }

    public function inquiries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PropertyListingInquiry::class, 'property_listing_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function listingTypeLabel(): string
    {
        return self::LISTING_TYPES[$this->listing_type] ?? '';
    }

    public function propertyTypeLabel(): string
    {
        return self::PROPERTY_TYPES[$this->property_type] ?? '';
    }

    /** آدرس عمومی تصاویر آگهی به‌صورت آرایه‌ی مرتب. */
    public function photoUrls(): array
    {
        return collect($this->photos ?? [])
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->map(fn (string $path) => asset('storage/'.$path))
            ->values()
            ->all();
    }

    /**
     * قیمت آماده‌ی نمایش: آرایه‌ای از ردیف‌های برچسب/مقدار.
     * $short=true مبلغ را خلاصه می‌کند (مثلاً «۵ میلیارد تومان»).
     *
     * @return array<int, array{label: ?string, value: string}>
     */
    public function priceRows(bool $short = false): array
    {
        $fmt = fn ($n) => $short ? \App\Support\Fa::short((int) $n) : \App\Support\Fa::money((int) $n);

        if ($this->listing_type === 'rent') {
            $rows = [];
            if ($this->deposit) {
                $rows[] = ['label' => 'ودیعه', 'value' => $fmt($this->deposit)];
            }
            if ($this->monthly_rent) {
                $rows[] = ['label' => 'اجاره ماهانه', 'value' => $fmt($this->monthly_rent)];
            }

            return $rows ?: [['label' => null, 'value' => 'توافقی']];
        }

        $label = $this->listing_type === 'rent_request' ? 'بودجه' : null;
        if ($this->listing_type === 'rent_request' && ($this->deposit || $this->monthly_rent)) {
            $rows = [];
            if ($this->deposit) {
                $rows[] = ['label' => 'ودیعه', 'value' => $fmt($this->deposit)];
            }
            if ($this->monthly_rent) {
                $rows[] = ['label' => 'اجاره ماهانه', 'value' => $fmt($this->monthly_rent)];
            }

            return $rows;
        }

        return [['label' => $label, 'value' => $this->price ? $fmt($this->price) : 'توافقی']];
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
