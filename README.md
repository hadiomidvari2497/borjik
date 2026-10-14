# برجیک (Borjik)

سامانه‌ی مدیریت ساختمان به زبان فارسی — مبتنی بر **Laravel 12** و **PHP 8.2+**.

ورود با **کد یکبارمصرف پیامکی** (یا رمز عبور اختیاری)، چندساختمانی با **جداسازی داده در سطح Eloquent**، نقش‌ها و مجوزها، اشتراک بر اساس تعداد واحد، محاسبه و صدور شارژ با چند روش، هزینه‌های ماهانه، فیش پرداخت و داشبورد مدیر / ساکن / پرسنل.

---

## فهرست مطالب

1. [پیش‌نیازها](#پیش‌نیازها)
2. [نصب (ویندوز / XAMPP)](#نصب-ویندوز--xampp)
3. [نصب (لینوکس / macOS)](#نصب-لینوکس--macos)
4. [پیکربندی `.env`](#پیکربندی-env)
5. [اولین ورود و راه‌اندازی](#اولین-ورود-و-راه‌اندازی)
6. [معماری و مفاهیم](#معماری-و-مفاهیم)
7. [نقش‌ها و دسترسی‌ها](#نقش‌ها-و-دسترسی‌ها)
8. [ماژول‌های اصلی](#ماژول‌های-اصلی)
9. [پیامک (SMS)](#پیامک-sms)
10. [دستورات Artisan اختصاصی](#دستورات-artisan-اختصاصی)
11. [ساختار پوشه‌ها](#ساختار-پوشه‌ها)
12. [تست و CI](#تست-و-ci)
13. [عیب‌یابی رایج](#عیب‌یابی-رایج)
14. [نکات توسعه](#نکات-توسعه)

---

## پیش‌نیازها

| مورد | نسخه / توضیح |
|------|----------------|
| PHP | **8.2** یا بالاتر (افزونه‌ها: `mbstring`, `pdo_mysql` یا `pdo_sqlite`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`) |
| Composer | 2.x |
| پایگاه‌داده | **MySQL 8** / MariaDB (پیشنهادی برای لوکال با XAMPP) یا SQLite |
| وب‌سرور | `php artisan serve` یا Apache/Nginx با Document Root روی `public/` |
| Node.js | اختیاری؛ فقط اگر بخواهید Vite/`npm run build` را اجرا کنید. رابط فعلی عمدتاً CSS/JS استاتیک در `public/` دارد |

وابستگی‌های Composer مهم:

- `laravel/framework` ^12
- `morilog/jalali` ^3.5 (تاریخ شمسی)

---

## نصب (ویندوز / XAMPP)

### ۱) کلون یا دانلود

```cmd
cd C:\xampp\htdocs
git clone https://github.com/hadiomidvari2497/borjik.git borjik
cd borjik
```

یا ZIP را از GitHub بگیرید و در `C:\xampp\htdocs\borjik` استخراج کنید.

### ۲) وابستگی‌ها

```cmd
composer install
```

### ۳) محیط و کلید

```cmd
copy .env.example .env
php artisan key:generate
```

### ۴) پایگاه‌داده در MySQL (XAMPP)

1. XAMPP Control Panel → **MySQL** را Start کنید.
2. در phpMyAdmin یک دیتابیس بسازید، مثلاً `borjik` با collation `utf8mb4_unicode_ci`.
3. در `.env` این مقادیر را تنظیم کنید:

```env
APP_NAME=برجیک
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

> **مهم برای توسعه:** `QUEUE_CONNECTION=sync` باعث می‌شود Job ارسال SMS بلافاصله اجرا شود و کد OTP در لاگ دیده شود. اگر `database` بماند باید `php artisan queue:work` جداگانه روشن باشد.

### ۵) مایگریشن و Seed

```cmd
php artisan migrate --seed
```

این کار جداول را می‌سازد و Seedهای زیر را اجرا می‌کند:

- `RolePermissionSeeder` — نقش‌ها و مجوزها
- `SubscriptionPlanSeeder` — پلن‌های اشتراک
- `ProvinceCitySeeder` — استان و شهر

### ۶) اجرا

```cmd
php artisan serve
```

آدرس: [http://localhost:8000](http://localhost:8000)

---

## نصب (لینوکس / macOS)

```bash
git clone https://github.com/hadiomidvari2497/borjik.git
cd borjik
composer install
cp .env.example .env
php artisan key:generate
# ویرایش .env برای MySQL یا SQLite
php artisan migrate --seed
php artisan serve
```

برای SQLite:

```env
DB_CONNECTION=sqlite
# فایل database/database.sqlite را بسازید:
# touch database/database.sqlite
```

و خطوط `DB_HOST` / `DB_DATABASE` مربوط به MySQL را خالی یا کامنت بگذارید.

---

## پیکربندی `.env`

| متغیر | توضیح |
|--------|--------|
| `APP_KEY` | با `key:generate` ساخته می‌شود |
| `APP_URL` | آدرس عمومی اپ (برای لینک دعوت و assetها) |
| `DB_*` | اتصال دیتابیس |
| `SESSION_DRIVER` | ترجیحاً `database` در پروداکشن؛ در تست‌ها از `array` استفاده می‌شود |
| `QUEUE_CONNECTION` | توسعه: `sync` — پروداکشن: `database` + worker |
| `CACHE_STORE` | `database` یا `file` / `redis` |
| `SMS_DRIVER` | پیش‌فرض `log` (فقط لاگ). برای پروداکشن باید درایور واقعی پیاده شود |

پس از تغییر `.env`:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## اولین ورود و راه‌اندازی

### جریان ورود

1. باز کردن `/login`
2. وارد کردن شماره موبایل (مثلاً `09121234567`)
3. دریافت کد OTP:
   - با `SMS_DRIVER=log` کد در `storage/logs/laravel.log` نوشته می‌شود (جستجو برای `SMS` یا متن قالب OTP)
4. وارد کردن کد ۶ رقمی
5. تکمیل پروفایل (نام، کد ملی، تاریخ تولد شمسی به صورت `۱۳۷۰/۰۵/۱۲` یا از تقویم شمسی UI)

### اشتراک دستی (توسعه)

تا وقتی درگاه پرداخت اشتراک آماده نیست، بدون اشتراک فعال نمی‌توانید ساختمان جدید بسازید. بعد از حداقل یک بار ورود با شماره:

```bash
php artisan borjik:grant-subscription 09121234567 50 12
```

- آرگومان ۱: موبایل کاربر
- آرگومان ۲: سقف تعداد واحد (پیش‌فرض ۵۰)
- آرگومان ۳: مدت به ماه (پیش‌فرض ۱۲)

سپس به `/buildings/create` بروید و ساختمان بسازید.

### ترتیب منطقی کار بعد از ساخت ساختمان

1. بلوک‌ها → واحدها (و در صورت نیاز پارکینگ / انباری)
2. تنظیمات شارژ (`/charge-settings`)
3. ثبت هزینه‌های ماه (`/expenses`)
4. باز کردن دوره شارژ، محاسبه، انتشار (`/charges`)
5. دعوت اعضا / پرسنل از صفحات Staff و Members

---

## معماری و مفاهیم

### چندساختمانی (Tenant)

- هر کاربر می‌تواند در چند ساختمان نقش داشته باشد.
- ساختمان فعال در session با کلید `current_building_id` نگه داشته می‌شود.
- Middleware `building` (`SetCurrentBuilding`) ساختمان جاری را تنظیم می‌کند.
- جداسازی داده با Scope/Concern روی مدل‌های وابسته به ساختمان انجام می‌شود تا رکورد ساختمان دیگر نشت نکند.

### Middlewareهای اختصاصی

| Alias | کلاس | نقش |
|-------|------|------|
| `profile.complete` | `EnsureProfileComplete` | اجبار تکمیل پروفایل قبل از ورود به اپ |
| `building` | `SetCurrentBuilding` | وجود و تنظیم ساختمان فعال |
| `subscription.active` | `RequireActiveSubscription` | برای ساخت بلوک/واحد جدید |

### روت‌ها

- `routes/auth.php` — لاگین OTP، رمز، پروفایل، 2FA، دعوت
- `routes/app.php` — داشبورد، ساختمان، واحد، شارژ، پرداخت، تیم و …
- `routes/web.php` — اتصال به فایل‌های بالا

ساخت **اولین** ساختمان فقط با `auth` + `profile.complete` است (بدون `building`)، تا مشکل «عضو هیچ ساختمانی نیستید» دور زده شود.

### تاریخ شمسی

- پکیج `morilog/jalali`
- هلپرهای `App\Support\JalaliDate` و نمایش ارقام با `App\Support\Fa`
- UI انتخاب تاریخ: `public/js/jdate.js` + `public/css/jdate.css`

### MySQL و ستون‌های زمانی

در محیط‌های MySQL با SQL mode سخت، برای فیلدهایی مثل `starts_at` / `expires_at` / `ends_at` از نوع `dateTime` در مایگریشن استفاده شده تا خطای *Invalid default value* رخ ندهد.

---

## نقش‌ها و دسترسی‌ها

با `RolePermissionSeeder` نقش‌هایی مانند مالک (`owner`)، مدیر (`manager`)، نگهبان (`security`)، مستأجر (`tenant`) و سایر نقش‌های پرسنلی تعریف می‌شوند و مجوزها (مثل `unit.view`, `charge.calculate`, `payment.review`, `staff.manage`) به آن‌ها وصل می‌شوند.

منوی سایدبار از `config/menu.php` و منطق دسترسی از permissionها ساخته می‌شود.

---

## ماژول‌های اصلی

| ماژول | مسیر تقریبی | توضیح |
|--------|-------------|--------|
| ورود / OTP | `/login` | پیامک یا رمز عبور؛ روت `login.reset` برای ویرایش شماره |
| پروفایل | `/profile` | حقیقی/حقوقی، کد ملی، تاریخ شمسی، رمز اختیاری |
| 2FA | `/profile/two-factor` | TOTP (مثلاً Google Authenticator) |
| ساختمان | `/buildings`, `/building/settings` | ساخت، سوییچ، تنظیمات |
| بلوک / واحد | `/blocks`, `/units` | CRUD و ثبت گروهی واحد |
| پارکینگ / انباری | `/parkings`, `/storages` | ساخت، اختصاص به واحد، آزادسازی |
| تنظیمات شارژ | `/charge-settings` | روش محاسبه، جریمه دیرکرد، قواعد ثابت، دسته هزینه |
| هزینه‌ها | `/expenses` | ثبت هزینه ماه شمسی |
| شارژ | `/charges` | باز کردن دوره، محاسبه، انتشار صورت‌حساب |
| پرداخت من | `/my/invoices`, `/my/payments` | مشاهده بدهی و ثبت فیش |
| بررسی فیش | `/payments` | تأیید / رد توسط مدیر |
| پرسنل / اعضا | `/staff`, `/members` | دعوت، نقش، فعال/غیرفعال |

### روش‌های محاسبه شارژ

سرویس `App\Services\Charge\ChargeCalculator` چند روش را پشتیبانی می‌کند (ثابت واحدی، متراژ، نفری، ترکیبی، نسبتی روی کل هزینه‌ها، هزینه‌های واقعی بر اساس مبنای تقسیم دسته و …). تست واحد: `tests/Unit/ChargeCalculatorTest.php`.

---

## پیامک (SMS)

پیکربندی در `config/sms.php`:

```env
SMS_DRIVER=log
```

- درایور `log`: متن را در لاگ می‌نویسد (مناسب توسعه).
- قالب‌های پیش‌فرض: OTP، یادآوری هزینه، تأیید/رد فیش، دعوت.
- برای پروداکشن باید کلاسی پیاده‌سازی‌کننده `App\Services\Sms\SmsGateway` بنویسید و در binding سرویس ثبت کنید.

یافتن کد OTP در توسعه (ویندوز):

```cmd
findstr /i "SMS" storage\logs\laravel.log
```

یا محتوای آخرین خطوط `storage/logs/laravel.log` را باز کنید.

---

## دستورات Artisan اختصاصی

```bash
# اعطای اشتراک دستی برای تست
php artisan borjik:grant-subscription {mobile} {units=50} {months=12}

# یادآوری ثبت هزینه (قابل زمان‌بندی در scheduler)
php artisan borjik:remind-expenses   # نام دقیق را با php artisan list | findstr borjik ببینید
```

دستور یادآوری: `App\Console\Commands\RemindExpenses`.

---

## ساختار پوشه‌ها

```
app/
  Console/Commands/     # GrantSubscription، RemindExpenses
  Http/Controllers/     # Auth + دامنه کسب‌وکار
  Http/Middleware/      # building، profile.complete، subscription.active
  Models/
  Services/             # Auth، Charge، Sms، Payment، …
  Support/              # Fa، JalaliDate، MobileNumber، CurrentBuilding، …
config/
  menu.php              # ساختار منو
  sms.php               # درایور و قالب پیامک
database/
  migrations/
  seeders/
public/
  css/                  # app.css، auth.css، jdate.css
  js/                   # app.js، auth.js، jdate.js
resources/views/
  auth/                 # login، profile، two-factor
  layouts/              # app، guest
  …
routes/
  auth.php
  app.php
  web.php
tests/
  Feature/              # OTP، دعوت، شارژ، CRUD، تیم، …
  Unit/                 # ChargeCalculator، MobileNumber، …
```

---

## تست و CI

### محلی

```bash
php artisan test
```

`phpunit.xml` برای تست از SQLite در حافظه (`:memory:`) استفاده می‌کند.

### GitHub Actions

فایل `.github/workflows/ci.yml`:

1. نصب Composer و PHP 8.2
2. بررسی سینتکس PHP
3. اجرای `php artisan test`
4. در صورت موفقیت روی `push` به `main`: ساخت **Artifact** به نام `borjik-release` (ZIP سورس بدون `vendor`، `tests`، `.env`، لاگ و …)

وضعیت اجرا: [Actions](https://github.com/hadiomidvari2497/borjik/actions)

---

## عیب‌یابی رایج

| مشکل | راه حل |
|------|--------|
| `Route [login.reset] not defined` | روت `POST /login/reset` و متد `OtpLoginController@reset` باید وجود داشته باشد؛ سپس `php artisan route:clear` |
| `Table '…sessions' doesn't exist` | `php artisan migrate` یا موقتاً `SESSION_DRIVER=file` |
| `Invalid default value for 'expires_at'` / timestamp | از مایگریشن‌های به‌روز با `dateTime` استفاده کنید؛ در صورت دیتابیس قدیمی: `migrate:fresh --seed` (داده پاک می‌شود) |
| کد OTP نمی‌آید | `SMS_DRIVER=log` و `QUEUE_CONNECTION=sync`؛ لاگ را بخوانید |
| تاریخ تولد `validation.regex` | فرمت `YYYY/MM/DD` شمسی مثل `1370/05/12` |
| `403 شما عضو هیچ ساختمانی نیستید` | اشتراک بدهید (`borjik:grant-subscription`) و به `/buildings/create` بروید |
| Fatal در تست: override `status()` | در تست‌ها نام متد کمکی نباید `status` باشد (تداخل با PHPUnit 11) |
| CSS/JS صفحه ورود قدیمی است | فایل‌های `public/css/auth.css` و `public/js/*` را به‌روز کنید و Hard Refresh (`Ctrl+F5`) |

---

## نکات توسعه

- **صفحات مهمان (ورود/پروفایل):** layout در `resources/views/layouts/guest.blade.php` و استایل جدا در `public/css/auth.css`.
- **اپ اصلی:** `layouts/app.blade.php` + `public/css/app.css`.
- پس از افزودن روت یا تغییر config همیشه کش را پاک کنید.
- برای پروداکشن: `APP_DEBUG=false`، درایور SMS واقعی، queue worker، و پشتیبان‌گیری دیتابیس.
- فایل `.env` را هرگز commit نکنید.

---

## لایسنس

کد اسکلت Laravel تحت [MIT](https://opensource.org/licenses/MIT) است. منطق کسب‌وکار برجیک متعلق به مالک پروژه است؛ قبل از استفاده تجاری با صاحب ریپو هماهنگ کنید.

---

**ریپو:** [github.com/hadiomidvari2497/borjik](https://github.com/hadiomidvari2497/borjik)
