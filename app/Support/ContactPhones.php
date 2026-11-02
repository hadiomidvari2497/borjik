<?php

namespace App\Support;

/**
 * شماره‌های تماس بخش «تماس با ما» در صفحهٔ اصلی.
 *
 * ساختار جدید: contact.phones = [['label' => 'پشتیبانی', 'number' => '021-1234'], ...]
 * برای سازگاری با داده‌های قبلی، اگر لیست خالی باشد مقدار قدیمی contact.phone هم خوانده می‌شود.
 */
class ContactPhones
{
    public const MAX = 6;

    /**
     * شماره‌های معتبر را (بدون ردیف خالی) برای نمایش یا ویرایش برمی‌گرداند.
     *
     * @param  array<string, mixed>|null  $contact
     * @return list<array{label: string, number: string, href: string}>
     */
    public static function from(?array $contact): array
    {
        $rows = is_array($contact['phones'] ?? null) ? $contact['phones'] : [];

        if ($rows === [] && is_string($contact['phone'] ?? null) && trim($contact['phone']) !== '') {
            $rows = [['label' => '', 'number' => $contact['phone']]];
        }

        $phones = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $number = trim((string) ($row['number'] ?? ''));
            if ($number === '') {
                continue;
            }
            $phones[] = [
                'label' => trim((string) ($row['label'] ?? '')),
                'number' => $number,
                'href' => self::href($number),
            ];

            if (count($phones) >= self::MAX) {
                break;
            }
        }

        return $phones;
    }

    /** مقدار href برای لینک tel: (ارقام فارسی و عربی به لاتین، بدون فاصله و خط‌تیره). */
    public static function href(string $number): string
    {
        $latin = MobileNumber::toLatinDigits(trim($number));

        return preg_replace('/[^0-9+*#,;]/', '', $latin) ?? '';
    }
}
