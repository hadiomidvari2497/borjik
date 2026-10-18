<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('excerpt', 500)->nullable()->after('title');
            $table->string('cover_image', 1000)->nullable()->after('body');
        });

        $now = now();
        \Illuminate\Support\Facades\DB::table('articles')->insertOrIgnore([
            ['title' => 'چطور محاسبهٔ شارژ را شفاف‌تر کنیم؟', 'slug' => 'transparent-building-charges', 'excerpt' => 'با چند قاعدهٔ روشن، ثبت هزینه‌ها و دسترسی به سوابق، اختلاف‌ها کمتر و تصمیم‌گیری آسان‌تر می‌شود.', 'body' => "برای شفافیت در شارژ ساختمان، ابتدا روش محاسبه را از قبل مشخص کنید و هزینه‌های مشترک را با شرح و تاریخ ثبت کنید.\n\nسهم هر واحد باید بر اساس قاعده‌ای قابل توضیح محاسبه شود. سوابق پرداخت و هزینه‌ها را در یک محل نگه دارید تا مدیر و ساکنان در صورت نیاز بتوانند آن‌ها را بررسی کنند.\n\nدر نهایت، گزارش‌های منظم و اطلاع‌رسانی به‌موقع به اعتماد بیشتر میان اعضای ساختمان کمک می‌کند.", 'cover_image' => null, 'status' => 'published', 'published_at' => $now, 'admin_id' => null, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'هیچ درخواست ساکنی نباید گم شود', 'slug' => 'resident-requests-follow-up', 'excerpt' => 'ثبت، ارجاع و پیگیری منظم درخواست‌ها، رسیدگی به مشکلات روزمره را قابل اعتمادتر می‌کند.', 'body' => "هر درخواست بهتر است عنوان، توضیح، زمان ثبت و مسئول پیگیری مشخصی داشته باشد. ثبت درخواست‌ها در یک مسیر مشترک مانع گم شدن پیام‌ها در گفتگوهای پراکنده می‌شود.\n\nوضعیت درخواست را در هر مرحله به‌روزرسانی کنید و پس از انجام کار، نتیجه را به درخواست‌دهنده اطلاع دهید.\n\nاین روش به مدیر کمک می‌کند موارد فوری را تشخیص دهد و ساکنان هم از روند رسیدگی آگاه بمانند.", 'cover_image' => null, 'status' => 'published', 'published_at' => $now, 'admin_id' => null, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'نقش ارتباط شفاف در ساختمان آرام‌تر', 'slug' => 'clear-building-communication', 'excerpt' => 'اطلاع‌رسانی روشن و به‌موقع می‌تواند همکاری میان مدیر، ساکنان و پرسنل را بهتر کند.', 'body' => "ارتباط مؤثر از پیام‌های کوتاه، روشن و به‌موقع شروع می‌شود. برای اطلاعیه‌ها، زمان مشخص و مسیر قابل دسترسی در نظر بگیرید و از بیان مبهم موضوعات مهم پرهیز کنید.\n\nبرای تصمیم‌هایی که بر همهٔ ساکنان اثر می‌گذارند، دلیل تصمیم و اقدام مورد انتظار را توضیح دهید. همچنین بهتر است پرسش‌ها و بازخوردها در یک مسیر مشخص جمع‌آوری شوند.\n\nوقتی همه بدانند چه اتفاقی افتاده و قدم بعدی چیست، سوءتفاهم کمتر می‌شود و همکاری آسان‌تر پیش می‌رود.", 'cover_image' => null, 'status' => 'published', 'published_at' => $now, 'admin_id' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['excerpt', 'cover_image']);
        });
    }
};
