# System Overview - MK Scholars E-learning Platform

## Purpose

MK Scholars is a web-based learning platform for students, instructors, mentors, and administrators. It supports course discovery, enrollment, lesson learning, quizzes, assignments, subscriptions, payments, certificates, reports, and notifications.

## Architecture

- Laravel provides the application backend, routing, authentication, models, migrations, and services.
- Blade and Livewire provide the server-rendered frontend.
- Tailwind CSS and Vite provide styling and asset compilation.
- Filament provides the admin interface.
- PHPUnit feature tests cover major product flows.

Filament is the Laravel admin panel used by MK Scholars. It provides the `/admin` dashboard where authorized admins manage users, courses, academies, payments, subscriptions, certificates, reports, and other operational records through secure tables, forms, filters, and actions. Viewer accounts are read-only admin observers: they can inspect allowed admin records but cannot create, edit, delete, approve, reject, upload, or change operational data.

## Main User Roles

- Student: learns courses, submits work, takes quizzes, tracks progress, manages documents/payments/subscriptions, and downloads certificates.
- Instructor: manages owned courses, modules, lessons, quizzes, assignments, students, submissions, quiz attempts, and live classes.
- Mentor: views assigned students and check-ins when mentorship is enabled.
- Admin: manages platform data through Filament and exports reports.

## Core Domain Models

- `User`
- `Academy`
- `Course`
- `Module`
- `Lesson`
- `LessonActivity`
- `Enrollment`
- `LessonProgress`
- `Quiz`
- `QuizQuestion`
- `QuizOption`
- `QuizAttempt`
- `QuizAnswer`
- `Assignment`
- `AssignmentQuestion`
- `AssignmentSubmission`
- `AssignmentQuestionAnswer`
- `LiveClass`
- `LiveClassAttendance`
- `MentorAssignment`
- `MentorCheckIn`
- `PaymentMethod`
- `Payment`
- `SubscriptionPlan`
- `Subscription`
- `Certificate`
- `CertificateSkill`
- `CourseReview`
- `AppNotification`
- `StudentDocument`

## Main Screens

Public:

- Home
- Academies
- Courses
- Course details
- Pricing
- About
- Contact
- Certificate verification
- Login
- Register

Student:

- Dashboard
- My courses
- Learning page
- Quiz page
- Assignment page
- Payments
- Subscriptions
- Documents
- Live classes
- Mentorship
- Certificates
- Notifications
- Settings

Instructor:

- Dashboard
- Courses
- Course create/edit
- Course detail
- Students
- Submissions
- Quiz attempts
- Live classes
- Notifications
- Settings

Admin:

- Filament admin panel at `/admin`
- Users
- Academies
- Courses
- Modules
- Lessons
- Quizzes
- Assignments
- Payments
- Subscriptions
- Certificates
- Reports
- Notifications
- Live classes
- Mentorship resources

## Authentication And Authorization

The application uses Laravel session authentication. Passwords are hashed. Role checks are centralized through the `role` middleware alias. Admin panel access is restricted in the `User` model's Filament access method.

Approval status is part of the user model. Instructors and mentors can require admin approval before protected-area access.

## Payments

Manual payment is the active flow. Payment-provider metadata exists for future integrations, but provider webhooks currently return a disabled `501` response and do not approve payments.

## Uploads

The application stores student documents, payment proofs, and assignment submissions on the public disk with validation for file type and size. Production deployments should review whether local public storage is enough or whether cloud storage is required.

## Testing

Tests live in `tests/Feature`. They cover public pages, role protection, user approval, dashboards, instructor ownership, quizzes, assignments, certificates, payments, reports, notifications, settings, and content rendering.

## Known Operational Requirement

The app needs PHP 8.2+, Composer dependencies, a valid `.env`, and a database before it can run.
