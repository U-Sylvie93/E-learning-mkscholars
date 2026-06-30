# MK Scholars E-learning Platform

MK Scholars is a Laravel-based e-learning platform for student courses, mentorship, live classes, assignments, quizzes, manual payments, certificates, notifications, and admin reporting.

The project uses Blade and Livewire for the application UI, Tailwind CSS for the MK Scholars navy/gold/white design system, Filament for admin operations, and migrations that are ready for SQLite local testing and MySQL production deployment.

## Requirements

- PHP 8.2+
- Composer
- Node.js and npm
- SQLite for local testing or MySQL for production-like testing
- A writable `storage` directory
- A public storage link for uploaded demo files

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
php artisan migrate
php artisan db:seed
php artisan storage:link
```

Start the local server:

```bash
php -S 127.0.0.1:9000 -t public server.php
```

Open:

```text
http://127.0.0.1:9000
```

## Demo Data

Running `php artisan db:seed` creates local demo data through `DemoSeeder`. The seeder is idempotent and skips production environments.

Local demo credentials:

```text
Admin:      admin@mkscholars.test / password
Student:    student@mkscholars.test / password
Instructor: instructor@mkscholars.test / password
Mentor:     mentor@mkscholars.test / password
```

These accounts are for local testing and demo presentation only. Do not seed demo users into a production database.

## Demo Coverage

The demo seeder creates:

- 5 MK Scholars academies
- 3 published demo courses
- Modules, lessons, and lesson activities
- 1 quiz with questions and options
- 1 assignment
- 1 scheduled live class
- 1 active manual payment method
- 1 active demo subscription plan with included paid courses
- Admin, student, instructor, and mentor demo users

## Useful Commands

```bash
php artisan route:list
php artisan migrate:status
php artisan view:clear
php artisan optimize:clear
npm run build
```

Do not run destructive commands such as `migrate:fresh` against shared or production-like data unless you intentionally want to erase the database.

This application currently uses route closures in `routes/web.php`; keep `php artisan route:list` in the launch checks, but do not enable `route:cache` until routes are moved to controllers and verified.

## Email Notifications

In-app notifications remain the source of truth. Email notifications are disabled by default for local and demo environments:

```env
MK_EMAIL_NOTIFICATIONS_ENABLED=false
MAIL_MAILER=log
```

To test email locally without sending real email, keep `MAIL_MAILER=log` or use Laravel's `array` mailer and set `MK_EMAIL_NOTIFICATIONS_ENABLED=true`. Configure real SMTP credentials only in a secure `.env`; do not commit credentials.

## Payment Provider Preparation

Manual payments are still the only active payment flow. Payments now include provider metadata fields so future MTN MoMo, Airtel Money, Stripe, or PayPal integrations can be added without changing the current student/admin workflow.

Current lifecycle:

- Student chooses a paid course or subscription plan.
- The app creates a pending manual payment.
- Student uploads proof through `/student/payments/{payment}`.
- Admin approves or rejects the proof in Filament.
- Approval activates the course enrollment or subscription access.

`POST /payments/webhooks/{provider}` is a disabled placeholder for future provider callbacks. It does not approve payments, activate enrollments, or activate subscriptions.

## Certificate PDF Downloads

Issued certificates can be downloaded from the student certificate detail page. The app renders certificates from a Blade template and uses `barryvdh/laravel-dompdf` when that package is installed. If the package is not installed yet, the download route falls back to a printable HTML certificate instead of crashing.

To enable true PDF rendering, install the package locally or in deployment:

```bash
composer require barryvdh/laravel-dompdf
```

## Testing Checklist

Use [TESTING_CHECKLIST.md](TESTING_CHECKLIST.md) for a full manual QA walkthrough.

## Deployment Notes

Use [DEPLOYMENT_NOTES.md](DEPLOYMENT_NOTES.md) before preparing a staging or production environment.
