# MedFind Solutions

A Laravel-based healthcare directory platform connecting patients with specialist doctors and medical facilities in Kenya.

---

## Table of Contents

1. [Application Architecture](#1-application-architecture)
2. [Technology Stack](#2-technology-stack)
3. [User Roles](#3-user-roles)
4. [Local Development Setup](#4-local-development-setup)
5. [Setup Wizard Walkthrough](#5-setup-wizard-walkthrough)
6. [cPanel Deployment Guide](#6-cpanel-deployment-guide)
7. [Environment Variables Reference](#7-environment-variables-reference)
8. [SMTP Configuration](#8-smtp-configuration)
9. [Paystack Integration](#9-paystack-integration)
10. [Test Credentials](#10-test-credentials)
11. [Subscription Packages](#11-subscription-packages)
12. [Key URLs](#12-key-urls)

---

## 1. Application Architecture

```
medfindsolutions.co.ke/
├── app/                          # Core Laravel application code
│   ├── Http/Controllers/
│   │   ├── Admin/                # Admin panel controllers
│   │   ├── Auth/                 # Patient authentication (login, register, verify)
│   │   ├── Clinic/Auth/          # Clinic authentication + dashboard
│   │   ├── Doctor/               # Doctor portal controllers
│   │   └── Doctor/Auth/          # Doctor authentication
│   ├── Models/                   # Shared models (Admin, User, UserDetails…)
│   ├── Providers/
│   │   └── AppServiceProvider.php  # Bootstraps DB-driven mail, timezone, Pusher config
│   └── Traits/                   # GlobalMailTrait, GetGlobalInformationTrait…
│
├── Modules/                      # nwidart/laravel-modules — each is a self-contained feature
│   ├── App/                      # Mobile API + app-specific logic
│   ├── Appointment/              # Appointment booking + payment flow
│   ├── BasicPayment/             # Payment gateway config (Stripe, PayPal, Paystack…)
│   ├── Blog/                     # Blog / articles
│   ├── Currency/                 # Multi-currency support
│   ├── Doctor/                   # Doctors, departments, locations, subscriptions, clinics
│   ├── GlobalSetting/            # Site-wide settings (stored in DB), SMTP, SEO, email templates
│   ├── Installer/                # Setup wizard (5-step: requirements → DB → account → config → SMTP)
│   ├── Language/                 # Translations + RTL support
│   ├── Order/                    # Order / payment records
│   ├── Schedule/                 # Doctor availability schedules
│   └── …                        # 15+ further modules
│
├── resources/views/
│   ├── layouts/patient/          # Public-facing layout (header, footer, verify-banner)
│   ├── patient/profile/auth/     # Login, register, provider-register, subscription blades
│   ├── doctor/                   # Doctor portal layout + dashboard
│   └── clinic/                   # Clinic portal layout + dashboard
│
├── routes/
│   ├── web.php                   # Minimal — delegates to module route files
│   ├── patient.php               # Patient auth + dashboard routes
│   ├── doctor.php                # Doctor + clinic auth + dashboard routes
│   └── admin.php                 # Admin panel routes
│
├── database/
│   ├── migrations/               # Core migrations
│   ├── seeders/
│   │   ├── DatabaseSeeder.php    # Master seeder (calls all module seeders)
│   │   └── MedfindTestSeeder.php # Local-only test accounts seeder
│   └── Modules/*/migrations      # Per-module migrations
│
├── config/
│   ├── auth.php                  # Guards: web (patients), doctor, clinic, admin, api
│   └── mail.php                  # Mail defaults — overridden at runtime by AppServiceProvider
│
└── .env                          # Local environment — SMTP credentials live here
```

### How settings flow at runtime

```
.env (base defaults)
  ↓
config/mail.php (reads env())
  ↓
AppServiceProvider::boot()
  ├── Loads settings table from DB (cached forever in 'setting' cache key)
  ├── If DB mail values are real  → overrides config() with DB values
  └── If DB mail values are placeholders → keeps .env values (fallback)
```

This means:
- **Fresh install** (DB has Mailtrap placeholders) → mail is sent using `.env` credentials immediately.
- **After setup wizard / admin panel config** → DB values take over and `.env` mail is ignored.
- To reset to `.env` mail: clear the `setting` cache key (`php artisan cache:clear`).

---

## 2. Technology Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 10 (PHP 8.1+) |
| Module system | nwidart/laravel-modules 10 |
| Database | MySQL 8 |
| Authentication | Laravel guards (web / doctor / clinic / admin / api) |
| Payments | Paystack, Stripe, PayPal, Razorpay, Flutterwave, Bank transfer |
| File storage | Local disk (public) — `storage:link` required |
| Real-time | Pusher (optional) |
| PDF | barryvdh/laravel-dompdf |
| Search/translate | Stichoza Google Translate |
| Image processing | Intervention Image 3 |

---

## 3. User Roles

| Role | Guard | Login URL | Dashboard |
|---|---|---|---|
| Super Admin | `admin` | `/admin/login` | `/admin/dashboard` |
| Doctor | `doctor` | `/login?type=doctor` | `/doctor/dashboard` |
| Clinic / Facility | `clinic` | `/login?type=clinic` | `/clinic/dashboard` |
| Patient / Client | `web` | `/login` | `/dashboard` |

---

## 4. Local Development Setup

### Prerequisites

- PHP 8.1+ with extensions: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO MySQL, Tokenizer, XML, Zip, Intl
- Composer 2
- MySQL 8
- Node.js 18+ and npm (for front-end assets)

### Steps

```bash
# 1. Clone / extract the project
cd /path/to/project

# 2. Install PHP dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Create a local database
mysql -u root -p -e "CREATE DATABASE medfind_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6. Edit .env — set your DB credentials and SMTP (see sections 7 & 8 below)

# 7. Run migrations + seed demo data
php artisan migrate --seed

# 8. Seed local test accounts (runs automatically when APP_ENV=local, but can also be run manually)
php artisan db:seed --class=MedfindTestSeeder

# 9. Link storage
php artisan storage:link

# 10. Install and build front-end assets
npm install && npm run build

# 11. Start the dev server
php artisan serve
```

The app will be available at `http://localhost:8000`.

> **Note:** If you already have a database with data and want to add only new migrations without losing existing records, run `php artisan migrate` (not `migrate:fresh`).

---

## 5. Setup Wizard Walkthrough

The setup wizard lives at `/setup/requirements` and runs 5 steps. It is the recommended way to configure a fresh deployment.

```
Step 1 — Requirements check
  Verifies PHP version, required extensions, and file permissions.
  Fix any ❌ items before proceeding.

Step 2 — Database
  Enter MySQL host, port, database name, user, and password.
  ┌─────────────────────────────────────────────────────────────────┐
  │ IMPORTANT — Existing data safety:                               │
  │ • If the database is EMPTY   → migrations + seeders run fresh.  │
  │ • If the database HAS tables → only NEW migrations run.         │
  │   Your existing data is NEVER dropped unless you enable the     │
  │   "WIPE database" toggle explicitly.                            │
  └─────────────────────────────────────────────────────────────────┘

Step 3 — Admin account
  Creates (or updates) the super-admin account.
  Pick a strong email and password — you will use these for the
  admin panel at /admin/login.

Step 4 — Configuration
  Sets the application name stored in the database.

Step 5 — SMTP
  Configure your outgoing email server.
  See Section 8 for credential details.
  You can skip this step and configure it later in
  Admin → Settings → Email Configuration.
  After skipping, mail falls back to .env automatically.
```

After completing all steps, click **Launch Website** to:
- Set `APP_ENV=production` and `APP_DEBUG=false` in `.env` automatically.
- Clear all caches.
- Redirect to the public homepage.

---

## 6. cPanel Deployment Guide

### File Upload

```
Option A — File Manager
  1. Zip the entire project folder.
  2. Upload to /home/<user>/ via cPanel File Manager.
  3. Extract.

Option B — Git (if your host supports it)
  Use cPanel → Git Version Control to clone the repository.
```

### Directory Structure on cPanel

cPanel serves files from `public_html`. Laravel's public folder must be the document root.

**Two ways to handle this:**

**Option 1 — Recommended: Place files above public_html**

```
/home/<cpanel-user>/
├── medfindsolutions/          ← upload the full Laravel project here
│   ├── app/
│   ├── public/                ← this becomes the public_html symlink target
│   └── ...
└── public_html/               ← point this to medfindsolutions/public
```

In cPanel → Domains → Document Root, set it to `medfindsolutions/public`.

**Option 2 — Move public_html contents into public/**

1. Upload the entire project to `/home/<user>/medfindsolutions/`.
2. Copy everything inside `public/` into `public_html/`.
3. Edit `public_html/index.php` — update the two paths:

```php
// Before
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// After
require __DIR__.'/../medfindsolutions/vendor/autoload.php';
$app = require_once __DIR__.'/../medfindsolutions/bootstrap/app.php';
```

4. Edit `public_html/.htaccess` — the project root `.htaccess` already handles PHP handler selection; no change needed.

### PHP Version

In cPanel → MultiPHP Manager, set the domain to **PHP 8.1** or **PHP 8.3**.

The project `.htaccess` already includes:
```apache
AddHandler application/x-httpd-ea-php83 .php .php8 .phtml
```
Change `ea-php83` to match your host's PHP 8.x handler name if different.

### Required PHP Extensions (enable in cPanel → PHP Extensions)

`bcmath`, `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`, `intl`, `gd` or `imagick`

### Environment File

Copy `.env.example` to `.env` and fill in production values:

```bash
# In SSH terminal
cd /home/<user>/medfindsolutions
cp .env.example .env
php artisan key:generate
```

### Composer & npm on cPanel

```bash
# SSH into your hosting account, then:
cd /home/<user>/medfindsolutions

# Install PHP dependencies
php8.3 /usr/local/bin/composer install --no-dev --optimize-autoloader

# Build front-end assets (if Node is available on your host)
npm install && npm run build
# OR build locally and upload the compiled dist/ folder

# Link storage
php artisan storage:link

# Run the setup wizard at https://yourdomain.com/setup/requirements
# OR manually run migrations:
php artisan migrate --seed
```

### File Permissions

```bash
chmod -R 755 /home/<user>/medfindsolutions
chmod -R 775 /home/<user>/medfindsolutions/storage
chmod -R 775 /home/<user>/medfindsolutions/bootstrap/cache
chmod 644 /home/<user>/medfindsolutions/.env
```

### Cron Job (for scheduled tasks)

In cPanel → Cron Jobs, add:

```
* * * * * php /home/<user>/medfindsolutions/artisan schedule:run >> /dev/null 2>&1
```

### Post-Deployment Checklist

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

Set in `.env`:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

---

## 7. Environment Variables Reference

```env
# ── Application ──────────────────────────────────────────────────
APP_NAME="MedFind Solutions"
APP_ENV=local                    # local | production
APP_KEY=base64:...               # generated by php artisan key:generate
APP_DEBUG=true                   # false in production
APP_URL=http://localhost:8000    # your full public URL in production
APP_MODE=LIVE                    # LIVE | DEMO

# ── Database ─────────────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=medfind_local
DB_USERNAME=medfind_user
DB_PASSWORD=your_db_password

# ── Mail (SMTP) — see Section 8 for full details ─────────────────
MAIL_MAILER=smtp
MAIL_HOST=mail.medfindsolutions.co.ke
MAIL_PORT=465
MAIL_USERNAME=noreply@medfindsolutions.co.ke
MAIL_PASSWORD="your_smtp_password"
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@medfindsolutions.co.ke
MAIL_FROM_NAME="MedFind Solutions"

# ── Cache / Session / Queue ───────────────────────────────────────
CACHE_DRIVER=file                # file | redis | database
SESSION_DRIVER=file
QUEUE_CONNECTION=sync            # sync | database | redis

# ── Pusher (optional real-time) ───────────────────────────────────
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1
```

> **Important:** SMTP credentials in `.env` are the fallback source.
> After running the setup wizard or configuring email in the admin panel
> (Admin → Settings → Email Configuration), the DB values take priority.
> The `.env` values are used automatically when the DB still has placeholder values.

---

## 8. SMTP Configuration

### Where credentials are stored

| Source | When used |
|---|---|
| `.env` file | Always used on fresh install; fallback when DB has placeholders |
| `settings` DB table (keys: `mail_host`, `mail_port`, `mail_username`, `mail_password`, `mail_encryption`, `mail_sender_email`, `mail_sender_name`) | Takes over after setup wizard Step 5 or admin panel config |

### Priority logic (AppServiceProvider)

```
DB mail_host = "sandbox.smtp.mailtrap.io"   →  use .env
DB mail_username = "mail_username"           →  use .env
DB mail values are real/configured           →  use DB (ignores .env)
```

### MedFind production SMTP

```
Host:       mail.medfindsolutions.co.ke
Port:       465
Encryption: SSL
Username:   noreply@medfindsolutions.co.ke
Password:   <stored securely — do not commit to git>
From Name:  MedFind Solutions
```

### Updating SMTP after deployment

**Option A — Admin panel (recommended)**
```
Admin → Settings → Email Configuration
Fill in the form → Save → click "Test Mail"
```

**Option B — Artisan (direct DB update)**
```bash
php artisan tinker --execute="
use Modules\GlobalSetting\app\Models\Setting;
use Illuminate\Support\Facades\Cache;

\$updates = [
    'mail_host'         => 'mail.example.com',
    'mail_port'         => '465',
    'mail_encryption'   => 'ssl',
    'mail_username'     => 'noreply@example.com',
    'mail_password'     => 'your_password',
    'mail_sender_email' => 'noreply@example.com',
    'mail_sender_name'  => 'Your App Name',
];
foreach (\$updates as \$k => \$v) Setting::where('key', \$k)->update(['value' => \$v]);
Cache::forget('setting');
echo 'Done';
"
```

---

## 9. Paystack Integration

All four provider types — **specialists (doctors), facilities (clinics), medical suppliers and healthcare schools** — pay for their subscription through Paystack hosted checkout (card / M-Pesa / bank). Bank-transfer and other gateways are retired.

### How it works

```
Provider registers → plan page → "Continue to Paystack"
  POST /provider/{type}/checkout/{token}     (new sign-ups)
  POST /provider/{type}/renew                (signed-in renewals)
    ↓ server creates a pending subscription + payment attempt and redirects to checkout.paystack.com
Provider pays on Paystack
  ├── Browser returns to  GET  /provider/paystack/callback?reference=...   (callback URL)
  │     server verifies the reference with Paystack, activates the plan,
  │     redirects to the portal (or login) with a success message
  ├── Paystack also sends POST /api/provider-payments/paystack/webhook   (webhook URL)
  │     signed with your secret key; activates the plan even if the browser was closed
  └── "Cancel" on Paystack returns to the provider's plan page
```

Payment never publishes a listing by itself: admin still approves the profile and documents.

Where providers find the pay/renew page after sign-up:

| Provider | Link |
|---|---|
| Specialist | Sidebar → **My Plan / Renew** or the dashboard button (`/listing-plans/doctor`) |
| Facility | Sidebar → **Subscription** (`/clinic/subscription`) |
| School / Supplier | Sidebar → **Subscription / renew** (`/partner/{type}/subscription`) |

### Paystack dashboard setup (required)

In the Paystack dashboard → **Settings → API Keys & Webhooks**, set:

| Field | Value |
|---|---|
| Callback URL | `https://YOUR-DOMAIN/provider/paystack/callback` |
| Webhook URL | `https://YOUR-DOMAIN/api/provider-payments/paystack/webhook` |

The exact URLs for your site, with copy buttons, are shown in **Admin → Settings → Payment Gateway → Paystack**. Set them for both Test and Live mode. `APP_URL` in `.env` must be your public https domain.

### Paystack test keys (seeded by default)

```
Public key:  pk_test_057dfe5dee14eaf9c3b4573df1e3760c02c06e38
Secret key:  sk_test_77cb93329abbdc18104466e694c9f720a7d69c97
Currency:    KES
```

### Switching to live keys

Admin → Settings → Payment Gateway → Paystack
- Enter your live public and secret keys from the Paystack dashboard.
- Set status to **Active**.
- Make sure the Live callback and webhook URLs above are saved in the Paystack dashboard.

---

## 10. Test Credentials

These accounts are seeded automatically when `APP_ENV=local`. Run manually with:

```bash
php artisan db:seed --class=MedfindTestSeeder
```

| Role | Email | Password | Notes |
|---|---|---|---|
| Admin | `admin@medfind.test` | `Admin@1234` | Super admin, full permissions |
| Doctor | `doctor@medfind.test` | `Doctor@1234` | Active, Premium plan approved |
| Clinic | `clinic@medfind.test` | `Clinic@1234` | Active, Executive plan approved |
| Customer | `customer@medfind.test` | `Customer@1234` | Verified, ready for appointments |

**Original demo accounts (from base seeders):**

| Role | Email | Password |
|---|---|---|
| Admin | `admin@gmail.com` | `1234` |
| Doctor | `doctor@gmail.com` | `1234` |
| Patient | `patient@gmail.com` | `1234` |

> Do not use these accounts in production. Change all passwords after first login.

---

## 11. Subscription Packages

### Doctor Plans

| Plan | Price | Duration | Key features |
|---|---|---|---|
| Basic | KSh 1,000 | 1 month | Profile listing, search visibility, call/email contact |
| Premium | KSh 3,500 | 3 months | Everything in Basic + verified badge, higher ranking, WhatsApp button, patient inquiry form, analytics |
| Executive | KSh 10,000 | 6 months | Everything in Premium + homepage rotation, Top Specialist badge, reviews & ratings, full analytics, emergency tag |

### Clinic / Facility Plans

| Plan | Price | Duration | Key features |
|---|---|---|---|
| Basic | KSh 3,000 | 1 month | Name, specialty, logo, phone, 3 services |
| Premium | KSh 10,000 | 3 months | Everything in Basic + cover photo, email, WhatsApp, description, insurance, 10 services, 5 highlights |
| Executive | KSh 15,000 | 6 months | Everything in Premium + 15 gallery photos, 20 services, equipment list, reviews, emergency info, maps link, ⭐ Top Clinic badge |

### Payment flow

- **Paystack** → subscription approved instantly after payment verification.
- **Bank / M-Pesa transfer** → subscription stays `pending` until admin approves in Admin → Subscriptions.

Admin subscription management:
```
Admin → Subscriptions → Doctor Subscriptions  (/admin/subscriptions/doctors)
Admin → Subscriptions → Clinic Subscriptions  (/admin/subscriptions/clinics)
```

---

## 12. Key URLs

| URL | Description |
|---|---|
| `/` | Public homepage |
| `/login` | Shared login (Client / Doctor / Clinic tabs) |
| `/register` | Client registration |
| `/provider/register` | Provider registration (Doctor + Clinic) — not publicly linked |
| `/provider/register?type=clinic` | Opens clinic tab directly |
| `/dashboard` | Patient dashboard |
| `/doctor/dashboard` | Doctor portal dashboard |
| `/clinic/dashboard` | Clinic portal dashboard |
| `/admin/login` | Admin panel login |
| `/admin/dashboard` | Admin panel |
| `/admin/subscriptions/doctors` | Doctor subscription management |
| `/admin/subscriptions/clinics` | Clinic subscription management |
| `/setup/requirements` | Setup wizard (only works before `launchWebsite` is called) |
| `/admin/basicpayment` | Payment gateway configuration |
| `/admin/email-configuration` | SMTP configuration |

---

## Security Notes

- Never commit `.env` to version control — it is in `.gitignore`.
- Set `APP_DEBUG=false` in production.
- The provider registration URL (`/provider/register`) is intentionally not linked in the public navigation. Share it only with doctors and clinics directly.
- All subscription payments via Paystack are server-verified — the client cannot fake a successful payment.
- Admin subscription approve/reject actions are protected by the `doctor.update` permission.

---

*Content was summarised and formatted for this project's setup documentation.*




Not fully yet. It now covers much more of the rubric, including provider subscriptions, license submissions/review, FAQ content, legal pages, clinic galleries/branches, inquiries, reviews, and expiry reminders.

Still incomplete or only partial:

- **Verification enforcement:** homepage/API/search paths must consistently require approved licenses; current checks were added to core public detail/listing paths only.
- **Admin reporting:** CSV/Excel/PDF exports, revenue/inquiry reports, and full analytics dashboard.
- **Review moderation:** provider “report review” action and admin moderation queue.
- **Payments:** M-Pesa automation, payment receipts, robust renewal/upgrade/downgrade, and payment reporting.
- **Search:** complete rubric filters/autocomplete for all specialist/clinic fields.
- **Provider fields:** some package-gated fields and standardized dropdowns still need full end-to-end enforcement.
- **Security/operations:** audit logs, CAPTCHA, backups, and full SEO/schema/sitemap work.

So it is substantially closer, but I would not call it rubric-complete yet.