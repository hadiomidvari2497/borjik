# برجیک (Borjik)

سامانه‌ی مدیریت ساختمان به زبان فارسی — مبتنی بر **Laravel 12** و **PHP 8.2+**.

ورود با **کد یکبارمصرف پیامکی** (یا رمز عبور اختیاری)، چندساختمانی با **جداسازی داده در سطح Eloquent**، نقش‌ها و مجوزها، اشتراک بر اساس تعداد واحد، محاسبه و صدور شارژ با چند روش، هزینه‌های ماهانه، فیش پرداخت و داشبورد مدیر / ساکن / پرسنل.

---

## فهرست مطالب

1. [پیش‌نیازها](#پیش‌نیازها)
2. [مراحل نصب سریع (خلاصه)](#مراحل-نصب-سریع-خلاصه)
3. [نصب کامل — ویندوز / XAMPP](#نصب-کامل--ویندوز--xampp)
4. [نصب کامل — لینوکس / macOS](#نصب-کامل--لینوکس--macos)
5. [پیکربندی `.env`](#پیکربندی-env)
6. [اولین ورود و راه‌اندازی](#اولین-ورود-و-راه‌اندازی)
7. [معماری و مفاهیم](#معماری-و-مفاهیم)
8. [نقش‌ها و دسترسی‌ها](#نقش‌ها-و-دسترسی‌ها)
9. [ماژول‌های اصلی](#ماژول‌های-اصلی)
10. [پیامک (SMS)](#پیامک-sms)
11. [دستورات Artisan اختصاصی](#دستورات-artisan-اختصاصی)
12. [ساختار پوشه‌ها](#ساختار-پوشه‌ها)
13. [تست و CI](#تست-و-ci)
14. [عیب‌یابی رایج](#عیب‌یابی-رایج)
15. [نکات توسعه](#نکات-توسعه)

---

## پیش‌نیازها

| مورد | نسخه / توضیح |
|------|----------------|
| PHP | **8.2** یا بالاتر (افزونه‌ها: `mbstring`, `pdo_mysql` یا `pdo_sqlite`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`) |
| Composer | 2.x |
| پایگاه‌داده | **MySQL 8** / MariaDB (پیشنهادی برای لوکال با XAMPP) یا SQLite |
| وب‌سرور | `php artisan serve` یا Apache/Nginx با Document Root روی `public/` |
| Node.js | اختیاری؛ رابط فعلی عمدتاً CSS/JS استاتیک در `public/` دارد |

وابستگی‌های Composer مهم:

- `laravel/framework` ^12
- `morilog/jalali` ^3.5 (تاریخ شمسی)

---

## مراحل نصب سریع (خلاصه)

اگر محیط آماده است، این دستورات کافی‌اند:

```bash
git clone https://github.com/hadiomidvari2497/borjik.git
cd borjik
composer install
cp .env.example .env          # ویندوز: copy .env.example .env
php artisan key:generate
# .env را برای دیتابیس ویرایش کنید (بخش پایین)
php artisan migrate --seed
php artisan serve
```

سپس مرورگر: [http://localhost:8000](http://localhost:8000)

---

## نصب کامل — ویندوز / XAMPP

این مسیر برای تست روی **ویندوز + XAMPP** است (همان چیزی که معمولاً روی سیستم لوکال استفاده می‌شود).

### مرحله ۱ — نصب نرم‌افزارها

1. [XAMPP](https://www.apachefriends.org/) را نصب کنید (PHP 8.2 توصیه می‌شود).
2. [Composer](https://getcomposer.org/download/) را نصب کنید.
3. از XAMPP Control Panel سرویس **MySQL** را Start کنید. Apache اختیاری است؛ با `php artisan serve` هم کار می‌کند.

### مرحله ۲ — دریافت پروژه

**روش A — Git:**

```cmd
cd C:\xampp\htdocs
git clone https://github.com/hadiomidvari2497/borjik.git borjik
cd borjik
```

**روش B — ZIP از GitHub:**

1. از صفحهٔ ریپو گزینه **Code → Download ZIP** را بزنید (یا Artifact `borjik-release` از Actions).
2. محتویات را در `C:\xampp\htdocs\borjik` استخراج کنید طوری که فایل `artisan` مستقیم داخل همین پوشه باشد.

### مرحله ۳ — نصب پکیج‌های PHP

```cmd
cd C:\xampp\htdocs\borjik
composer install
```

اگر `composer` در PATH نبود، مسیر کامل PHP و Composer را استفاده کنید.

### مرحله ۴ — فایل محیط (`.env`)

```cmd
copy .env.example .env
php artisan key:generate
```

### مرحله ۵ — ساخت دیتابیس MySQL

1. باز کردن [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. تب **Databases** → نام: `borjik` → Collation: `utf8mb4_unicode_ci` → Create
3. فایل `.env` را با Notepad یا VS Code باز کنید و این مقادیر را بگذارید:

```env
APP_NAME=برجیک
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=borjik
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=sync
CACHE_STORE=database
SMS_DRIVER=log
```

> **مهم**
> - `DB_PASSWORD` در XAMPP پیش‌فرض معمولاً خالی است.
> - `QUEUE_CONNECTION=sync` یعنی Job پیامک فوری اجرا می‌شود و کد OTP در لاگ دیده می‌شود.
> - اگر `QUEUE_CONNECTION=database` باشد باید جداگانه `php artisan queue:work` را هم اجرا کنید.

### مرحله ۶ — مایگریشن و دادهٔ اولیه

```cmd
php artisan migrate --seed
```

این دستور:

- همهٔ جداول را می‌سازد
- نقش‌ها و مجوزها (`RolePermissionSeeder`)
- پلن‌های اشتراک (`SubscriptionPlanSeeder`)
- استان و شهر (`ProvinceCitySeeder`)

را وارد می‌کند.

اگر دیتابیس را از صفر می‌خواهید (همهٔ داده پاک می‌شود):

```cmd
php artisan migrate:fresh --seed
```

### مرحله ۷ — اجرای سرور

```cmd
php artisan serve
```

آدرس برنامه: **http://localhost:8000**

صفحهٔ ورود: **http://localhost:8000/login**

### مرحله ۸ — اولین ورود (توسعه)

1. یک شماره موبایل وارد کنید (مثلاً `09121234567`).
2. کد OTP را از لاگ بخوانید:

```cmd
findstr /i "SMS" storage\logs\laravel.log
```

یا فایل `storage\logs\laravel.log` را باز کنید و دنبال متن «کد ورود» بگردید.

3. کد ۶ رقمی را وارد کنید.
4. پروفایل را کامل کنید (نام، کد ملی، تاریخ تولد شمسی مثل `1370/05/12`).
5. اشتراک دستی بدهید تا بتوانید ساختمان بسازید:

```cmd
php artisan borjik:grant-subscription 09121234567 50 12
```

6. به آدرس زیر بروید و ساختمان بسازید:

**http://localhost:8000/buildings/create**

---

## نصب کامل — لینوکس / macOS

```bash
git clone https://github.com/hadiomidvari2497/borjik.git
cd borjik
composer install
cp .env.example .env
php artisan key:generate
```

`.env` را برای MySQL یا SQLite ویرایش کنید، سپس:

```bash
php artisan migrate --seed
php artisan serve
```

### حالت SQLite (بدون MySQL)

```bash
touch database/database.sqlite
```

در `.env`:

```env
DB_CONNECTION=sqlite
# DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD را برای MySQL کامنت کنید

SESSION_DRIVER=database
QUEUE_CONNECTION=sync
CACHE_STORE=database
SMS_DRIVER=log
```

سپس:

```bash
php artisan migrate --seed
php artisan serve
```

یافتن OTP:

```bash
grep -i SMS storage/logs/laravel.log
```

---

## پیکربندی `.env`

| متغیر | توضیح |
|--------|--------|
| `APP_KEY` | با `php artisan key:generate` ساخته می‌شود |
| `APP_URL` | آدرس عمومی اپ (لینک دعوت و asset) |
| `DB_*` | اتصال دیتابیس |
| `SESSION_DRIVER` | لوکال/پروداکشن: `database` — بعد از migrate جدول `sessions` ساخته می‌شود |
| `QUEUE_CONNECTION` | توسعه: `sync` — پروداکشن: `database` + `queue:work` |
| `CACHE_STORE` | `database` یا `file` / `redis` |
| `SMS_DRIVER` | توسعه: `log` — پروداکشن: درایور واقعی SMS |

پس از هر تغییر مهم در `.env`:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## اولین ورود و راه‌اندازی

### جریان ورود

1. `/login`
2. شماره موبایل
3. کد OTP (در توسعه از لاگ)
4. تکمیل پروفایل
5. `borjik:grant-subscription` برای اشتراک
6. `/buildings/create`

### ترتیب کار بعد از ساخت ساختمان

1. بلوک‌ها → واحدها (و در صورت نیاز پارکینگ / انباری)
2. تنظیمات شارژ (`/charge-settings`)
3. ثبت هزینه‌های ماه (`/expenses`)
4. باز کردن دوره شارژ، محاسبه، انتشار (`/charges`)
5. دعوت اعضا / پرسنل از Staff و Members

---

## معماری و مفاهیم

### چندساختمانی (Tenant)

- هر کاربر می‌تواند در چند ساختمان نقش داشته باشد.
- ساختمان فعال در session با کلید `current_building_id` نگه داشته می‌شود.
- Middleware `building` (`SetCurrentBuilding`) ساختمان جاری را تنظیم می‌کند.
- جداسازی داده با Scope/Concern روی مدل‌های وابسته به ساختمان انجام می‌شود.

### Middlewareهای اختصاصی

| Alias | کلاس | نقش |
|-------|------|------|
| `profile.complete` | `EnsureProfileComplete` | اجبار تکمیل پروفایل |
| `building` | `SetCurrentBuilding` | ساختمان فعال |
| `subscription.active` | `RequireActiveSubscription` | ساخت بلوک/واحد جدید |

### روت‌ها

- `routes/auth.php` — لاگین OTP، رمز، پروفایل، 2FA، دعوت
- `routes/app.php` — داشبورد، ساختمان، واحد، شارژ، پرداخت، تیم
- `routes/web.php` — اتصال به فایل‌های بالا

ساخت **اولین** ساختمان فقط با `auth` + `profile.complete` است (بدون `building`).

### تاریخ شمسی

- پکیج `morilog/jalali`
- `App\Support\JalaliDate` و `App\Support\Fa`
- UI: `public/js/jdate.js` + `public/css/jdate.css`

---

## نقش‌ها و دسترسی‌ها

با `RolePermissionSeeder` نقش‌هایی مانند `owner`، `manager`، `security`، `tenant` و سایر نقش‌های پرسنلی تعریف می‌شوند.

منو از `config/menu.php` و دسترسی از permissionها ساخته می‌شود.

---

## ماژول‌های اصلی

| ماژول | مسیر | توضیح |
|--------|------|--------|
| ورود / OTP | `/login` | پیامک یا رمز؛ `login.reset` برای ویرایش شماره |
| پروفایل | `/profile` | حقیقی/حقوقی، کد ملی، تاریخ شمسی |
| 2FA | `/profile/two-factor` | TOTP |
| ساختمان | `/buildings` | ساخت، سوییچ، تنظیمات |
| بلوک / واحد | `/blocks`, `/units` | CRUD و ثبت گروهی |
| پارکینگ / انباری | `/parkings`, `/storages` | اختصاص به واحد |
| تنظیمات شارژ | `/charge-settings` | روش محاسبه و قواعد |
| هزینه‌ها | `/expenses` | هزینه ماه شمسی |
| شارژ | `/charges` | محاسبه و انتشار |
| پرداخت من | `/my/invoices`, `/my/payments` | فیش |
| بررسی فیش | `/payments` | تأیید / رد |
| پرسنل / اعضا | `/staff`, `/members` | دعوت و نقش |

---

## پیامک (SMS)

```env
SMS_DRIVER=log
```

- درایور `log`: فقط در لاگ (مناسب توسعه)
- قالب‌ها در `config/sms.php`: OTP، یادآوری هزینه، تأیید/رد فیش، دعوت

ویندوز:

```cmd
findstr /i "SMS" storage\logs\laravel.log
```

---

## دستورات Artisan اختصاصی

```bash
# اشتراک دستی (توسعه)
php artisan borjik:grant-subscription {mobile} {units=50} {months=12}

# یادآوری ثبت هزینه
php artisan list | findstr borjik
```

---

## ساختار پوشه‌ها

```
app/
  Console/Commands/
  Http/Controllers/
  Http/Middleware/
  Models/
  Services/
  Support/
config/
  menu.php
  sms.php
database/migrations/
database/seeders/
public/css/
public/js/
resources/views/
routes/auth.php
routes/app.php
routes/web.php
tests/
```

---

## تست و CI

```bash
php artisan test
```

GitHub Actions (`.github/workflows/ci.yml`):

- Lint، PHPStan، Security Audit، اعتبارسنجی config، مایگریشن، Tests
- در موفقیت روی `main`: Artifact به نام **`borjik-release`**

وضعیت: [Actions](https://github.com/hadiomidvari2497/borjik/actions)

---

## عیب‌یابی رایج

| مشکل | راه حل |
|------|--------|
| `Route [login.reset] not defined` | `php artisan route:clear`؛ روت در `routes/auth.php` باشد |
| `Table '…sessions' doesn't exist` | `php artisan migrate` یا موقتاً `SESSION_DRIVER=file` |
| `Invalid default value for timestamp` | `migrate:fresh --seed` (داده پاک می‌شود) |
| کد OTP نمی‌آید | `SMS_DRIVER=log` و `QUEUE_CONNECTION=sync`؛ لاگ را بخوانید |
| تاریخ تولد نامعتبر | فرمت `1370/05/12` |
| `403 شما عضو هیچ ساختمانی نیستید` | `borjik:grant-subscription` سپس `/buildings/create` |
| CSS قدیمی | Hard Refresh (`Ctrl+F5`) |

---

## نکات توسعه

- صفحات مهمان: `resources/views/layouts/guest.blade.php` + `public/css/auth.css`
- اپ اصلی: `layouts/app.blade.php` + `public/css/app.css`
- `.env` را commit نکنید
- اعلان‌ها (بدون CDN): تاست = Bootstrap 5.3 Toast، سؤال بله/خیر = SweetAlert2؛ فایل‌ها در `public/vendor` (راهنمای به‌روزرسانی: `public/vendor/README.md`)
  - تأیید قبل از ارسال فرم: `<form data-confirm="متن سؤال" data-confirm-title="عنوان" data-confirm-icon="warning">` (با `warning` دکمه‌ی بله قرمز می‌شود)
  - گرفتن متن (مثل دلیل رد): `<form data-prompt="توضیح" data-prompt-name="reason">`
  - پیام‌ها: `->with('status'|'success'|'warning'|'error', ...)` یا `withErrors(['general' => ...])` خودکار تاست می‌شوند؛ از جاوااسکریپت: `Borjik.toastSuccess('...')`
  - `confirm()` / `alert()` / `prompt()` بومی مرورگر ممنوع است (تست `NoNativeDialogsTest` بررسی می‌کند)
- اعلان‌های درون‌سامانه‌ای (بند ۴.۱۸): زنگوله‌ی بالای صفحه و صفحه‌ی «اعلان‌ها» (همه/خوانده‌نشده، «همه را خوانده‌شده کن»)؛ هر کاربر در هر ساختمان فقط اعلان‌های همان ساختمان را می‌بیند
  - ارسال: `app(\App\Services\Notifier::class)->send($کاربر|شناسه|لیست, $buildingId, 'رویداد', 'عنوان', 'متن', route('...'), ['level' => 'info|success|warning', 'key' => 'کلید ضدتکرار', 'label' => 'متن لینک'])`؛ مالک/مدیرها: `->managers($buildingId)`
  - خودکار اعلان می‌شود: صدور شارژ، صدور اجاره‌ی ماهانه، ثبت/تأیید/رد فیش، ثبت و پایان قرارداد، انتقال مالکیت، یادآوری ثبت هزینه‌ها و رویدادهای درخواست/تسک
- تاریخچه‌ی تغییرات (Audit Log): ایجاد/ویرایش/حذف/بازیابی هر موجودیت مهم خودکار ثبت می‌شود (چه کسی، چه زمانی، مقدار قبلی و جدید)؛ صفحه‌ی «تاریخچه تغییرات» (`/audit`، مالک و مدیر) و بخش تاریخچه در صفحه‌ی قرارداد و ویرایش واحد
  - برای موجودیت جدید: `use Auditable;` در مدل + برچسب فارسی در `config/audit.php`؛ ستون‌های اسرار/حجیم: `protected array $auditExclude = [...]`
  - فقط تغییرِ انجام‌شده از طریق مدل ثبت می‌شود؛ به‌جای `Model::where()->update()/delete()` از `->get()->each(fn ($m) => $m->update(...))` استفاده کنید
- زمان‌بندی (Scheduler): `rent:issue-invoices` (صدور خودکار اجاره‌ی ماهانه، ۰۶:۰۰ UTC) و `charges:remind-expenses` هر روز اجرا می‌شوند؛ به شرط اینکه هر دقیقه `php artisan schedule:run` اجرا شود:
  - لینوکس (cron): `* * * * * cd /مسیر/برجیک && php artisan schedule:run >> /dev/null 2>&1`
  - ویندوز/XAMPP (Task Scheduler، تکرار هر ۱ دقیقه): `C:\xampp\php\php.exe C:\xampp\htdocs\borjik\artisan schedule:run`
  - اجرای دستی و تکرارناپذیر: `php artisan rent:issue-invoices` — لیست زمان‌بندی‌ها: `php artisan schedule:list`
  - اجاره‌ی ماهانه هر ماه شمسی در همان روز ماهِ تاریخ عقد صادر می‌شود (نخستین بار هنگام ثبت قرارداد)، تا قبل از تاریخ پایان؛ مهلت پرداخت = «مهلت پرداخت» تنظیمات شارژ؛ قرارداد با تاریخ عقد گذشته صورت‌حساب عقب‌افتاده نمی‌سازد
- پروداکشن: `APP_DEBUG=false`، SMS واقعی، queue worker، بکاپ دیتابیس

---

## لایسنس

اسکلت Laravel تحت [MIT](https://opensource.org/licenses/MIT). منطق کسب‌وکار برجیک متعلق به مالک پروژه است.

**ریپو:** [github.com/hadiomidvari2497/borjik](https://github.com/hadiomidvari2497/borjik)
