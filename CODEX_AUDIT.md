# Codex Audit - MK Scholars E-learning Platform

Date: 2026-07-07

## Summary

This repository is a Laravel-based e-learning platform, not a JavaScript-only app. It already contains substantial learning-platform functionality: public course pages, session authentication, role-based student/instructor/mentor/admin areas, Filament admin resources, course content, lesson progress, quizzes, assignments, manual payments, subscriptions, certificates, reports, notifications, and feature tests.

The main blocker found during this audit is local runtime availability in the current environment: PHP and Composer are not available on the PATH, `vendor/` is missing, and no `.env` file exists. Because of that, Laravel commands, migrations, tests, and the PHP development server could not be executed here.

## Verified Stack

- Backend framework: Laravel 12, PHP 8.2+
- Frontend: Blade, Livewire 3, Tailwind CSS, Vite
- Admin interface: Filament
- Database: Laravel migrations; `.env.example` defaults to MySQL
- Auth: Laravel session auth with Livewire login/register forms
- Roles: `student`, `instructor`, `mentor`, `admin`
- Package managers: Composer for PHP, npm for frontend assets
- Tests: PHPUnit feature tests under `tests/Feature`

## Key Files Reviewed

- `composer.json`
- `package.json`
- `.env.example`
- `README.md`
- `routes/web.php`
- `bootstrap/app.php`
- `app/Models/User.php`
- `app/Http/Middleware/EnsureUserHasRole.php`
- `app/Livewire/LoginForm.php`
- `app/Livewire/RegisterForm.php`
- `app/Livewire/SetupAdminForm.php`
- `app/Providers/Filament/AdminPanelProvider.php`
- `database/migrations`
- `resources/views`
- `tests/Feature`

## Project Structure

- `app/Models`: domain models for users, courses, lessons, enrollments, quizzes, assignments, payments, subscriptions, certificates, notifications, mentorship, live classes, and reports.
- `app/Livewire`: login, registration, contact, and first-admin setup forms.
- `app/Filament`: admin resources, report pages, and dashboard widgets.
- `app/Services`: notifications, reports, CSV export, certificates, course completion, and payment-provider abstractions.
- `routes/web.php`: public, auth, student, instructor, mentor, admin export, payment, quiz, assignment, certificate, and notification routes. This file is large and closure-heavy.
- `resources/views`: public pages, student pages, instructor pages, mentor pages, admin dashboard placeholder, shared components, Livewire forms, and certificate templates.
- `database/migrations`: complete schema coverage for the main platform modules.
- `tests/Feature`: broad feature-test coverage for public pages, roles, dashboards, quizzes, assignments, payments, certificates, reports, notifications, and course content.

## Runtime Check Results

Commands attempted:

- `php -v`: failed because PHP is not recognized on PATH.
- `php artisan --version`: failed because PHP is not recognized on PATH.
- `composer --version`: failed because Composer is not recognized on PATH.
- `npm --version`: passed with npm `11.11.0`.
- `npm run build`: failed in this sandbox because Vite attempted to load config from the sandbox cwd path and was denied access to the parent directory.

Local state:

- `.env`: missing
- `vendor/`: missing
- `node_modules/`: present

Conclusion: the app cannot be fully run or tested in this environment until PHP, Composer, `vendor/`, `.env`, and a configured database are available.

## Authentication And Security

Implemented:

- Passwords are hashed through Laravel's `hashed` cast and explicit hashing in registration/setup.
- Login uses `Auth::attempt`, session regeneration, and safe validation errors.
- Logout invalidates and regenerates the session token.
- Role middleware protects student/instructor/mentor/admin areas.
- Admin panel access is restricted to approved admin users through `canAccessPanel`.
- Instructor and mentor approval statuses are enforced.
- Student access to courses, quizzes, assignments, certificates, payments, and documents is checked in routes.
- Upload validations exist for student documents, payment proofs, and assignment submissions.
- YouTube URLs are validated and rendered through helper/rule classes.
- Rich course content has sanitation support.

Needs follow-up:

- There is no password reset flow visible in the audited files.
- `routes/web.php` is very large and closure-heavy, so route caching is intentionally not recommended in the README.
- The `/setup-admin` route is public by design but must be monitored carefully in production because it creates the first admin if no admin exists.
- Future payment webhooks are explicitly disabled placeholders.

## Database Coverage

Migrations exist for:

- Users and approval fields
- Academies
- Courses and instructor ownership
- Modules and lessons
- Lesson activities
- Enrollments
- Lesson progress
- Quizzes, questions, options, attempts, and answers
- Assignments, questions, submissions, and question answers
- Live classes and attendance
- Mentor assignments and check-ins
- Certificates and certificate skills
- Opportunities and applications
- Student documents
- Payment methods and payments
- Course completion rules and completions
- App notifications
- Subscription plans, plan courses, and subscriptions
- Course reviews

## API And Routing Notes

This app primarily uses web routes and server-rendered Blade/Livewire flows rather than a separate JSON API. Public, student, instructor, mentor, and admin/report routes are present. There are no separate `routes/api.php` endpoints in the inspected file list.

Important route groups present:

- Public: home, academies, courses, course details, pricing, about, contact, certificate verification
- Auth: login, register, logout, setup admin
- Student: dashboard, settings, notifications, subscriptions, documents, enrollments, payments, my courses, learning page, reviews, lesson completion, quizzes, assignments, live classes, mentorship, certificates
- Instructor: dashboard, settings, course list/create/edit/update, module/lesson/quiz/assignment creation, students, submissions, quiz attempts, live classes, notifications
- Mentor: dashboard, settings, notifications, students, check-ins
- Admin: Filament resources plus report CSV exports

## UI Status

Implemented:

- Public pages for home, academies, courses, course details, pricing, about, contact, certificate verification, login, and register.
- Student workspace pages for dashboard, my courses, learning, quizzes, assignments, subscriptions, payments, documents, live classes, mentorship, certificates, notifications, and settings.
- Instructor workspace pages for dashboard, course management, content builder, students, submissions, quiz attempts, live classes, notifications, and settings.
- Mentor workspace pages for dashboard, students, check-ins, notifications, and settings.
- Filament admin resources for most operational entities.
- Shared Blade components for layout, cards, badges, buttons, nav, empty states, and progress cards.

Needs follow-up:

- `resources/views/admin/dashboard.blade.php` is a protected placeholder, although Filament is the main admin interface.
- Visual QA could not be completed because the app could not be started in this environment.

## Tests

Feature tests are present for:

- Public pages
- Dashboard/role layout
- User approval
- Instructor course ownership and builder flows
- Quizzes
- Assignments
- Payments/subscriptions
- Certificates
- Reports and CSV exports
- Notifications
- Settings
- Rich course content
- YouTube lessons
- Academy visuals
- Email notification service

Tests could not be run because PHP and Composer are unavailable in the current environment.

## Main Risks

- Local verification is blocked until PHP/Composer/vendor/env/database are available.
- The large route file increases maintenance risk and makes route caching unavailable.
- The public first-admin setup route should be reviewed before production launch.
- Password reset is not yet visible.
- Payment provider integrations beyond manual payments are placeholders.
- Production file storage still needs an explicit secure storage decision.

## Recommended Immediate Actions

1. Install PHP 8.2+ and Composer on the local machine or use Laravel Sail/Docker.
2. Run `composer install`.
3. Copy `.env.example` to `.env` and configure the database.
4. Run `php artisan key:generate`.
5. Run `php artisan migrate --seed`.
6. Run `php artisan test`.
7. Run `npm run build` in a normal local terminal.
8. Start the app with `php artisan serve` or the README server command and manually verify public, student, instructor, and admin flows.
9. Refactor high-risk route closures into controllers after tests are green.
