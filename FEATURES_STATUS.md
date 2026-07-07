# Features Status

| Feature | Status | Notes |
| --- | --- | --- |
| Authentication | Implemented, not runtime-verified | Login, register, logout, session regeneration, hashed passwords, first-admin setup, and approval checks exist. Password reset was not found. |
| Role-Based Access | Implemented, not runtime-verified | Middleware supports student, instructor, mentor, and admin roles. Filament access is admin-only. |
| Student Dashboard | Implemented, not runtime-verified | Student dashboard and related pages exist under `resources/views/student`. |
| Teacher/Instructor Dashboard | Implemented, not runtime-verified | Code uses `instructor` rather than `teacher`. Dashboard, course management, content builder, students, submissions, and quiz attempts exist. |
| Admin Dashboard | Partially implemented | Filament admin panel and resources exist. Separate Blade admin dashboard is a placeholder. |
| User Management | Implemented through Filament | `UserResource` exists with roles and approval moderation. |
| Courses | Implemented, not runtime-verified | Course model, migrations, public pages, admin resource, instructor create/edit, enrollment, payments, and reviews exist. |
| Modules | Implemented, not runtime-verified | Modules connect courses to lessons. Admin and instructor management exist. |
| Lessons | Implemented, not runtime-verified | Lesson model, migrations, public outline, learning page, completion, content, video support, and admin/instructor management exist. |
| Enrollment | Implemented, not runtime-verified | Enrollments are modeled and used in student access checks. |
| Progress Tracking | Implemented, not runtime-verified | `LessonProgress`, course progress calculations, completion rules, and course completions exist. |
| Quizzes | Implemented, not runtime-verified | Quiz models, questions, options, attempts, answers, student quiz flow, instructor builder, admin resources, and tests exist. |
| Assignments | Implemented, not runtime-verified | Assignment models, questions, submissions, student flow, instructor/admin review, file upload validation, and tests exist. |
| File Uploads | Implemented, needs production decision | Student documents, payment proofs, assignment submissions, academy images, certificates, and assignment instruction files are represented. Production storage should be confirmed. |
| Payments | Manual implemented, providers pending | Manual payments work by design. Provider architecture exists, but webhooks are disabled placeholders. |
| Subscriptions | Implemented, not runtime-verified | Subscription plans, plan courses, subscriptions, payment flow, and tests exist. |
| Certificates | Implemented, not runtime-verified | Certificate model, verification, download, signatures, admin/student routes, and tests exist. |
| Notifications | Implemented, not runtime-verified | In-app notifications and optional email notification service exist. |
| Reports | Implemented, not runtime-verified | Filament report pages and CSV export service/routes exist. |
| Mentorship | Present, partially gated | Mentor models, routes, views, and resources exist. Feature gating can disable mentor routes/navigation. |
| Live Classes | Implemented, not runtime-verified | Live class and attendance models, student/instructor pages, and admin resources exist. |
| Public Pages | Implemented, not runtime-verified | Home, academies, courses, course details, pricing, about, contact, auth pages, and certificate verification exist. |
| Tests | Present, not run | Broad PHPUnit feature tests exist but could not be run without PHP/Composer/vendor. |
