# MK Scholars Deployment Notes

These notes are for staging or production preparation. They do not replace a secure hosting checklist.

## Environment

- Set `APP_ENV=production`.
- Set `APP_DEBUG=false`.
- Set a real `APP_URL`.
- Use a strong production `APP_KEY`.
- Configure production database credentials.
- Set `FILESYSTEM_DISK=public` unless you intentionally move uploads to another configured disk.
- Keep `MK_EMAIL_NOTIFICATIONS_ENABLED=false` until Laravel mail settings are verified.
- Configure `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, and `MAIL_FROM_NAME` in `.env` before enabling email notifications.
- Do not run demo seeders in production. `DemoSeeder` is guarded, but production deployment should still avoid demo credentials.
- Create the first production admin through `/setup-admin`; do not use local demo credentials in production.

## Install And Build

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan view:cache
```

This project currently defines many web routes as closures. Do not run `php artisan route:cache` until those routes are moved to controllers and verified in staging.

For staging/demo environments, run seeders only when you intentionally want sample content:

```bash
php artisan db:seed
```

## Database

- SQLite is suitable for local testing.
- MySQL is recommended for production.
- Confirm all migrations have run with:

```bash
php artisan migrate:status
```

- Do not use destructive commands such as `migrate:fresh` on production data.

## Storage And Uploads

- Run `php artisan storage:link`.
- Ensure `storage/app/public` and `storage/logs` are writable by the web server user.
- Disable directory listing on the web server.
- Keep uploaded proof/document URLs private in practice by sharing them only through authenticated admin/student/instructor screens.
- Consider moving sensitive uploads such as payment proofs, student documents, applications, and assignment submissions to a private disk before handling real production data.
- Back up uploaded files, including:
  - assignment submissions
  - application documents
  - student reusable documents
  - payment proofs
  - course images

## Security Reminders

- Remove or rotate any local demo credentials before production launch.
- Use HTTPS for all public traffic.
- Restrict admin access to trusted users with `role = admin`.
- Keep `APP_DEBUG=false` in production.
- Review file upload limits and allowed MIME types at the web server level.
- Keep Composer and npm dependencies updated.
- Protect `.env` from public access.
- Set regular database and storage backups.
- Monitor `storage/logs/laravel.log` or the configured production log channel.
- Confirm web server upload size limits match Laravel validation limits.
- Verify POST forms include CSRF tokens during browser smoke testing.

## Payment Provider Reminders

- Manual payment proof upload remains the active production-ready payment path.
- Do not add real provider credentials to the repository.
- Keep future provider secrets in `.env` only.
- The `/payments/webhooks/{provider}` route is a disabled placeholder and should not be treated as a live payment callback.
- Before enabling a real provider, add signature verification, idempotency checks, provider status mapping, and tests proving callbacks cannot double-approve a payment.
- Confirm manual payment reports still count approved manual revenue after provider metadata migrations run.

## Certificate PDF Reminders

- Install `barryvdh/laravel-dompdf` with `composer require barryvdh/laravel-dompdf` before expecting binary PDF output.
- Without the PDF package, certificate downloads fall back to printable HTML so students are not blocked.
- Keep certificate verification public at `/certificates/verify/{verification_code}`.
- Student PDF downloads must remain protected by login and certificate ownership.

## Launch Smoke Test

- Public pages load with built assets.
- `APP_ENV=production` and `APP_DEBUG=false` are confirmed.
- `APP_KEY` and `APP_URL` are set correctly.
- Login/register/logout works.
- Admin can access Filament at `/admin`.
- Student dashboard opens.
- Instructor dashboard opens.
- Mentor dashboard opens.
- Student, instructor, mentor, and admin role protections work.
- Course list and course detail pages open.
- Free course enrollment works.
- Manual payment approval creates/activates enrollment.
- Manual subscription payment approval activates subscription access.
- Subscription renewal payment can be submitted and approved once.
- Future payment provider webhook placeholder returns disabled/not implemented and does not approve records.
- Student learning page opens only for allowed courses.
- Course review submit/publish flow works.
- Issued certificate PDF download works for the owner and includes the public verification link.
- Quiz attempt flow works.
- Assignment upload/submission flow works.
- Certificate verification route works publicly.
- In-app notifications load for each role.
- If `MK_EMAIL_NOTIFICATIONS_ENABLED=true`, test payment, assignment, certificate, and application emails with a safe mail driver before using real SMTP.
- Storage upload and file download/open links work after `php artisan storage:link`.
- Production assets from `public/build` load without dev server or `public/hot`.
