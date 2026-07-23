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

### Phase 41F: Instructor Assignment Builder and Final Test Completion Gate

Implemented code-only changes for the instructor assignment builder and student completion flow.

- Added assignment objective-question support through `AssignmentOption` and stored selected option IDs on assignment question answers.
- Expanded instructor course builder assignment form with document upload, due days, richer question type selection, option inputs, and correct-answer controls.
- Updated student assignment display/submission to support short answer, long answer, single-choice, multiple-choice, and true/false questions.
- Updated lesson completion labels so video lessons use video-specific wording and reading lessons use reading-specific wording.
- Added published Final Test status to the student completion checklist and course-completion eligibility calculation.
- Added focused feature coverage for objective assignment answers and invalid option ownership.

Migration added:

- `2026_07_08_410500_add_assignment_options_and_selected_answers.php`

Manual verification is still required because this phase was code-only: run migrations, run tests, build assets, and check instructor assignment creation plus student assignment/final-test completion flows in a browser.

### Phase 41F Completion Audit/Fix

Verified and tightened the Phase 41F completion gaps with code-only changes.

- Video/reading manual completion was already present through the student lesson completion route and remains protected by course access plus student role middleware.
- Instructor lesson creation now uses automatic unique slug generation from the title when slug is blank; unique manual slugs are preserved.
- Quiz/final-test and assignment tables do not currently have slug columns, so no slug behavior was added for those records.
- Instructor course builder now has a Course completion summary card showing Videos, Reading, Quizzes, Assignments, and Final Test title/status.
- Course completion already counts all published lessons, which includes video and reading lessons, and now retains the published Final Test pass gate from Phase 41F.
- Assignment document upload uses the public storage disk, allowed document/image/archive mimes, and student document links remain behind enrolled course access.
- Added focused tests for unique instructor lesson slugs, instructor completion summary Final Test display, final-test completion gating, legacy courses without final tests, and non-duplicated completion records.

No new migration was added in this audit/fix pass.

Manual verification is still required because this phase was code-only: run migrations, run tests, build assets, and check instructor course builder plus student learning/assignment flows in a browser.

## Phase 41G Hotfix QA

- Admin account settings page renders with polished Filament/MK Scholars design.
- Profile Information, Account Details, Change Password, and Security Notes sections display clearly.
- Admin can update own profile/password safely.
- Role/status remain read-only.
- Subscription form does not submit to insecure HTTP URL.
- Production APP_URL should be `https://e-learning.mkscholars.com`.
- Production SESSION_SECURE_COOKIE should be true.
- Admin reports use the correct navbar/main MK Scholars logo.
- Final Test button says `Start Test`.
- Normal quiz button still says `Start Quiz`.

### Phase 42A: Certificate Stamp, Signatures, and QR Verification

Implemented code-only certificate branding and verification improvements. Admins manage a singleton official certificate settings record in Filament, including the organization stamp, issuer signature/name/title, optional logo, and footer note. Instructor signatures are stored as nullable public-disk paths on instructor user records and are admin-managed through the existing Users resource. Student and PDF certificate layouts now include instructor/issuer signature areas, the official stamp, a locally generated QR code, and the existing public verification URL based only on the verification code. Public verification remains intentionally limited to safe certificate details and does not show official assets for invalid or revoked certificates.

Migrations added:

- `2026_07_10_420000_create_certificate_settings_table.php`
- `2026_07_10_420100_add_signature_path_to_users_table.php`

The already locked `chillerlan/php-qrcode` package is reused; no Composer dependency change was required. Manual migration, tests, asset build, and browser/PDF verification remain required.

### Phase 42B: Certificate Approval Workflow

Implemented a controlled certificate lifecycle using `pending`, `issued`, `rejected`, and `revoked`. Eligible course-completion calculation now prepares at most one pending certificate per student/course while preserving any existing certificate, verification code, and Final Test Score. Admin-only Filament actions approve/issue, reject with an optional reason, or revoke a certificate; viewer access remains read-only and content editors/instructors cannot perform workflow actions. Official stamp, issuer signature, instructor signature, QR verification, printing, and PDF download are presented only for issued certificates. Public verification validates only `issued` records and gives safe non-valid states for pending, rejected, revoked, or unknown codes.

Migration added:

- `2026_07_10_421000_add_certificate_approval_fields.php`

The existing notification system informs students when a request is rejected or issued and informs the course instructor when a certificate is issued. A dedicated instructor certificate-request screen was intentionally deferred because the current instructor dashboard has no existing certificate-management surface; instructors cannot approve, reject, or revoke records. No Composer dependency changed, so `composer update` is not required. Manual migration, tests, build, route inspection, and browser verification remain required.

### Phase 42C: Student My Courses Paid and Unpaid Course Sections

Updated Student My Courses to split Paid/Active Courses from Unpaid Courses / Courses Awaiting Payment. Active courses are detected through the existing course access logic, including active enrollments with approved/free access and active subscription plan access. Unpaid cards are sourced from the current student’s pending/submitted/rejected course payments, paid enrollments without access, and pending/rejected/expired subscription plan courses. Pay actions reuse the existing course enrollment, payment proof, and subscription renewal routes so pending payments are reused instead of duplicated and admin approval flow remains unchanged.

No migration was added. Manual testing, asset build, and browser verification remain required.

### Phase 42D: Completed Lesson and Course Card Polish

Polished student completion UI without changing completion rules. My Courses active cards now use Completed as the primary state for completed courses, keep certificate status visible, and expose View Certificate for issued certificates. The student learning page now labels video, reading, and generic lesson completion states distinctly, shows quiz/final-test/assignment actions based on attempts or submissions, and includes a compact completion summary for videos, readings, quizzes, assignments, final test, overall progress, and course status. Lesson completion continues to use the existing access checks and update-or-create progress behavior.

No migration was added. Manual tests, asset build, route inspection, and browser verification remain required.

### Phase 42E: Instructor Live Class Creation

Added instructor-side live class creation and editing using the existing `live_classes` table and `LiveClass` model fields: course, instructor, title, description, meeting URL, platform, start/end time, status, and recording URL. Instructors can schedule, edit, and cancel their own live classes from the instructor workspace, with course selection restricted to their owned or already assigned courses. Student live class display remains protected by enrollment/access routes, and the course player now shows simple live class details while leaving smart timing behavior for a later phase. Admin Filament live class management remains unchanged.

No migration was added. Manual tests, asset build, route inspection, and browser verification remain required.

### Phase 42F: Live Class Smart Join and Recording Buttons

Added time-based live class state helpers on the existing `LiveClass` model and used them for student and instructor live class actions. Upcoming classes no longer expose active join buttons, live-now classes expose Join Class only, ended classes expose Watch Recording only when a recording URL exists, and cancelled classes show no active student action. Student join and recording redirects now re-check authentication, course access, class timing, and URL availability before redirecting away. Instructor live class lists show clear smart status labels and keep edit/add-recording paths available through the existing form.

No migration was added. Manual tests, asset build, route inspection, and browser verification remain required.

### Phase 42G: Payment Proof Reference Cleanup

Removed the student-facing reference number requirement from manual payment proof upload. Students now submit payment method and proof file only, while the existing nullable `payments.reference` column is kept for old data. New proof submissions no longer overwrite legacy reference values, and student payment summaries only show a clearly labeled Legacy Reference when an older value already exists. Admin payment management keeps reference as optional legacy information and continues to review proof files, amount, method, status, and approval/rejection without depending on a reference number. My Courses Pay Now, pending payment reuse, rejected retry, and subscription/course payment proof flows continue to use the existing payment routes.

No migration was added. Manual tests, asset build, route inspection, and browser verification remain required.

### Phase 43A: Instructor Course Form Polish and Live Class Timing Hotfix

Polished the instructor course form with required-field red stars, optional level/duration handling, automatic unique slug generation from the title, a stronger Markdown overview editing surface, and a course-level certificate toggle. Public course cards, course details, student course progress, certificate preparation, and admin certificate creation now respect whether a course offers certificates. Existing courses are marked certificate-enabled during migration to preserve current behavior, while newly created courses default to no certificate unless enabled.

Live class timing now treats the start/end window as the source of truth for Join Class and Watch Recording behavior, including the exact start and end times. Scheduled classes no longer stay visually stuck as Upcoming once the current time is inside the meeting window, and cancelled or missing-link states return clearer messages.

Migration added:

- `2026_07_14_430000_add_offers_certificate_and_optional_course_fields.php`

Manual migration, tests, asset build, route inspection, instructor course-form browser checks, student course-card checks, and live-class timing checks remain required.

### Phase 43B: Question Answer Type Fixes

Centralized quiz and assignment question-type behavior with helpers for option-based, text-based, multiple-select, single-select, and true/false questions. Quiz answers now have a nullable `answer_text` field so short/long text answers can be saved without fake options. Option-based quiz scoring remains unchanged, multiple-choice still uses exact selected-set matching, and unscored text quiz questions are saved for review without counting against the auto-graded total.

Instructor quiz and assignment builders now hide option boxes for text answers, force True/False to the standard True and False choices, and keep options visible for single-choice and multiple-choice. Student quiz and assignment pages render radio buttons, checkboxes, text inputs, and textareas based on the saved question type. Admin question resources were updated to expose the supported types and keep quiz options attached to option-based questions.

Migration added:

- `2026_07_14_431000_add_answer_text_to_quiz_answers_table.php`

Manual migration, tests, asset build, route inspection, instructor builder browser checks, student quiz checks, and student assignment submission checks remain required.

### Phase 43C: PDF Notes Upload, PDF Viewer, and Rich Content Rendering

Added file-backed lesson materials using the existing `lesson_activities` model instead of introducing a separate materials table. Instructors can upload PDF, image, Word, and PowerPoint materials from the course builder; admins can also attach uploaded resources through the Lesson Activities resource. Student learning pages embed uploaded PDFs through a protected route that verifies authentication, student access, published course/activity status, and file existence before returning the PDF inline.

Rich content styling was tightened for course overviews and lesson notes: tables stay wrapped in a horizontally scrollable bordered container, code blocks use readable monospace styling with horizontal overflow protection, and images stay responsive inside their containers.

Migration added:

- `2026_07_14_432000_add_uploaded_resource_fields_to_lesson_activities_table.php`

Manual migration, tests, asset build, route inspection, instructor upload browser checks, student PDF viewer checks, and mobile rich-content checks remain required.

### Phase 43D: Login One-Click Fix

Fixed the login form so it no longer depends on a blur-triggered Livewire state sync before submission. The form now renders as one standard POST form with a route-helper action, CSRF token, named email/password/remember fields, and a submit button, while Livewire continues to enhance the same form when available.

The shared login logic now lives in `LoginAuthenticator`, and both the Livewire login action and native `POST /login` route use it to preserve password validation, session regeneration, approval checks, and existing role dashboard redirects. Filament admin login remains configured separately through the existing admin panel provider.

No migration was added. Manual tests, asset build, route inspection, student/instructor login browser checks, and Filament admin login checks remain required.

### Phase 43E: Instructor Signature Settings

Added instructor-managed certificate signature upload to the existing shared account settings page. Instructors now see a Certificate Signature section with current preview, PNG/JPG/JPEG/WebP upload up to 2MB, replacement, and removal controls. Uploaded files are stored on the public disk under `certificates/instructor-signatures` and saved to the existing `users.signature_path` field.

The upload and removal routes are instructor-only and update only the authenticated instructor record. Old signature files are deleted when replacing or removing, limited to the instructor-signatures storage folder. Admin Filament user signature management remains unchanged, and certificate display/PDF paths continue to use the existing instructor `signature_path` behavior.

No migration was added. Manual tests, asset build, route inspection, instructor settings browser checks, admin user-resource checks, and issued certificate display/PDF checks remain required.

### Phase 43F: Entrance Exam Academy Foundation

Added the foundation for an Entrance Exam Academy with institutions, programs/faculties, subjects, and past papers. Admins manage the area through new Filament resources, including institution logos and PDF-only past paper uploads up to 20MB. Past papers can be classified by institution, program, subject, year, exam type, featured status, and draft/published/archived status.

Public users can browse published papers at the Entrance Exam Academy, filter by classification, and view paper metadata. The actual PDF viewer and inline PDF response require authentication, serve only published PDF records, return inline PDF headers, and avoid exposing raw storage paths or direct download buttons. The viewer includes a watermark overlay and documents the limitation that read-only viewing cannot prevent screenshots, screen recording, browser inspection, or external capture.

Migration added:

- `2026_07_14_433000_create_entrance_exam_academy_tables.php`

Online timed entrance exam practice, scoring, and attempts are intentionally deferred. Manual migration, tests, asset build, route inspection, admin upload checks, public filter checks, authenticated PDF viewer checks, and mobile checks remain required.

### Phase 43G: Entrance Exam Payment Gate, PDF Warning Cleanup, Toolbar, and Live Timing Hotfix

Added per-paper manual payment access for Entrance Exam Academy past papers. Published paper detail pages now show Register to Continue for guests, Pay Now, Payment Pending, Pay Again, or Read Paper based on the current student's payment state, and the protected viewer/inline PDF routes require an approved entrance exam paper payment before serving the file. Student payment screens, admin payment management, and payment notifications now understand entrance exam paper payments through the existing manual payment flow.

Removed the user-facing read-only/download limitation warnings from entrance exam and lesson PDF viewer screens while keeping protected inline routes and hidden raw storage paths. The instructor Full Course Overview toolbar now uses clearer compact controls with labels/tooltips for formatting, links, tables, images, undo, and redo. Live class displays now use time-derived status consistently, keep currently live classes visible on the instructor dashboard by querying through `ends_at`, and parse instructor-submitted class times with the app timezone.

Migration added:

- `2026_07_14_434000_add_entrance_exam_paper_to_payments_table.php`

Manual migration, tests, asset build, route inspection, entrance exam payment checks, student/admin payment checks, instructor toolbar browser checks, and live-class timing checks remain required.

### Phase 43H: Instructor Editor, Simple Live Links, and Entrance Exam Paper Pricing

Refined the instructor Full Course Overview editor to use a darker admin-style Markdown editing shell with icon-only toolbar controls for formatting, links, lists, tables, images, undo, and redo while preserving the existing Markdown storage/rendering path. Live class action behavior was simplified so authorized users can open Join Class whenever a meeting URL exists and Watch Recording whenever a recording URL exists, unless the class is cancelled; timing remains informational for status labels but no longer blocks links.

Entrance Exam Academy past papers now have admin-managed `price_amount` and `currency` fields. Public paper cards/details show Free or the configured price. Free published papers are readable by authenticated students without payment, while paid papers still require an approved per-paper entrance exam payment. Pay Now now creates or reuses manual payments using the paper's admin-set price instead of a config fallback.

Migration added:

- `2026_07_15_430000_add_price_fields_to_entrance_exam_past_papers_table.php`

Manual migration, tests, asset build, route inspection, instructor editor browser checks, live class link checks, and free/paid entrance exam paper payment checks remain required.

### Phase 43I: Student UI, Entrance Exam Controls, Social Share Images, and Admin Required Stars

Updated entrance exam student-facing copy so guests see Register to Continue instead of Login to Read, and added protected viewer controls for dark mode plus zoom in, zoom out, and reset zoom. The student dashboard navigation now includes Entrance Exam, and the mobile dashboard menu is constrained to the viewport with an internal scroll area so Settings, Back to Site, and Logout remain reachable on phones.

Public layout metadata now includes Open Graph and Twitter card tags. Course detail pages pass the course cover image and short description into the layout so shared course links can show the correct image on WhatsApp and social platforms, with the MK Scholars logo as the fallback image.

Admin Entrance Exam Institution, Program, Subject, and Past Paper title/name fields no longer use Filament required markers. Their database columns are made nullable and model slug generation now tolerates blank values by falling back to safe generic slugs.

Migration added:

- `2026_07_16_430000_make_entrance_exam_admin_names_nullable.php`

Manual migration, tests, asset build, route inspection, phone-menu checks, entrance exam viewer control checks, social-share metadata checks, and admin entrance exam form checks remain required.

### Phase 43K: Entrance Exam Mobile Viewer, Rich Instructions, and File Types

Reworked Entrance Exam Academy past paper viewing so the student page no longer relies on a native browser PDF iframe. PDFs now render through a local PDF.js-powered page renderer with dark mode, zoom controls, mobile-width page fitting, and vertical scrolling through all pages. The old past-paper watermark overlay was removed, and the viewer continues to avoid download buttons and raw storage paths.

Past papers now support rich Markdown instructions/content rendered with the existing sanitized course content renderer before the paper on detail and viewer pages. Admins can upload PDF, PNG, JPG, JPEG, WEBP, DOC, DOCX, PPT, and PPTX files. Images render inline through the protected route. Office files remain protected and show a no-preview message.

Migration added:

- `2026_07_23_430100_add_viewer_content_fields_to_entrance_exam_past_papers_table.php`

Package dependency added:

- `pdfjs-dist` for the local in-page PDF renderer.

Manual migration, npm install, asset build, tests, route inspection, mobile PDF viewer checks, image viewer checks, Office no-preview checks, and paid/free entrance exam access checks remain required.

### Phase 43K.1: Entrance Exam Viewer Hotfix

Tightened the Entrance Exam Academy viewer/detail rendering so both paper descriptions and paper instructions use the existing sanitized course content renderer. This prevents Markdown headings, lists, tables, code blocks, images, and links from appearing as raw text on student-facing pages while still stripping unsafe HTML.

The protected viewer behavior remains focused on the uploaded paper file: PDF papers render from the main uploaded file without requiring a separate preview, images render inline through the same protected route, and Office files show the no-preview message without exposing direct storage paths or download links.

No migration was added. Manual tests, asset build, route inspection, mobile viewer checks, image viewer checks, Office no-preview checks, and paid/free entrance exam access checks remain required.

### Phase 43K.2: Entrance Exam Single File Upload Only

Removed the separate Entrance Exam Past Paper preview upload from the Filament admin form. Admins now use one `Past Paper File` field for PDF, image, Word, and PowerPoint uploads, with helper text explaining that PDF and image files can be previewed in the reader.

The main uploaded paper file is now the sole viewer source. PDFs render directly through the protected inline route and local PDF.js reader, images render inline through the same protected route, and Word/PowerPoint files show `Preview is not available for this file type yet.` without exposing storage paths or direct download links. Legacy preview database columns may remain if already present, but they are not used by the admin form or viewer.

No migration was added. Manual tests, asset build, route inspection, single-upload admin form checks, PDF/image viewer checks, Office no-preview checks, and paid/free entrance exam access checks remain required.

### Phase 43K.3: Entrance Exam PDF Detection and Viewer Rendering

Hardened Entrance Exam Past Paper file detection around the main uploaded file columns: `paper_file_path`, `paper_file_disk`, and `paper_file_mime`. The model now normalizes missing or `application/octet-stream` MIME values from the paper file extension, including uppercase `.PDF`, so PDF/image viewer decisions no longer depend on perfect upload MIME metadata.

The protected inline route continues to serve only the main uploaded paper file for PDFs and images with inline headers. Word and PowerPoint files remain protected and show the preview-unavailable message without exposing raw storage paths or direct download links. No preview upload field was reintroduced.

No migration was added. Manual tests, asset build, route inspection, PDF detection checks for missing/octet-stream/uppercase MIME cases, image viewer checks, Office no-preview checks, and paid/free entrance exam access checks remain required.

### Phase 43K.4: Entrance Exam PDF Viewer Main File URL

Made the Entrance Exam viewer pass the protected main paper route to PDF.js explicitly through `data-pdf-url` whenever the uploaded paper is detected as a PDF. The model now exposes `mainPaperPath()`, `mainPaperDisk()`, `mainPaperExtension()`, and `mainPaperMime()` as the single source of truth for uploaded paper detection, while existing paper-file helper names delegate to those methods.

The protected inline route now reads the same main paper helpers, so PDFs and images are served from the uploaded paper file only. Word and PowerPoint files continue to show the preview-unavailable message and are not exposed as direct downloads. No preview upload field was reintroduced, and no migration was added.

Manual tests, asset build, route inspection, PDF Network-tab checks for the protected route request, image viewer checks, Office no-preview checks, and paid/free entrance exam access checks remain required.

### Phase 43K.5: Entrance Exam PDF Viewer Route and Worker MIME

Added hosting MIME configuration for PDF.js worker assets so Hostinger/LiteSpeed can serve `.mjs` files as `application/javascript` instead of `text/plain`. This supports the existing local PDF.js worker emitted by Vite without using an external CDN.

The Entrance Exam viewer continues to pass the protected main paper route to PDF.js through `data-pdf-url`, and the protected inline route continues to serve PDFs and images from the main uploaded paper file with inline headers while preserving paid/free access checks. Word and PowerPoint files remain protected and show the preview-unavailable message without direct downloads.

No migration was added. Manual tests, asset build, route inspection, PDF Network-tab checks for both the protected paper file request and JavaScript worker MIME, image viewer checks, Office no-preview checks, and paid/free entrance exam access checks remain required.
