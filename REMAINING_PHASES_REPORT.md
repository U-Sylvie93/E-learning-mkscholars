# MK Scholars Remaining Phases Report

This report is a handoff document for continuing the MK Scholars E-learning Platform in a future GPT/Codex session.

## Current Project Status

MK Scholars is a Laravel, Blade, Livewire, Tailwind CSS, MySQL/SQLite-ready, Filament-powered e-learning platform.

The platform currently includes:

- Public website pages with MK Scholars navy/gold/white branding
- Authentication and role-based access
- First admin setup
- Filament admin panel
- Academies, courses, modules, lessons, and activities
- Student enrollment and learning workspace
- Quiz system
- Assignment submissions and grading
- Live classes and attendance
- Mentorship and weekly check-ins
- Certificates and public verification
- Student document center
- Manual course payments
- Course completion rules and certificate eligibility
- In-app notifications
- Admin analytics and reports
- UI/mobile polish
- Demo data and launch documentation
- Manual subscription plans and subscription-based course access

Do not convert the project to React. Continue using Laravel, Blade, Livewire, Tailwind CSS, Filament, and database-backed Laravel models.

## Recently Completed Phase

### Phase 39A: Instructor Course Creation Studio

Code changes added for owner verification:

- Instructor course create/edit form is now a guided course studio inside the instructor dashboard shell.
- Instructor course image upload now reuses the existing `featured_image_path` field, `public` disk, `courses` directory, and JPG/PNG/WebP 4MB rule used by admin Filament.
- Instructor course list and instructor course preview show course cover images with clean fallback states.
- Existing instructor ownership checks remain in the route layer.
- Manual payment/free course fields remain on the existing `access_type`, `price_amount`, and `currency` fields.
- `TESTING_CHECKLIST.md` includes Phase 39A manual QA.

Manual verification is still required because this phase was code-only: run migrations if needed, run tests, build assets, and check the instructor flow in a browser.

### Phase 40A: Admin Control Center Improvements

Code changes added for owner verification:

- Certificate pages now use a more formal MK Scholars academy-style certificate design while preserving existing PDF and printable HTML fallback behavior.
- Admin account settings were added inside Filament for own-profile updates and password changes with current-password confirmation.
- A `viewer` role was added as a read-only admin observer role.
- Filament resources now share a read-only viewer guard that blocks create, edit, delete, and bulk delete permissions for viewer accounts.
- Subscription admin tables now show subscriber email, plan, amount, payment status, expiry state, and useful filters.
- The Filament user menu includes a View Website link back to the public home page.
- `TESTING_CHECKLIST.md` includes Phase 40A manual QA.

Manual verification is still required because this phase was code-only: run migrations if needed, run tests, build assets, and check the admin panel in a browser.

### Phase 41A: Instructor Quiz Builder Upgrade

Code changes added for owner verification:

- Instructor course builder quiz creation now includes quiz instructions, passing score, time limit, attempt limit, draft/published status, and publish action.
- Instructors can add questions to existing owned quizzes from the course builder.
- Question creation supports single-choice, multiple-choice, and true/false question types.
- Question options are no longer limited to two choices; the builder supports additional option rows and correct-answer controls.
- Student guided quiz mode now supports checkbox-based multiple-choice submissions.
- Multiple-choice scoring requires the selected option set to exactly match all correct options.
- A migration adds `selected_option_ids` to quiz answers for exact-set scoring while preserving the existing single selected option field.
- `TESTING_CHECKLIST.md` includes Phase 41A manual QA.

Manual verification is still required because this phase was code-only: run migrations, run tests, build assets, and check the instructor quiz builder in a browser.

### Phase 41B: Final Course Test

Code changes added for owner verification:

- Final Test is represented as a special quiz type with `quiz_type = final_test` and a direct `course_id`.
- Existing lesson quizzes remain `quiz_type = lesson_quiz` and continue using `lesson_id`.
- The instructor course builder now includes a Final Test section with Add Final Test, Publish Final Test, and Manage Questions flows.
- Final Test questions reuse the Phase 41A question/option builder and support single-choice, multiple-choice, and true/false.
- Student learning workspace shows the published Final Test near course completion with passing score, time limit, attempt limit, and latest attempt status.
- Final Test uses the existing guided quiz instruction, one-question-at-a-time, timer, save-answer, and result pages.
- Certificate templates were not changed in Phase 41B; Phase 41C adds Final Test mark display.
- `TESTING_CHECKLIST.md` includes Phase 41B manual QA.

Manual verification is still required because this phase was code-only: run migrations, run tests, build assets, and check the instructor and student Final Test flows in a browser.

### Phase 41C: Certificate Final Test Marks

Code changes added for owner verification:

- Certificates reuse the existing nullable `score` field; no additional certificate score migration was added.
- When a certificate is created without a manual score, the system uses the best submitted attempt percentage from the course's published Final Test.
- If a submitted attempt has raw score and total points but no stored percentage, the percentage is calculated safely.
- Existing certificate score behavior remains the fallback when no published Final Test or submitted Final Test attempt exists.
- Student certificate list/detail, printable/PDF certificate view, public certificate verification, and the admin certificate resource now label the mark as Final Test Score when available.
- Public verification only shows the Final Test Score for valid issued certificates and does not expose quiz answer review.
- `TESTING_CHECKLIST.md` includes Phase 41C manual QA.

Manual verification is still required because this phase was code-only: run migrations, run tests, build assets, and check certificate creation, student certificate pages, PDF/HTML fallback, and public verification in a browser.

### Phase 41D: Admin Settings and Back Home

Code changes added for owner verification:

- Admin account settings were polished inside the Filament page shell with Profile Information, Account Details, Change Password, and Security Notes sections.
- The settings page uses MK Scholars/Filament-friendly spacing, borders, dark-mode classes, helper text, and responsive layout without public navbar/footer chrome.
- Admin profile updates still only update the authenticated admin's own name and email.
- Role and approval status are displayed read-only and ignored by the profile update handler.
- Password updates still require the current password, confirmed new password, and Laravel's hashed password cast.
- Direct profile/password POST routes now require the admin account to be approved, matching Filament panel access.
- The Filament user menu link is labeled Back to Home and points to the public home route.
- `TESTING_CHECKLIST.md` includes Phase 41D manual QA.

Manual verification is still required because this phase was code-only: run tests, build assets, and check the admin settings page plus Back to Home link in the Filament panel.

### Phase 14A: Subscriptions and Advanced Payment Plans

Completed:

- Added `subscription_plans`, `plan_courses`, and `subscriptions` tables.
- Added `SubscriptionPlan` and `Subscription` models.
- Added relationships between users, courses, subscription plans, subscriptions, and payments.
- Added Filament resources for subscription plans and subscriptions.
- Updated payment admin screens to recognize subscription payments.
- Updated `/pricing` to show database subscription plans when available.
- Added student subscription pages:
  - `/student/subscriptions`
  - `/student/subscriptions/{subscription}`
- Choosing a plan creates:
  - pending subscription
  - pending manual payment
- Existing payment proof upload flow is reused.
- Admin payment approval activates the subscription.
- Active subscriptions grant access to included paid courses.
- Demo seeder now creates one demo subscription plan.

Important constraints preserved:

- No external payment APIs were added.
- Existing course payment approval logic was not replaced.
- Public design was not redesigned.
- The payment system remains manual.

## Suggested Remaining Phases

The following phases are suggested next steps. They should be implemented gradually and safely.

## Phase 15A: Subscription Polish and Renewal Flow

Goal:
Improve the subscription experience without adding real recurring billing.

Recommended work:

- Add clearer subscription status badges.
- Show subscription expiry warnings on student dashboard.
- Add manual renewal flow:
  - student can renew an expired or active subscription
  - renewal creates a new pending payment
  - admin approval extends `ends_at`
- Add subscription filters in student subscription page:
  - active
  - pending
  - expired
  - rejected
- Add admin helper actions:
  - mark expired
  - cancel subscription
  - extend subscription
- Add notifications for:
  - subscription approved
  - subscription rejected
  - subscription expiring soon

Do not add auto-billing yet.

## Phase 15B: Payment and Subscription QA

Goal:
Stabilize payments and subscriptions before any real payment integration.

Recommended work:

- Audit all payment status transitions.
- Confirm rejected subscription payments do not leave active access.
- Confirm cancelled subscriptions block paid course access.
- Confirm expired subscriptions block paid course access.
- Confirm course-specific approved payments still work.
- Confirm free courses still enroll directly.
- Add admin report rows for subscriptions if not already covered:
  - active subscriptions
  - pending subscription payments
  - subscription revenue
  - expiring subscriptions
- Update testing checklist with subscription edge cases.

Do not change payment provider logic.

## Phase 16A: Course Reviews and Student Feedback

Goal:
Allow students to leave simple feedback after taking a course.

Recommended database:

- `course_reviews`
  - user_id
  - course_id
  - rating
  - comment
  - status: pending/published/hidden
  - timestamps

Recommended work:

- Students can review enrolled courses.
- Prevent duplicate reviews per student/course.
- Admin can moderate reviews in Filament.
- Public course detail can show published reviews.
- Student dashboard can show prompt to review completed courses.

Keep it simple. Do not build complex recommendation logic yet.

## Phase 17A: Instructor Course Management Preview

Goal:
Give instructors limited visibility into courses and students without replacing Filament admin.

Recommended work:

- Add instructor course list page.
- Show courses where instructor has live classes or assigned teaching role if such relation exists.
- Show course students/enrollments read-only.
- Show assignment submission summaries read-only.
- Show quiz attempt summaries read-only.

Do not allow instructors to edit admin-owned course structure yet unless the permission model is carefully designed.

## Phase 18A: Email Notification Foundation

Goal:
Prepare real email notifications using Laravel mail, but keep in-app notifications as the source of truth.

Recommended work:

- Add mail configuration documentation.
- Add simple mail templates for:
  - payment approved/rejected
  - assignment graded
  - certificate issued
  - application status changed
  - subscription approved/rejected
- Add settings flag to avoid sending emails in local/demo.
- Keep in-app notifications active.

Do not add SMS, WhatsApp, or push notifications yet.

## Phase 19A: Real Payment API Preparation

Goal:
Prepare clean architecture for future MTN MoMo, Airtel Money, Stripe, PayPal, or card APIs.

Recommended work:

- Add payment provider abstraction/service.
- Keep manual payments as one provider.
- Add provider fields only if needed:
  - provider
  - provider_reference
  - provider_status
  - callback_payload
- Add webhook route skeletons only when provider integration begins.
- Document expected payment lifecycle.

Do not integrate real APIs until credentials and provider requirements are available.

## Phase 20A: Production Hardening

Goal:
Prepare the app for a serious staging or production launch.

Recommended work:

- Run full route, migration, and view checks.
- Confirm `APP_DEBUG=false` behavior.
- Confirm storage permissions and file uploads.
- Confirm Filament admin access is restricted to admins.
- Confirm demo users are not present in production.
- Confirm database backups plan.
- Confirm `storage:link`.
- Confirm built assets exist.
- Confirm all public pages are mobile friendly.
- Add deployment smoke-test script or checklist.

Do not run destructive migrations on real data.

## Phase 21A: PDF Certificates

Goal:
Generate downloadable certificate PDFs after certificate issuing is stable.

Recommended work:

- Add PDF generation package only after confirming dependency choice.
- Create certificate PDF template.
- Include:
  - student name
  - course title
  - certificate number
  - verification code/link
  - issued date
  - skills
- Keep browser print as fallback.

Do not break public certificate verification.

## Phase 22A: Advanced Reporting Exports

Goal:
Allow admins to export reports for operations and presentations.

Recommended work:

- Add CSV exports for:
  - students
  - enrollments
  - payments
  - subscriptions
  - certificates
  - applications
  - quiz attempts
  - assignment submissions
- Keep exports read-only.
- Avoid heavy chart libraries unless needed.

## Phase 23A: AI Features Later

Goal:
Add AI only after the core platform is stable.

Possible future AI features:

- AI study assistant
- AI quiz explanation helper
- AI scholarship/application draft feedback
- AI mentor summary assistant
- AI course recommendation helper

Do not add AI until:

- core learning flow is stable
- payment/subscription access is stable
- privacy and data handling requirements are clear
- OpenAI/API credentials and cost limits are defined

## Phase 24A: PWA and Mobile App Preparation

Goal:
Improve mobile access after web platform is stable.

Recommended work:

- Add PWA manifest.
- Add app icons.
- Add offline-friendly public shell if needed.
- Add mobile dashboard polish.

Do not add PWA before production basics and payment stability are complete.

## Notes For Future GPT/Codex Sessions

When continuing this project:

1. Use Laravel, Blade, Livewire, Tailwind CSS, Filament, and migrations.
2. Do not use React.
3. Do not redesign public pages unless the user explicitly asks.
4. Preserve MK Scholars navy/gold/white styling.
5. Keep changes SQLite-compatible and MySQL-ready.
6. Do not run destructive migrations unless the user clearly asks.
7. Check `routes/web.php` carefully before editing because it contains many route closures.
8. Check existing model constants before adding statuses.
9. Reuse existing manual payment flow where possible.
10. Keep each phase small and stable.

## Recommended Verification Commands

Run these after future phases:

```bash
php artisan migrate
php artisan route:list
php artisan view:clear
php artisan optimize:clear
npm run build
```

For demo testing:

```bash
php artisan db:seed
php -S 127.0.0.1:9000 -t public server.php
```

## Demo Credentials

If `DemoSeeder` has been run locally:

```text
Admin:      admin@mkscholars.test / password
Student:    student@mkscholars.test / password
Instructor: instructor@mkscholars.test / password
Mentor:     mentor@mkscholars.test / password
```

These are for local/demo testing only.
