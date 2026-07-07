# Next Steps - MK Scholars E-learning Platform

## Priority 1: Make The Project Runnable

1. Install or expose PHP 8.2+ on PATH.
2. Install Composer.
3. Run `composer install`.
4. Create `.env` from `.env.example`.
5. Set `APP_KEY` with `php artisan key:generate`.
6. Configure a local database. SQLite is simplest for local testing; MySQL matches `.env.example`.
7. Run migrations and seeders.
8. Run `php artisan test`.
9. Run `npm run build`.
10. Start the local server and manually test the main routes.

## Priority 2: Confirm Existing Feature Health

Verify these flows in the browser after the runtime works:

- Public home, academies, course listing, course details, pricing, about, contact
- Register as student
- Register as instructor and confirm pending approval behavior
- Login and logout
- Admin first setup if no admin exists
- Admin Filament access and user approval
- Student enrollment into free course
- Paid course or subscription manual payment flow
- Lesson viewing and completion
- Quiz attempt and scoring
- Assignment submission and grading/review
- Certificate verification and download
- Instructor course creation and content builder
- Report pages and CSV exports

## Priority 3: Close Known Product Gaps

- Add or confirm a password reset flow.
- Decide whether mentor functionality is enabled or intentionally hidden for launch.
- Replace the protected admin placeholder page or redirect it fully to Filament.
- Decide production storage for uploads: local disk for small deployments, cloud storage for production scale.
- Finish real payment-provider integrations only after webhook security, idempotency, and audit logging are designed.

## Priority 4: Reduce Maintenance Risk

- Move large route closures from `routes/web.php` into controllers.
- Keep existing tests passing during the route refactor.
- Add focused tests for any new controller extraction.
- Keep route caching disabled until closure routes are removed.
- Review naming consistency between "teacher" in product language and "instructor" in code.

## Priority 5: Production Readiness

- Configure mail delivery.
- Configure queue driver if email, certificates, or notifications become heavier.
- Configure backups.
- Configure secure storage and file visibility rules.
- Set `APP_ENV=production`, `APP_DEBUG=false`, and real `APP_URL`.
- Confirm no demo credentials or seed data are present in production.
- Run full manual QA from `TESTING_CHECKLIST.md`.
