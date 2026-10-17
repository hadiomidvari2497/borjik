<?php

use App\Models;

return [
    // موجودیت‌هایی که تاریخچه دارند: برچسب فارسی و (اختیاری) مسیرِ صفحه‌ی نمایش
    'models' => [
        Models\Building::class => ['label' => 'ساختمان'],
        Models\Block::class => ['label' => 'بلوک'],
        Models\Unit::class => ['label' => 'واحد'],
        Models\UnitParty::class => ['label' => 'ساکن/مالک واحد'],
        Models\BuildingUserRole::class => ['label' => 'عضویت/پرسنل'],
        Models\Invitation::class => ['label' => 'دعوت‌نامه'],
        Models\User::class => ['label' => 'کاربر'],
        Models\Contract::class => ['label' => 'قرارداد', 'route' => 'contracts.show'],
        Models\Parking::class => ['label' => 'پارکینگ'],
        Models\ParkingAssignment::class => ['label' => 'اختصاص پارکینگ'],
        Models\Storage::class => ['label' => 'انبار'],
        Models\StorageAssignment::class => ['label' => 'اختصاص انبار'],
        Models\Expense::class => ['label' => 'هزینه'],
        Models\ExpenseCategory::class => ['label' => 'دسته‌ی هزینه'],
        Models\ChargeSetting::class => ['label' => 'تنظیمات شارژ'],
        Models\ChargeFixedRule::class => ['label' => 'قاعده‌ی شارژ ثابت'],
        Models\ChargePeriod::class => ['label' => 'دوره‌ی شارژ'],
        Models\Invoice::class => ['label' => 'صورت‌حساب'],
        Models\PaymentReceipt::class => ['label' => 'فیش پرداخت'],
        Models\Ticket::class => ['label' => 'تیکت'],
        Models\TicketAssignee::class => ['label' => 'ارجاع تیکت'],
        Models\Document::class => ['label' => 'سند'],
        Models\Event::class => ['label' => 'رویداد'],
        Models\Poll::class => ['label' => 'نظرسنجی'],
        Models\PhonebookEntry::class => ['label' => 'دفترچه تلفن'],
        Models\SecurityShift::class => ['label' => 'شیفت حراست'],
        Models\Supply::class => ['label' => 'ملزومات'],
        Models\SmsTemplate::class => ['label' => 'قالب پیامک'],
    ],

    'events' => ['created' => 'ایجاد', 'updated' => 'ویرایش', 'deleted' => 'حذف', 'restored' => 'بازیابی'],

    // برچسب فارسی ستون‌ها (برای همه‌ی موجودیت‌ها مشترک؛ ستونِ ناشناخته با نام خودش نمایش داده می‌شود)
    'fields' => [
        'name' => 'نام', 'title' => 'عنوان', 'number' => 'شماره', 'code' => 'کد', 'status' => 'وضعیت', 'type' => 'نوع', 'kind' => 'نوع',
        'category' => 'دسته', 'description' => 'توضیحات', 'notes' => 'توضیحات', 'note' => 'توضیحات', 'address' => 'نشانی', 'postal_code' => 'کد پستی',
        'floor' => 'طبقه', 'floors_count' => 'تعداد طبقات', 'area_m2' => 'متراژ', 'bedrooms' => 'تعداد خواب', 'direction' => 'جهت',
        'residents_count' => 'تعداد ساکنین', 'opening_balance' => 'مانده‌ی اولیه', 'block_id' => 'بلوک', 'unit_id' => 'واحد', 'user_id' => 'کاربر',
        'building_id' => 'ساختمان', 'role_id' => 'نقش', 'contract_id' => 'قرارداد', 'created_by' => 'ثبت‌کننده', 'invited_by' => 'دعوت‌کننده',
        'assigned_by' => 'اختصاص‌دهنده', 'entered_by' => 'ثبت‌کننده', 'reviewed_by' => 'بررسی‌کننده', 'uploaded_by' => 'بارگذارنده',
        'charge_payer' => 'شارژ بر عهده‌ی', 'deposit_amount' => 'پیش‌پرداخت', 'monthly_rent' => 'اجاره‌ی ماهانه', 'sale_price' => 'مبلغ فروش',
        'signed_at' => 'تاریخ عقد', 'start_date' => 'تاریخ شروع', 'end_date' => 'تاریخ پایان', 'ended_at' => 'تاریخ خاتمه', 'from_date' => 'از تاریخ', 'to_date' => 'تا تاریخ',
        'relation' => 'نسبت با واحد', 'amount' => 'مبلغ', 'paid_amount' => 'مبلغ پرداخت‌شده', 'issued_at' => 'تاریخ صدور', 'due_date' => 'مهلت پرداخت',
        'paid_at' => 'تاریخ پرداخت', 'reject_reason' => 'دلیل رد', 'reviewed_at' => 'زمان بررسی', 'period_id' => 'دوره', 'payer_user_id' => 'پرداخت‌کننده',
        'jalali_year' => 'سال', 'jalali_month' => 'ماه', 'calc_date' => 'تاریخ محاسبه', 'total_expenses' => 'مجموع هزینه‌ها', 'category_id' => 'دسته‌ی هزینه',
        'split_basis' => 'مبنای تقسیم', 'method' => 'روش محاسبه', 'mode' => 'حالت', 'fixed_amount' => 'مبلغ ثابت', 'area_rate' => 'نرخ هر متر', 'person_rate' => 'نرخ هر نفر',
        'person_ratio_pct' => 'سهم نفرات (٪)', 'area_ratio_pct' => 'سهم متراژ (٪)', 'calc_day' => 'روز محاسبه', 'warn_days_before' => 'روز هشدار پیش از محاسبه',
        'due_days' => 'مهلت پرداخت (روز)', 'emergency_phone' => 'تلفن اضطراری', 'late_fee_enabled' => 'جریمه‌ی تأخیر', 'late_fee_type' => 'نوع جریمه',
        'late_fee_value' => 'مقدار جریمه', 'late_fee_cap' => 'سقف جریمه', 'late_fee_start_day' => 'روز شروع جریمه', 'has_parking' => 'دارای پارکینگ', 'has_storage' => 'دارای انبار',
        'parking_id' => 'پارکینگ', 'storage_id' => 'انبار', 'assigned_at' => 'زمان اختصاص', 'released_at' => 'زمان آزادسازی', 'ticket_id' => 'تیکت', 'priority' => 'اولویت',
        'location' => 'مکان', 'deadline' => 'مهلت', 'closed_at' => 'زمان بستن', 'accepted_at' => 'زمان پذیرش', 'accepted_by' => 'پذیرنده', 'target_role_id' => 'نقش مقصد',
        'starts_at' => 'شروع', 'ends_at' => 'پایان', 'audience' => 'مخاطب', 'phone' => 'تلفن', 'weekday' => 'روز هفته', 'shift_date' => 'تاریخ شیفت', 'start_time' => 'ساعت شروع',
        'end_time' => 'ساعت پایان', 'quantity' => 'موجودی', 'min_quantity' => 'حداقل موجودی', 'body' => 'متن', 'key' => 'کلید', 'is_active' => 'فعال',
        'mobile' => 'موبایل', 'first_name' => 'نام', 'last_name' => 'نام خانوادگی', 'company_name' => 'نام شرکت', 'national_code' => 'کد/شناسه‌ی ملی', 'person_type' => 'نوع شخص',
        'birth_date' => 'تاریخ تولد', 'two_factor_enabled' => 'تأیید دومرحله‌ای', 'deleted_at' => 'زمان حذف', 'invited_at' => 'زمان دعوت', 'joined_at' => 'زمان پیوستن',
        'expires_at' => 'انقضا', 'rejected_at' => 'زمان رد', 'mobile_verified_at' => 'تأیید موبایل', 'profile_completed_at' => 'تکمیل پروفایل', 'built_at' => 'سال ساخت',
        'province_id' => 'استان', 'city_id' => 'شهر', 'subscription_id' => 'اشتراک', 'parking_count_info' => 'تعداد پارکینگ', 'storage_count_info' => 'تعداد انبار',
        'login_destination_mode' => 'مقصد ورود', 'breakdown' => 'جزئیات',
    ],

    'money' => ['amount', 'paid_amount', 'deposit_amount', 'monthly_rent', 'sale_price', 'opening_balance', 'fixed_amount', 'area_rate', 'person_rate', 'late_fee_cap', 'total_expenses'],
    'booleans' => ['is_active', 'late_fee_enabled', 'has_parking', 'has_storage', 'two_factor_enabled'],

    // مقدارهای متنیِ رایج
    'values' => [
        'active' => 'فعال', 'inactive' => 'غیرفعال', 'invited' => 'دعوت‌شده', 'ended' => 'پایان‌یافته', 'pending' => 'در انتظار', 'approved' => 'تأییدشده', 'rejected' => 'ردشده',
        'rent' => 'اجاره', 'sale' => 'فروش', 'charge' => 'شارژ', 'penalty' => 'جریمه', 'unpaid' => 'پرداخت‌نشده', 'partial' => 'پرداخت جزئی', 'paid' => 'پرداخت‌شده', 'void' => 'باطل',
        'tenant' => 'مستأجر', 'owner' => 'مالک', 'vacant' => 'خالی', 'rented' => 'اجاره‌داده‌شده', 'sold' => 'فروخته‌شده', 'repair' => 'در حال تعمیر',
        'draft' => 'پیش‌نویس', 'calculated' => 'محاسبه‌شده', 'published' => 'منتشرشده', 'real' => 'حقیقی', 'legal' => 'حقوقی',
    ],
];
