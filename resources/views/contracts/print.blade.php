@php
    $F = \App\Support\Fa::class;
    $rent = $contract->type->value === 'rent';
    $partyName = fn ($person) => $person?->display_name ?: '................................';
    $personAddress = fn ($person) => $person?->address ?: '................................................................';
    $personCode = fn ($person) => $person?->national_code ?: '........................';
    $personMobile = fn ($person) => $person?->mobile ?: '........................';
    $unit = $contract->unit;
    $building = $unit->building;
    $firstParty = $rent ? $owner : $seller;
    $secondParty = $contract->user;
    $money = fn ($amount) => $F::money($amount).' تومان';
@endphp
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>قرارداد {{ $rent ? 'اجاره' : 'خرید و فروش' }} شماره {{ $F::digits($contract->id) }}</title>
<style>
@page{size:A4;margin:16mm 15mm 17mm}
*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{font-family:Tahoma,"Vazirmatn",sans-serif;color:#172b32;font-size:11px;line-height:2;background:#eef1f2}
.sheet{width:210mm;min-height:297mm;margin:18px auto;padding:15mm;background:#fff;box-shadow:0 4px 24px #102b3a18}
.topline{height:5px;background:#176d62;margin:-15mm -15mm 9mm}
.header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #176d62;padding-bottom:12px;margin-bottom:16px}
.brand{font-size:13px;font-weight:800;color:#176d62}
.doc-title{text-align:center;flex:1}
.doc-title h1{font-size:20px;margin:0 0 3px}
.doc-title p{margin:0;color:#65767b;font-size:10px}
.serial{border:1px solid #aab8bb;border-radius:6px;padding:5px 9px;min-width:95px;text-align:center}
.serial b{display:block;font-size:13px}
.intro{background:#f4f8f7;border:1px solid #d4e3df;padding:9px 12px;border-radius:5px;margin-bottom:13px}
.section{margin:13px 0;break-inside:avoid}
.section h2{font-size:12px;margin:0 0 7px;padding:5px 9px;background:#edf4f2;border-right:3px solid #176d62}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:6px 16px}
.field{border-bottom:1px dotted #aab6b9;padding:3px 0;min-height:25px}
.field strong{color:#42575d;font-weight:700}
.value{font-weight:700}
.amount{font-size:13px;color:#145d54;font-weight:800}
.clause{margin:6px 0;text-align:justify}
.clause b{color:#1a4e48}
.signature-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:22px;break-inside:avoid}
.sign{border:1px solid #aebbbd;border-radius:5px;min-height:112px;padding:9px;text-align:center}
.sign h3{margin:0 0 6px;font-size:12px}
.sign .line{margin-top:55px;border-top:1px dotted #7b8b8f;padding-top:4px;color:#65767b}
.witness{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;break-inside:avoid}
.witness div{border:1px dashed #b8c2c4;padding:7px;min-height:65px}
.notice{font-size:9px;color:#58696e;background:#f7f8f8;padding:8px;border:1px solid #e0e5e6;border-radius:4px;margin-top:15px}
.footer{display:flex;justify-content:space-between;border-top:1px solid #cbd4d5;margin-top:18px;padding-top:7px;font-size:9px;color:#68777b}
.actions{width:210mm;margin:12px auto;display:flex;justify-content:flex-end;gap:8px}
.btn{border:0;border-radius:6px;background:#176d62;color:#fff;padding:9px 16px;font:inherit;cursor:pointer;text-decoration:none}
.btn.secondary{background:#53666c}
@media print{body{background:#fff}.sheet{width:auto;min-height:0;margin:0;padding:0;box-shadow:none}.topline{margin:-0mm 0 9mm}.actions{display:none}.section,.signature-grid,.witness{break-inside:avoid}a{color:inherit;text-decoration:none}}
@media screen and (max-width:800px){.sheet,.actions{width:100%;margin:0}.sheet{padding:18px}.topline{margin:-18px -18px 18px}.grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="actions">
    <a class="btn secondary" href="{{ route('contracts.show', $contract->id) }}">بازگشت به قرارداد</a>
    <button class="btn" type="button" onclick="window.print()">چاپ قرارداد / ذخیره PDF</button>
</div>
<main class="sheet">
<div class="topline"></div>
<header class="header">
    <div class="brand">صابخونه</div>
    <div class="doc-title">
        <h1>قرارداد {{ $rent ? 'اجاره' : 'خرید و فروش' }} ملک</h1>
        <p>متن چاپی قرارداد ثبت‌شده در سامانه مدیریت ساختمان</p>
    </div>
    <div class="serial"><span>شماره قرارداد</span><b>{{ $F::digits($contract->id) }}</b></div>
</header>

<div class="intro">
    این قرارداد در تاریخ <b>{{ $F::date($contract->signed_at, 'Y/m/d') }}</b>، با قصد و رضای طرفین و بر اساس اطلاعات ثبت‌شده زیر تنظیم شده است. طرفین با امضای تمام صفحات، مفاد و تعهدات مندرج در این سند را تأیید می‌کنند.
</div>

<section class="section">
<h2>ماده ۱ ـ مشخصات طرفین قرارداد</h2>
<div class="grid">
    <div class="field"><strong>{{ $rent ? 'موجر (مالک)' : 'فروشنده' }}:</strong> <span class="value">{{ $partyName($firstParty) }}</span></div>
    <div class="field"><strong>کد ملی / شناسه ملی:</strong> {{ $personCode($firstParty) }}</div>
    <div class="field"><strong>شماره تماس:</strong> {{ $personMobile($firstParty) }}</div>
    <div class="field"><strong>نشانی:</strong> {{ $personAddress($firstParty) }}</div>
    <div class="field"><strong>{{ $rent ? 'مستأجر' : 'خریدار' }}:</strong> <span class="value">{{ $partyName($secondParty) }}</span></div>
    <div class="field"><strong>کد ملی / شناسه ملی:</strong> {{ $personCode($secondParty) }}</div>
    <div class="field"><strong>شماره تماس:</strong> {{ $personMobile($secondParty) }}</div>
    <div class="field"><strong>نشانی:</strong> {{ $personAddress($secondParty) }}</div>
</div>
</section>

<section class="section">
<h2>ماده ۲ ـ موضوع قرارداد و مشخصات مورد معامله / اجاره</h2>
<div class="grid">
    <div class="field"><strong>ساختمان:</strong> {{ $building?->name ?: '—' }}</div>
    <div class="field"><strong>بلوک:</strong> {{ $unit->block?->name ?: '—' }}</div>
    <div class="field"><strong>شماره واحد:</strong> {{ $F::digits($unit->number) }}</div>
    <div class="field"><strong>طبقه:</strong> {{ $F::digits($unit->floor) }}</div>
    <div class="field"><strong>مساحت:</strong> {{ $F::digits($unit->area_m2) }} مترمربع</div>
    <div class="field"><strong>کد پستی واحد:</strong> {{ $unit->postal_code ?: '........................' }}</div>
    <div class="field" style="grid-column:1/-1"><strong>نشانی ملک:</strong> {{ trim(($building?->province?->name ?? '').' '.($building?->city?->name ?? '').' '.($building?->address ?? '')) ?: '................................................................' }}</div>
</div>
<p class="clause">مورد قرارداد عبارت است از {{ $rent ? 'واگذاری منافع و حق استفاده از ملک فوق برای مدت مقرر در این قرارداد' : 'انتقال حقوق مالکانه و شش‌دانگ واحد فوق از فروشنده به خریدار، با رعایت تشریفات قانونی و ثبتی مربوط' }}.</p>
</section>

<section class="section">
<h2>ماده ۳ ـ مبلغ و شرایط مالی</h2>
@if($rent)
<div class="grid">
    <div class="field"><strong>مبلغ ودیعه / قرض‌الحسنه:</strong><div class="amount">{{ $money($contract->deposit_amount) }}</div></div>
    <div class="field"><strong>اجاره‌بهای ماهانه:</strong><div class="amount">{{ $money($contract->monthly_rent) }}</div></div>
    <div class="field"><strong>مسئول پرداخت شارژ ساختمان:</strong> {{ $contract->charge_payer->value === 'tenant' ? 'مستأجر' : 'مالک' }}</div>
    <div class="field"><strong>تاریخ شروع:</strong> {{ $contract->start_date ? $F::date($contract->start_date, 'Y/m/d') : $F::date($contract->signed_at, 'Y/m/d') }}</div>
    <div class="field"><strong>تاریخ پایان:</strong> {{ $contract->end_date ? $F::date($contract->end_date, 'Y/m/d') : '........................' }}</div>
</div>
@else
<div class="field"><strong>ثمن معامله:</strong> <span class="amount">{{ $money($contract->sale_price) }}</span></div>
<div class="field"><strong>نحوه و زمان‌بندی پرداخت:</strong> ....................................................................................................................................</div>
<div class="field"><strong>تاریخ تحویل و انتقال:</strong> ....................................................................................................................................</div>
@endif
</section>

<section class="section">
<h2>ماده ۴ ـ تعهدات و شروط قرارداد</h2>
@if($rent)
<p class="clause"><b>۴-۱.</b> موجر متعهد است مورد اجاره را در موعد توافق‌شده با امکان انتفاع متعارف در اختیار مستأجر قرار دهد و مستأجر متعهد به استفاده متعارف و نگهداری از عین مستأجره است.</p>
<p class="clause"><b>۴-۲.</b> مستأجر مکلف است اجاره‌بها را در موعد مورد توافق طرفین پرداخت کند و رسید پرداخت‌ها را نگهداری نماید.</p>
<p class="clause"><b>۴-۳.</b> هزینه‌های مصرفی و تعهدات مربوط به شارژ ساختمان مطابق توافق فوق و مقررات ساختمان پرداخت می‌شود. هزینه‌های ناشی از تعدی و تفریط هر طرف بر عهده همان طرف است.</p>
<p class="clause"><b>۴-۴.</b> مستأجر بدون رضایت کتبی موجر حق واگذاری منافع به غیر یا تغییر کاربری مورد اجاره را ندارد، مگر در مواردی که قانون یا توافق کتبی طرفین اجازه دهد.</p>
<p class="clause"><b>۴-۵.</b> تمدید، تغییر شروط یا خاتمه توافقی قرارداد باید به صورت مکتوب و با امضای طرفین انجام شود. تخلیه و استرداد ودیعه مطابق قانون و پس از تعیین تکلیف بدهی‌ها و خسارات مستند انجام می‌شود.</p>
@else
<p class="clause"><b>۴-۱.</b> فروشنده متعهد است مدارک و مستندات مالکیت و اطلاعات لازم برای انجام تشریفات قانونی انتقال را ارائه کند و خریدار نیز ثمن معامله را مطابق توافق طرفین بپردازد.</p>
<p class="clause"><b>۴-۲.</b> فروشنده اعلام می‌کند اطلاعات و مدارک ارائه‌شده درباره مورد معامله صحیح است و هرگونه محدودیت، بازداشت، رهن یا حق اشخاص ثالث را که از آن اطلاع دارد باید پیش از امضا به خریدار اعلام کند.</p>
<p class="clause"><b>۴-۳.</b> تحویل مورد معامله، تسویه هزینه‌های مربوط تا تاریخ تحویل و تعیین تکلیف قبوض و شارژ بر اساس توافق کتبی طرفین انجام می‌شود.</p>
<p class="clause"><b>۴-۴.</b> طرفین متعهدند برای انجام تشریفات قانونی و ثبتی انتقال مالکیت در مراجع ذی‌صلاح حاضر شوند. ثبت این قرارداد در سامانه مدیریت ساختمان به‌تنهایی جایگزین سند رسمی یا ثبت قانونی انتقال نیست.</p>
<p class="clause"><b>۴-۵.</b> هرگونه تغییر یا توافق تکمیلی درباره مبلغ، موعد پرداخت، تحویل یا تعهدات طرفین باید به صورت مکتوب و با امضای طرفین باشد.</p>
@endif
<p class="clause"><b>۴-۶.</b> طرفین اقرار می‌کنند پیش از امضا، مفاد این قرارداد را مطالعه کرده و فرصت بررسی مدارک و طرح پرسش‌های لازم را داشته‌اند.</p>
</section>

<section class="section">
<h2>ماده ۵ ـ توضیحات و شروط اختصاصی مورد توافق</h2>
<div style="min-height:75px;border:1px solid #cbd4d5;border-radius:4px;padding:8px">
........................................................................................................................................................................<br>
........................................................................................................................................................................<br>
........................................................................................................................................................................
</div>
</section>

<section class="section">
<h2>ماده ۶ ـ نسخ قرارداد و امضا</h2>
<p class="clause">این قرارداد در دو نسخه متحدالمتن تنظیم شده و هر نسخه دارای اعتبار یکسان است. طرفین با امضای ذیل و امضای صفحات، مفاد آن را تأیید می‌کنند. هرگونه اختلاف در تفسیر یا اجرای قرارداد تابع قوانین و مقررات لازم‌الاجرای جمهوری اسلامی ایران خواهد بود.</p>
</section>

<div class="signature-grid">
    <div class="sign"><h3>امضا و اثر انگشت {{ $rent ? 'موجر' : 'فروشنده' }}</h3><b>{{ $partyName($firstParty) }}</b><div class="line">امضا / اثر انگشت</div></div>
    <div class="sign"><h3>امضا و اثر انگشت {{ $rent ? 'مستأجر' : 'خریدار' }}</h3><b>{{ $partyName($secondParty) }}</b><div class="line">امضا / اثر انگشت</div></div>
</div>
<div class="witness">
    <div><b>شاهد اول</b><br>نام و نام خانوادگی: ........................................<br>کد ملی: ............................ امضا: ................</div>
    <div><b>شاهد دوم</b><br>نام و نام خانوادگی: ........................................<br>کد ملی: ............................ امضا: ................</div>
</div>

<div class="notice">توجه: این نسخه، فرم چاپی و پیش‌نویس قراردادی مبتنی بر اطلاعات ثبت‌شده در سامانه است و به‌خودی‌خود سند رسمی، سند مالکیت یا ثبت قطعی معامله محسوب نمی‌شود. پیش از امضا، مشخصات هویتی، مالکیت، وضعیت ثبتی، شروط اختصاصی، مبالغ و مواعد را با مدارک معتبر تطبیق دهید و برای استفاده حقوقی نهایی از مشاوره متخصص حقوقی بهره بگیرید.</div>
<footer class="footer"><span>صابخونه ـ سامانه مدیریت ساختمان</span><span>شماره قرارداد: {{ $F::digits($contract->id) }} | تاریخ تنظیم: {{ $F::date($contract->signed_at, 'Y/m/d') }}</span></footer>
</main>
</body>
</html>
