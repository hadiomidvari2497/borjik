<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class MarketingContentController extends Controller
{
    public function edit()
    {
        return view('admin.marketing-content.edit', [
            'content' => array_replace($this->defaults(), SiteSetting::get('marketing.content', [])),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'hero.badge' => ['required', 'string', 'max:120'],
            'hero.title' => ['required', 'string', 'max:160'],
            'hero.highlight' => ['required', 'string', 'max:120'],
            'hero.description' => ['required', 'string', 'max:600'],
            'hero.primary_cta' => ['required', 'string', 'max:60'],
            'hero.secondary_cta' => ['required', 'string', 'max:60'],
            'features_title' => ['required', 'string', 'max:160'],
            'features_description' => ['required', 'string', 'max:500'],
            'features' => ['required', 'array', 'min:1', 'max:12'],
            'features.*.icon' => ['nullable', 'string', 'max:12'],
            'features.*.title' => ['required', 'string', 'max:100'],
            'features.*.description' => ['required', 'string', 'max:500'],
            'trust_items' => ['required', 'array', 'min:1', 'max:6'],
            'trust_items.*.icon' => ['nullable', 'string', 'max:12'],
            'trust_items.*.text' => ['required', 'string', 'max:100'],
            'steps' => ['required', 'array', 'min:1', 'max:6'],
            'steps.*.title' => ['required', 'string', 'max:120'],
            'steps.*.description' => ['required', 'string', 'max:500'],
            'footer_description' => ['required', 'string', 'max:500'],
            'enamad' => ['nullable', 'array'],
            'enamad.enabled' => ['nullable', 'boolean'],
            'enamad.image_url' => ['nullable', 'url', 'max:2048'],
            'enamad.verification_url' => ['nullable', 'url', 'max:2048'],
            'enamad.alt' => ['nullable', 'string', 'max:160'],
            'about.eyebrow' => ['required', 'string', 'max:100'],
            'about.title' => ['required', 'string', 'max:180'],
            'about.paragraph1' => ['required', 'string', 'max:1200'],
            'about.paragraph2' => ['nullable', 'string', 'max:1200'],
            'about.note_title' => ['required', 'string', 'max:180'],
            'about.note_description' => ['required', 'string', 'max:500'],
            'about.points' => ['nullable', 'array', 'max:6'],
            'about.points.*' => ['nullable', 'string', 'max:200'],
            'articles_title' => ['required', 'string', 'max:160'],
            'articles_description' => ['required', 'string', 'max:500'],
            'ads.eyebrow' => ['required', 'string', 'max:100'],
            'ads.title' => ['required', 'string', 'max:180'],
            'ads.description' => ['required', 'string', 'max:1000'],
            'ads.button' => ['required', 'string', 'max:60'],
            'partners.eyebrow' => ['required', 'string', 'max:100'],
            'partners.title' => ['required', 'string', 'max:180'],
            'partners.description' => ['required', 'string', 'max:1000'],
            'partners.button' => ['required', 'string', 'max:60'],
            'contact.eyebrow' => ['required', 'string', 'max:100'],
            'contact.title' => ['required', 'string', 'max:180'],
            'contact.description' => ['required', 'string', 'max:1000'],
            'contact.email' => ['nullable', 'email', 'max:120'],
            'contact.phone' => ['nullable', 'string', 'max:30'],
            'contact.support_title' => ['required', 'string', 'max:120'],
            'contact.support_description' => ['required', 'string', 'max:300'],
        ], [], [
            'hero.badge' => 'برچسب ابتدای صفحه',
            'hero.title' => 'عنوان اصلی',
            'hero.highlight' => 'عبارت برجسته',
        ]);

        $data['enamad'] = [
            'enabled' => $request->boolean('enamad.enabled'),
            'image_url' => data_get($data, 'enamad.image_url'),
            'verification_url' => data_get($data, 'enamad.verification_url'),
            'alt' => data_get($data, 'enamad.alt', 'نماد اعتماد الکترونیکی برجیک'),
        ];

        SiteSetting::set('marketing.content', $data);

        return redirect()->route('admin.marketing-content.edit')->with('status', 'محتوای صفحهٔ اصلی ذخیره شد.');
    }

    private function defaults(): array
    {
        return [
            'hero' => ['badge' => 'مدیریت نوین ساختمان، در یک جا', 'title' => 'مدیریت ساختمان،', 'highlight' => 'با آرامش بیشتر', 'description' => 'برجیک کمک می‌کند امور مالی، شارژ، درخواست‌های ساکنان و ارتباطات ساختمان را شفاف‌تر و منظم‌تر مدیریت کنید؛ بدون کاغذبازی و سردرگمی.', 'primary_cta' => 'شروع استفاده از برجیک', 'secondary_cta' => 'امکانات را ببینید'],
            'features_title' => 'همه‌ی کارهای ساختمان، در یک تجربه‌ی یکپارچه',
            'features_description' => 'ابزارهایی برای کمتر شدن کارهای تکراری، روشن‌تر شدن وضعیت‌ها و ارتباط بهتر میان اعضای ساختمان.',
            'trust_items' => [['icon'=>'◈','text'=>'شفافیت مالی'],['icon'=>'✓','text'=>'پیگیری درخواست‌ها'],['icon'=>'⌂','text'=>'مناسب انواع ساختمان'],['icon'=>'↗','text'=>'دسترسی ساده و آنلاین']],
            'steps' => [['title'=>'حساب خود را بسازید','description'=>'با شماره موبایل وارد شوید و اطلاعات اولیه‌ی حساب خود را تکمیل کنید.'],['title'=>'ساختمان را راه‌اندازی کنید','description'=>'ساختمان، بلوک‌ها و واحدها را تعریف کنید و اعضا و مسئولیت‌ها را مشخص کنید.'],['title'=>'همه‌چیز را یکپارچه پیگیری کنید','description'=>'شارژ، پرداخت‌ها، درخواست‌ها و برنامه‌های ساختمان را از یک سامانه مدیریت کنید.']],
            'footer_description' => 'سامانه‌ی فارسی مدیریت ساختمان؛ برای شفافیت بیشتر، پیگیری ساده‌تر و همکاری بهتر.',
            'enamad' => ['enabled' => false, 'image_url' => '', 'verification_url' => '', 'alt' => 'نماد اعتماد الکترونیکی برجیک'],
            'features' => [
                ['icon' => '◉', 'title' => 'محاسبه و صدور شارژ', 'description' => 'قواعد شارژ و هزینه‌های ساختمان را مدیریت کنید و سهم هر واحد را با روشی روشن محاسبه کنید.'],
                ['icon' => '⇄', 'title' => 'پرداخت و پیگیری مالی', 'description' => 'صورتحساب‌ها، ثبت پرداخت و وضعیت تأیید فیش‌ها را در یک مسیر مشخص دنبال کنید.'],
                ['icon' => '☷', 'title' => 'درخواست‌ها و وظایف', 'description' => 'درخواست‌های ساکنان را ثبت، ارجاع و پیگیری کنید تا هیچ موردی میان پیام‌ها گم نشود.'],
                ['icon' => '♧', 'title' => 'مدیریت اعضا و پرسنل', 'description' => 'نقش‌ها و دسترسی‌ها را متناسب با مسئولیت مدیر، ساکن و پرسنل سازمان‌دهی کنید.'],
                ['icon' => '▦', 'title' => 'تقویم و اطلاع‌رسانی', 'description' => 'رویدادها و برنامه‌های ساختمان را منظم نگه دارید و ارتباطات را از مسیرهای مشخص پیش ببرید.'],
                ['icon' => '▤', 'title' => 'اسناد و سوابق', 'description' => 'به اطلاعات و سوابق موردنیاز دسترسی داشته باشید و تصمیم‌ها را با دید روشن‌تری بگیرید.'],
            ],
            'about' => ['eyebrow' => 'درباره برجیک', 'title' => 'ساختمان بهتر، با همکاری شفاف‌تر', 'paragraph1' => 'برجیک یک سامانه‌ی فارسی برای سامان‌دهی کارهای روزمره‌ی ساختمان است؛ فضایی مشترک که مدیران، ساکنان و پرسنل بتوانند امور مرتبط را متناسب با نقش خود پیگیری کنند.', 'paragraph2' => 'هدف ما ساده است: کاهش سردرگمی، روشن‌تر شدن سوابق و فراهم کردن تجربه‌ای منظم‌تر برای زندگی و کار در ساختمان.', 'note_title' => 'مدیریت بهتر، با اطلاعات روشن‌تر', 'note_description' => 'وقتی وضعیت‌ها مشخص باشند، تصمیم‌گیری و همکاری هم ساده‌تر می‌شود.', 'points' => ['اطلاعات و مسئولیت‌ها در مسیرهای مشخص و قابل پیگیری', 'تجربه‌ی فارسی و طراحی‌شده برای نیازهای مدیریت ساختمان', 'دسترسی متناسب با نقش هر کاربر در ساختمان']],
            'articles_title' => 'مطالبی برای مدیریت بهتر ساختمان',
            'articles_description' => 'راهنماهای کوتاه درباره‌ی موضوعاتی که هر مدیر و ساکن ساختمان با آن‌ها روبه‌رو می‌شود.',
            'ads' => ['eyebrow' => 'تبلیغات و معرفی خدمات', 'title' => 'کسب‌وکار شما به جامعه‌ی ساختمان‌ها خدمت می‌کند؟', 'description' => 'این بخش برای معرفی خدمات مرتبط با ساختمان، نگهداری، تعمیرات و زندگی آپارتمانی در نظر گرفته شده است. برای فعال‌سازی همکاری تبلیغاتی با ما در ارتباط باشید.', 'button' => 'درخواست همکاری تبلیغاتی'],
            'partners' => ['eyebrow' => 'همکاران ما', 'title' => 'با همکاری هم، مدیریت ساختمان را بهتر می‌کنیم', 'description' => 'از شرکت‌های خدمات ساختمانی و تعمیر و نگهداری تا کسب‌وکارهای مرتبط؛ این بخش برای معرفی همکاران و راهکارهای مکمل برجیک آماده شده است.', 'button' => 'پیشنهاد همکاری'],
            'contact' => ['eyebrow' => 'تماس با ما', 'title' => 'برای شروع همکاری، در ارتباط باشیم', 'description' => 'برای استفاده از سامانه یا پیگیری امور حساب خود، از ورود به برجیک شروع کنید.', 'email' => (string) SiteSetting::get('support.email', config('mail.from.address', '')), 'phone' => (string) SiteSetting::get('support.phone', ''), 'support_title' => 'پشتیبانی حساب کاربری', 'support_description' => 'برای شروع، وارد سامانه شوید و از امکانات حساب خود استفاده کنید.'],
        ];
    }
}
