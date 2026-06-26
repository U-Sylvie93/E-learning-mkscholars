# MK Scholars Testing Checklist

Use this checklist after running migrations, seeding demo data, building assets, and starting the local server.

## Production Smoke Tests

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY`, and `APP_URL` are set in staging/production.
- `npm run build` creates `public/build/manifest.json` and public pages load built CSS/JS.
- `php artisan storage:link` has been run and uploaded files can be opened/downloaded through intended authenticated screens.
- Missing uploaded files show a safe missing-file state or 404 instead of crashing pages.
- Student document and application upload forms display allowed file types and 10MB size guidance.
- `php artisan route:list`, `php artisan view:clear`, and `php artisan optimize:clear` complete successfully.
- `php artisan route:cache` is not used while route closures remain in `routes/web.php`.
- Logs are writable in `storage/logs`.
- Demo seeders are not run in production and demo credentials are not used for production admin access.
- `/setup-admin` is used only when no production admin exists.
- Public webhook placeholder returns disabled/not implemented and does not alter payment records.
- `MK_EMAIL_NOTIFICATIONS_ENABLED=false` remains set until mail settings are verified with a safe mail driver.

## Public Pages

- Home page loads with navbar, hero, academy/course previews, FAQ, and footer without a public opportunities section.
- Academies page shows published academies and course counts.
- Courses page shows published courses with images/fallback images, price labels, and CTAs.
- Course details page shows hero, syllabus, outcomes, lessons, pricing, and correct CTA state.
- Opportunities page shows published opportunities with deadline badges.
- Opportunity details page shows requirements, benefits, and Apply/Login CTA.
- Pricing, About, and Contact pages load with MK Scholars styling.
- Certificate verification route shows valid and invalid certificate states.
- Public homepage no longer shows opportunity cards, opportunity section, or Find Scholarships CTA.
- Public opportunities page and opportunity details page still load through their normal routes.
- Public footer appears on home, academies, courses, course details, pricing, about, contact, login, and register pages.
- Public footer uses MK Scholars navy branding with readable white/light text and gold accents.
- Footer stacks cleanly on phone-width screens without horizontal overflow.
- Footer links and logo do not show broken links or broken images.

## Admin Tests

- Admin can log in and access `/admin`.
- Non-admin users cannot access `/admin`.
- Filament resources load for academies, courses, modules, lessons, activities, quizzes, assignments, payments, certificates, opportunities, notifications, and reports.
- Admin can review manual payments and approve/reject with notes.
- Admin reports are read-only and load without changing data.
- Admin Reports Overview shows CSV export buttons.
- Admin can download students, enrollments, payments, subscriptions, certificates, applications, quiz attempts, assignment submissions, and course reviews CSV files.
- Guest, student, instructor, and mentor users cannot download admin CSV exports.
- Payment CSV exports do not include proof file paths, provider payloads, or private upload URLs.
- Application CSV exports do not include private document paths or external document links.
- Assignment submission CSV exports do not include uploaded file paths.
- Certificate CSV exports include public verification URLs.
- Empty CSV exports download with headers and no crash.
- CSV exports preserve commas, quotes, and line breaks when opened in Excel/LibreOffice.
- CSV filters using `from`/`to`, `date_from`/`date_to`, `status`, and supported `course_id` values fail safely.
- CSV export requests do not create, update, or delete platform records.
- CSV exports open in a spreadsheet app with readable headers and rows.
- Admin Users tab loads in Filament and shows name, email, role, approval status, and created date.
- Admin can search users and filter by role or approval status.
- Admin can approve a pending instructor account.
- Admin can reject a pending instructor account.
- Admin can approve a pending mentor account.
- Pending instructor or mentor accounts cannot access their dashboards.
- Rejected or suspended instructor or mentor accounts cannot access protected dashboards.
- Student registration still creates an approved student account and reaches the student dashboard.
- Admin login still works and admin accounts stay protected from self-suspension.
- Admin Reports Overview uses a compact professional header and does not show an oversized logo.
- Student, Course, Payment, Learning, Live Class, Mentorship, Certificate, and Opportunity report pages load with the shared polished layout.
- Report filters are grouped in a readable card with labels, Apply Filters, and Reset actions.
- KPI cards show labels, values, helper text, and readable navy/gold contrast.
- Report tables show clear headers, row counts, empty states, and horizontal scrolling on narrow screens.
- CSV export actions appear as clear cards with Download CSV buttons and preserve existing export routes.
- Admin report pages remain usable on desktop, tablet, and phone-width screens.

## Student Tests

- Student can log in and reaches `/student/dashboard`.
- Student dashboard shows reminders, notifications, payments, mentorship, certificates, and opportunities summaries.
- Student dashboard shows a small feedback prompt for enrolled courses not yet reviewed.
- Free course enrollment creates active access.
- Paid course enrollment creates a pending payment and blocks learning access until approval.
- `/student/my-courses` shows enrolled courses and completion status.
- Learning workspace opens, switches lessons with `?lesson=`, marks lessons complete, and shows completion checklist.
- Enrolled student can submit one course review from the learning workspace.
- Duplicate course review submission shows a clear validation error.
- Non-enrolled student cannot submit a course review.
- Submitted review starts as pending and shows its moderation status to the student.
- Quiz page submits answers and shows score/pass/fail result.
- Assignment page accepts valid text/file/link submissions and blocks duplicate active submissions.
- Student live classes page shows enrolled course sessions and join action.
- Student mentorship page shows assigned mentor/check-ins or a clean empty state.
- Student certificates page shows issued certificates and print/verification link.
- Student documents page uploads, downloads, and deletes allowed files.
- Student notifications page marks one/all notifications as read.

## Instructor Tests

- Instructor can log in and reaches `/instructor/dashboard`.
- Instructor cannot access student, mentor, or admin protected pages.
- Instructor dashboard shows assigned courses count, enrolled student count, pending submissions, quiz summary, and upcoming live classes.
- Instructor courses page shows only courses linked through that instructor's live classes.
- Instructor course inference works through live classes linked directly to a course, linked to a module, or linked to a lesson.
- Duplicate live-class links to the same course do not duplicate the course card.
- Orphan/unlinked instructor live classes do not crash dashboard, courses, or live class pages.
- Instructor course detail page is read-only and shows course status, students, modules/lessons, assignments, quizzes, and live class summary.
- Instructor course students page shows enrolled students, enrollment status, progress, and enrolled date.
- Instructor course students page does not show students from unrelated courses.
- Instructor submissions page shows assignment submissions for assigned courses only.
- Instructor submissions page only shows file links when the stored file exists.
- Instructor quiz attempts page shows quiz attempts for assigned courses only.
- Instructor cannot access unrelated course detail, students, submissions, or quiz attempts pages by changing the URL.
- Student and mentor accounts cannot access instructor pages.
- Instructor live classes page shows assigned sessions, meeting links, and attendance.
- Instructor notifications page loads and supports mark read/all read.

## Mentor Tests

- Mentor can log in and reaches `/mentor/dashboard`.
- Mentor cannot access student, instructor, or admin protected pages.
- Mentor students page only shows assigned students.
- Mentor check-ins page allows completing assigned check-ins with feedback.
- Mentor notifications page loads and supports mark read/all read.

## Payment Tests

- Active payment method appears on student payment pages.
- Payment proof accepts only PDF, PNG, JPG, or JPEG.
- Pending/submitted payments show Payment Pending state on course details.
- Approved course payment creates or activates enrollment.
- Pending course payment does not grant learning access.
- Rejected course payment does not grant learning access.
- Rejected payment shows admin notes and allows resubmission.
- Free courses still enroll and open without payment.
- Expired subscriptions do not block free course enrollment.
- New payments default to the manual provider.
- Provider reference/status/payload fields can remain empty for manual payments.
- Filament Payments shows provider information without requiring admins to edit provider internals.
- Unknown payment providers fail safely in the provider manager.
- `POST /payments/webhooks/{provider}` returns disabled/not implemented and does not approve or activate payments.
- Manual payment approval still creates or activates course enrollment after provider metadata is present.
- Payment reports show manual provider revenue in the provider breakdown.
- Old payments with `provider = null` still display as Manual and do not crash admin, reports, or student payment pages.
- Paid course enrollment creates a pending manual provider payment.
- Manual course payment proof upload still moves the payment to submitted.
- The disabled webhook placeholder does not update payment status, create enrollment, or activate subscriptions.
- Provider report groups old null-provider and new manual payments under Manual.

## Subscription Tests

- Pricing page shows database subscription plans when seeded.
- Student can choose a subscription plan and gets a pending subscription/payment.
- Subscription plan choice creates a pending manual provider payment.
- Student can upload payment proof through the existing payment page.
- Admin approval activates the subscription and sets start/end dates.
- Active subscription detail shows included courses and learning access.
- Rejected subscription payment shows rejected/pending state for student follow-up.
- Active subscription with future expiry grants access to included paid courses.
- Pending subscription does not grant paid course access.
- Rejected subscription does not grant paid course access.
- Cancelled subscription does not grant paid course access.
- Expired subscription does not grant paid course access.
- Subscription with missing plan/course relationship does not break student pages.
- Active or expired subscription can be renewed through a new pending manual payment.
- Renewal payments keep the manual provider value.
- Renewal approval extends from the current expiry date when still active.
- Renewal approval starts from approval/current date when the subscription is expired.
- Cancelled, rejected, pending, and expired subscriptions do not grant paid course access.
- Multiple pending renewal payments are not duplicated for the same subscription.
- Rejected renewal payment does not activate or extend access.
- Re-approving the same renewal payment does not double-extend the subscription.
- Admin subscription actions can cancel, mark expired, or extend without deleting records.

## Course Review Tests

- Admin can open Course Reviews in Filament.
- Admin can filter course reviews by pending, published, or hidden.
- Admin can publish a pending review.
- Admin can hide a published or pending review.
- Guest review submissions redirect to login.
- Rating validation accepts only 1 through 5.
- Empty review comments are accepted.
- Public course detail page shows average rating and published review count.
- Public course detail page shows only published reviews.
- Average rating and review count ignore pending and hidden reviews.
- Pending reviews do not appear publicly.
- Hidden reviews do not appear publicly.
- Review forms include CSRF protection and show validation errors.

## Certificate Verification Tests

- Issued certificate appears on student certificate page.
- `barryvdh/laravel-dompdf` is installed before testing binary PDF output.
- Issued certificate detail page shows Download PDF and Print actions.
- Student can download their own issued certificate.
- Certificate download includes student name, course title, certificate number, verification code, issued date, and verification URL.
- Guest cannot download a student certificate.
- Another student cannot download someone else's certificate.
- Admin can download an issued certificate PDF from the Filament certificate record action.
- A normal student cannot use the admin certificate PDF route.
- Missing certificate score or skills do not break PDF/fallback download.
- Verification URL shows valid certificate data and skills.
- Revoked or missing verification code shows invalid state.
- Browser print button works for certificate detail.
- Printable HTML fallback works if DomPDF is not installed.
- Public certificate verification stays accessible without login.

## Opportunity/Application Tests

- Student can browse opportunities and filter by type/country/deadline.
- Guest Apply redirects to login.
- Student Apply creates or opens one application per opportunity.
- Application tracker groups applications by status.
- Application detail shows requirements, missing documents, uploaded documents, admin feedback, and status timeline.
- Application documents accept only allowed file types.
- Submitting application records submitted state and status history.

## Notification Tests

- Payment submitted notifies admins.
- Payment approved/rejected notifies student.
- Assignment graded notifies student.
- Mentor check-in scheduled notifies student and mentor.
- Certificate issued notifies student.
- Application status change notifies student.
- Live class creation notifies enrolled students when linked to a course.
- With `MK_EMAIL_NOTIFICATIONS_ENABLED=false`, important events still create in-app notifications and do not send email.
- With `MK_EMAIL_NOTIFICATIONS_ENABLED=true` and `MAIL_MAILER=log` or `array`, payment approved/rejected emails are generated safely.
- With email enabled, subscription approved/rejected payment emails are generated safely.
- With email enabled, assignment graded, certificate issued, and application status changed emails are generated safely.
- Users without an email address do not trigger mail sending errors.
- Email sending remains disabled after config caching when `MK_EMAIL_NOTIFICATIONS_ENABLED=false`.
- Mail failures should not block payment review, grading, certificate issue, or application status updates.
- No payment proof files, private upload files, or SMTP credentials are included in email content.

## Mobile/UX Tests

- Public navbar and mobile menu are usable on a phone-width viewport.
- Cards stack cleanly and buttons do not overflow.
- Forms show validation errors.
- Tables or report grids scroll horizontally where needed.
- Uploaded file names and verification links wrap instead of breaking layout.
## Branding QA Tests

- MK Scholars logo appears in the public navbar and footer.
- MK Scholars logo appears on the home hero without stretching or crowding mobile layouts.
- MK Scholars logo appears on login and register pages.
- MK Scholars logo appears through the shared navbar on student, instructor, and mentor dashboards.
- MK Scholars logo appears on certificate print/detail view and certificate PDF output.
- Filament admin uses MK Scholars brand name, logo, and favicon.
- Favicon loads from `public/favicon.webp` without a broken image path.
- Navy, gold, white, and soft light background colors match the logo and keep readable contrast.
- Buttons, links, badges, and cards remain readable after brand color polish.
- No page uses a local Windows file path for images.
- `npm run build` passes after branding changes.
## Role Dashboard UI Tests

- Student dashboard uses the role dashboard shell with sidebar, top bar, and mobile menu.
- Instructor dashboard uses the role dashboard shell with sidebar, top bar, and mobile menu.
- Mentor dashboard uses the role dashboard shell with sidebar, top bar, and mobile menu.
- Student sidebar links point only to existing student routes and include Notifications.
- Instructor sidebar links point only to existing instructor routes and include Notifications.
- Mentor sidebar links point only to existing mentor routes and include Notifications.
- Settings/Profile appears as a disabled placeholder until a real settings route exists.
- Logout works from the role dashboard shell.
- Mobile dashboard menu opens, stacks links cleanly, and does not create horizontal overflow.
- Student, instructor, and mentor role protection remains unchanged after dashboard polish.
## Phase 26B Dashboard Navigation QA

- Student, instructor, and mentor dashboards show the shared role dashboard shell on desktop.
- Desktop sidebar highlights the active route and keeps role-specific links only.
- Mobile menu opens with role-specific links, notification access, disabled Settings placeholder, and Logout.
- Topbar remains readable with role label, page title, notification link, and user initial.
- Disabled Settings placeholder is clearly marked and does not link to a fake route.
- Logout uses POST with CSRF from desktop sidebar and mobile menu.
- Dashboard pages do not render the public navbar or public footer content.
- Student dashboard links open existing student pages only.
- Instructor dashboard links open instructor courses, live classes, and notifications only.
- Mentor dashboard links open assigned students, check-ins, and notifications only.
- Guests are redirected to login from student, instructor, and mentor dashboards.
- Wrong roles are blocked from student, instructor, and mentor dashboards.
- Pending, rejected, or suspended instructor and mentor accounts remain blocked.
- Dashboard content has no horizontal overflow on phone-width screens.
## Phase 27A Academy Visual QA

- Admin can open the Academies resource in Filament.
- Admin can upload an academy image using JPG, PNG, or WebP up to 4MB.
- Admin can select an academy icon from the predefined safe icon dropdown.
- Academies table shows academy name, status, course count, icon label, and image thumbnail where available.
- Public academies page shows uploaded academy images without broken image paths.
- Public academies page uses a clean fallback image when an academy has no uploaded image.
- Public academies page uses a safe default icon when an academy has no icon selected.
- Academy cards show the selected icon near the title/image and remain mobile-friendly.
- Course cards show academy labels with the selected academy icon without overcrowding the card.
- Course detail page shows the academy label with the selected academy icon.
- Existing academy records created before image support still render without errors.
- Run `php artisan storage:link` in environments where uploaded public academy images need to be served.
## Phase 27B Academy Visual Polish QA

- Admin academy image upload still accepts JPG, PNG, and WebP up to 4MB.
- Admin academy image upload stores files on the public disk under the academies directory.
- Admin academy table shows a clear fallback placeholder when no image is uploaded.
- Admin can select each predefined academy icon without entering arbitrary icon HTML.
- Invalid or missing academy icon data falls back to Book Open on public pages.
- Academy cards with uploaded images show the image, icon badge, title, course count, summary, and View Courses action.
- Academy cards without uploaded images show the local navy/gold CSS fallback, not a remote Unsplash image.
- Academy cards without uploaded images do not render broken image icons.
- Public academy page has no external fallback image dependency for missing academy images.
- Course cards and course detail pages still render academy icon labels safely when academy image/icon is missing.
- Academy cards remain clean on phone-width screens and summaries/buttons do not overflow.
- Confirm `php artisan storage:link` exists in deployed environments so uploaded academy images are publicly served.


## Phase 28A Course Overview Rich Content QA

- Admin can edit a course overview with headings, paragraphs, links, bullet lists, numbered lists, images, and Markdown tables.
- Admin course cover image upload still stores JPG, PNG, and WebP files on the public disk under the courses directory.
- Public course detail page renders rich course overview content with MK Scholars navy/gold styling.
- Public course detail page tables scroll horizontally on mobile without breaking the layout.
- Public course detail page renders Markdown images responsively without overflow.
- Unsafe scripts, event handlers, unsafe embeds, and JavaScript links do not render on public course pages.
- Pending or missing full course descriptions fall back to the short course summary without errors.
- Missing course cover images still use the existing academy-based fallback image without broken images.
- Course cards on the courses page continue to show escaped short summaries only and do not render full rich overview HTML.
- Course detail headings, lists, tables, and links remain readable on phone-width screens.
- Instructor live classes page still loads after course overview rendering changes.

## Phase 28B Course Overview Rich Content QA Polish

- Rich course overview renders headings, paragraphs, bullet lists, numbered lists, blockquotes, code blocks, links, images, and tables.
- Long links, long words, and code blocks wrap or scroll without causing horizontal page overflow.
- Markdown tables are wrapped in the responsive `mk-rich-table` container and scroll horizontally on phone-width screens.
- Unsafe raw HTML tags such as script, iframe, object, embed, form, style, link, and meta do not appear in public course overview output.
- Event handler attributes such as `onclick` and `onerror` are removed from rendered overview content.
- Unsafe URL schemes such as `javascript:`, `vbscript:`, and `data:` are neutralized or stripped from rendered links/images.
- Admin Markdown formatting still renders after sanitizer hardening.
- Course detail overview sidebar remains readable with long course titles, long durations, and zero lessons.
- Course detail page still falls back to short summary and fallback image when full overview or cover image is missing.
- Course cards continue to render escaped summaries only and never render rich overview HTML or raw Markdown.
- Admin Course editor still saves title, short summary, full overview, cover image, and learning outcomes normally.

## Phase 29A Lesson YouTube Video QA

- Admin can add a normal YouTube watch URL to a lesson, such as `https://www.youtube.com/watch?v=VIDEO_ID`.
- Admin can add a short YouTube URL, such as `https://youtu.be/VIDEO_ID`.
- Admin can add a YouTube embed URL, such as `https://www.youtube.com/embed/VIDEO_ID`.
- Admin can add a YouTube Shorts URL, such as `https://www.youtube.com/shorts/VIDEO_ID`.
- Admin cannot save or should receive validation feedback for non-YouTube video URLs where the lesson form validation is active.
- Admin should not paste iframe HTML into the lesson video URL field.
- Student learning page renders a responsive 16:9 YouTube player for valid lesson video URLs.
- YouTube player does not overflow on mobile screens.
- Lesson without a video URL still loads and shows written lesson content.
- Invalid, non-YouTube, `javascript:`, or `data:` video URLs do not render an iframe.
- Written lesson content remains escaped and does not render raw iframe/script HTML.
- Existing course access rules still block unenrolled students from video lessons.
- Free course enrollment/access still works with video lessons.
- Paid course and active subscription access rules continue to apply before a video lesson can be viewed.

## Phase 29B Lesson Video Rendering QA

- YouTube watch URLs with extra query parameters, playlist values, or time offsets still render the correct embed video.
- `youtu.be` URLs with query strings still render the correct embed video.
- YouTube embed URLs with query strings still render without leaking unsafe query data into the iframe source.
- Fake YouTube-looking domains such as `youtube.com.evil.com` are blocked.
- Invalid YouTube video IDs are blocked.
- Raw iframe HTML pasted into the video URL field is rejected or ignored and does not render.
- `javascript:`, `data:`, and unsupported URL schemes do not render an iframe.
- Lessons without video URLs still load normally and show written lesson content.
- Written lesson content remains escaped and does not execute iframe/script HTML.
- The lesson video iframe keeps a responsive 16:9 ratio on mobile and desktop.
- Unenrolled students cannot access video lessons through the learning page.
- Free, approved payment, and active subscription access rules continue to control video lesson access.

## Phase 30A Student Learning Page Layout QA

- Student learning page uses the student dashboard workspace shell instead of the public marketing navbar/footer.
- Lesson sidebar shows the course title context, progress, modules, lessons, completed state, and current lesson indicator.
- Main lesson area stays focused on lesson title, type, duration, video, written content, previous/next lesson navigation, and Mark Lesson Complete.
- YouTube lesson videos still render through the safe responsive 16:9 iframe helper.
- Missing or invalid lesson videos still show a safe empty video state without rendering an iframe.
- Written lesson content remains escaped and does not execute raw script or iframe HTML.
- Learning tools panel shows quiz access when a published quiz exists.
- Learning tools panel shows assignments and submission status when assignments exist.
- Materials and activities appear in the tools panel and external resources open safely in a new tab.
- Live class actions remain available from the tools panel without changing attendance logic.
- Completion checklist and certificate eligibility remain visible in the tools panel.
- Course review form/status remains available without changing moderation rules.
- Mobile layout stacks with the main lesson content first, then lesson list, then tools, without horizontal overflow.
- Existing free, approved payment, active subscription, and unauthorized access rules continue to apply before the learning page loads.

## Phase 30B Learning Page Layout QA and Navigation Polish

- Desktop learning page uses the student dashboard shell with no public navbar or public footer.
- Desktop learning layout keeps the lesson path, main lesson content, and tools panel readable without horizontal overflow.
- Tablet learning layout keeps columns and spacing clean before stacking.
- Mobile learning layout shows main lesson content first, then lesson path, then learning tools.
- Lesson Tools jump link moves students to the tools panel on smaller screens.
- Sidebar shows course title, progress, modules, lessons, active lesson, and completed lesson states clearly.
- Long course titles and lesson titles wrap without breaking cards or creating horizontal scrolling.
- Previous Lesson and Next Lesson buttons follow published lesson sort order.
- Completed lessons show a clear completed state and do not show confusing duplicate completion actions.
- Mark Lesson Complete remains available for incomplete lessons and submits with CSRF.
- Learning tools panel shows clear empty states when no quiz, assignments, materials, or live classes exist.
- Learning tools panel links for quizzes, assignments, resources, live classes, and reviews point to real existing routes or external resources.
- Valid YouTube video URLs still render a responsive 16:9 iframe.
- Invalid, missing, or unsafe video URLs do not render an iframe or broken video box.
- Written lesson content remains escaped, readable, and wraps long text safely.
- Access control continues to block unenrolled students and wrong roles before the learning page renders.
- Free course, approved course payment, and active subscription access continue to work.
- Pending, rejected, expired, or cancelled subscription states still do not grant paid course access.

## Phase 31A Assignment Builder Improvement QA

- Admin can create or edit an assignment with normal instructions, submission type, score, late-work setting, and status.
- Admin can upload an optional assignment document using PDF, DOC, DOCX, TXT, ZIP, PNG, JPG, or JPEG up to 10MB.
- Admin can add, reorder, and edit simple assignment questions with text or textarea answer types.
- Assignment questions can be marked required or optional and can include points.
- Student assignment page shows assignment instructions, optional assignment document link, and assignment questions.
- Student can submit required question answers safely.
- Required assignment questions cannot be submitted blank.
- Student can submit a file using the existing safe file validation rules.
- Existing file-only, text-only, link-only, and mixed assignment submission behavior remains compatible.
- Student can see their own submitted question answers and uploaded file link after submission.
- Another student cannot see someone else's submitted answers.
- Admin grading view shows submitted text, file/link fields, question answers, score, feedback, and status.
- Instructor submissions preview shows question answers in read-only mode for assigned courses.
- Learning tools panel shows assignment question count and document badge/link when available.
- Unenrolled or unauthorized students cannot open or submit assignments.
- File links use public storage URLs and do not expose local server paths.
## Phase 31B Assignment Builder QA and File Validation

- Admin can create an assignment with multiple text/textarea questions and reorder them without losing question data.
- Required assignment questions block blank submissions and show readable validation errors.
- Optional assignment questions can be left blank while the assignment is still submitted through another required answer type.
- Student cannot submit answers for questions that belong to a different assignment.
- Assignment instruction document links and learning-page document badges appear only when the stored public file exists.
- Missing instruction files and missing submitted files show clean empty states with no broken links.
- Student upload accepts allowed document/image/archive types and rejects invalid or oversized files.
- Existing file-only, text-only, link-only, and mixed assignments still submit with the original validation rules.
- Admin assignment submission review shows question answers and submitted file status without exposing raw server paths.
- Instructor assignment submission preview remains read-only and shows answers/files only for assigned courses.
- Unenrolled students cannot open or submit assignments, and another student cannot see someone else's answers.
- Public storage link setup is documented and verified before testing downloadable assignment files.
## Phase 32A Quiz Builder Polish QA

- Admin can create a quiz with a clear title, lesson, instructions, passing score, attempt limit, time limit, and published status.
- Admin can add multiple-choice and true/false questions with points, status, and display order.
- Admin can add answer options, mark the correct option clearly, and control option display order.
- Draft quiz questions do not appear on the student quiz page.
- Student can open a published quiz from the learning page when enrolled and authorized.
- Learning page quiz card shows quiz title, published question count, latest attempt status when available, and a working quiz link.
- Student quiz page shows quiz overview, instructions, passing score, time limit, question numbers, points, and readable options.
- Student cannot submit a quiz without answering every published question.
- Student cannot submit an option that belongs to another quiz question.
- Quiz result page shows score, percentage, pass/fail status, correct count, answer review, and back-to-learning link.
- Student cannot view another student's quiz result by changing the attempt query string.
- Unenrolled students and wrong roles cannot open or submit protected quizzes.
- Quizzes with missing options show a clean message and do not crash.
- Mobile quiz page stacks cleanly and keeps submit/results readable.
## Phase 32B Quiz Builder QA and Attempt Validation

- Create and publish a multiple-choice quiz with ordered questions and ordered answer options.
- Create and publish a true/false quiz with True and False options and one correct answer.
- Confirm draft/unpublished quizzes cannot be opened or submitted by students.
- Confirm quizzes with no published questions show a clean not-ready state and block submission.
- Confirm questions with missing answer options show a clean message and block submission.
- Confirm students must answer every published question before submitting.
- Confirm fake option IDs, unrelated option IDs, and answers for another quiz question are rejected.
- Confirm score, total points, percentage, and pass/fail status calculate correctly.
- Confirm zero-point questions and deleted/missing selected options do not crash scoring or result pages.
- Confirm answer keys are not visible before submission and only appear in result review after submission.
- Confirm students cannot view another student's quiz attempt/result by changing query parameters.
- Confirm learning page quiz cards show title, published question count, no-attempt ready state, and latest attempt status.
- Confirm free course, approved payment, and active subscription access can open quizzes, while unpaid or inactive access cannot.
- Confirm quiz page and result page remain readable on mobile.


## Phase 33A Certificate Signature Support QA

- Admin can create or edit a certificate without signer fields and the PDF/HTML fallback still shows a clean Authorized Signature area.
- Admin can add signer name and signer title without uploading an image, and both appear on student certificate details, PDF/HTML fallback, and public verification.
- Admin can upload an optional PNG, JPG, JPEG, or WebP signature image up to 2MB using public storage.
- Uploaded certificate signatures display through safe public URLs or embedded PDF data URIs without exposing local server file paths.
- Missing or deleted signature image files do not render broken images and do not break certificate downloads.
- Public certificate verification only shows valid issued certificates and never exposes raw signature storage paths as text.
- Student certificate download, print, and verification links still work for certificates with and without signatures.
- Revoked certificates remain invalid publicly and unavailable for student PDF download.
- Confirm `php artisan storage:link` is configured before manually checking uploaded signature images in a browser.

## Phase 33B Certificate Signature QA and PDF/Verification Polish

- Certificate without signer fields or signature image renders with a clean Authorized Signature fallback.
- Certificate with signer name/title only renders on student detail, public verification, and PDF/HTML fallback.
- Certificate with uploaded PNG, JPG, JPEG, or WebP signature renders without exposing local server paths.
- Missing or deleted signature image files show no broken image and do not expose stored file paths.
- Public certificate verification still shows valid certificates and safe invalid-code states.
- Student can view/download their own certificate and cannot view/download another student's certificate.
- Admin can download issued certificates with or without signature metadata.
- Filament signature upload rejects unsupported file types.
- Filament signature upload rejects oversized files above 2MB.
- `php artisan storage:link` is configured before browser testing uploaded public signature images.
- Public verification and student certificate pages remain readable on mobile with signer/signature blocks.
- Long student names, course titles, signer names, and signer titles wrap without crashing certificate rendering.

## Phase 34A User Settings Pages QA

- Student can open `/student/settings` from desktop and mobile dashboard navigation.
- Approved instructor can open `/instructor/settings` from desktop and mobile dashboard navigation.
- Approved mentor can open `/mentor/settings` from desktop and mobile dashboard navigation.
- Dashboard sidebar shows a real Settings link and no longer shows the disabled Settings soon placeholder.
- Profile form updates only the authenticated user's own name.
- Email is visible but not editable on settings pages.
- Password change succeeds with the correct current password and confirmed new password.
- Password change fails with a clear validation error when the current password is wrong.
- Wrong roles cannot access another role's settings page.
- Pending, rejected, and suspended instructor/mentor accounts are blocked by existing approval middleware.
- Settings pages do not expose password hashes, internal user IDs, or sensitive account data.
- Settings pages remain readable and usable on mobile screens.

## Phase 35A Admin Reports UI Polish QA

- Admin reports overview at `/admin/reports` looks like a polished analytics dashboard, not raw text.
- Report header shows Reports & Analytics, MK Scholars analytics branding, and the current report context.
- Filter panel is inside a clean card and supports date, course, and status filters where available.
- KPI cards display real values from the report service and stack cleanly on mobile.
- Report tables have readable headers, spacing, soft dividers, status badges, and horizontal scroll on small screens.
- Empty report tables show a friendly empty state instead of blank output.
- Export Center shows CSV export cards with clear descriptions and working download buttons.
- CSV export links remain admin-only and continue returning CSV files.
- Guest, student, instructor, and mentor users cannot access admin reports.
- Reports page does not expose password hashes, private file paths, or provider payloads.
- Check Filament dark mode if enabled to confirm cards, filters, and tables remain readable.

## Phase 35B Admin Reports Emergency Browser UI QA

- Open `/admin/reports/certificates` and confirm it looks like a dashboard, not raw stacked text.
- Certificate report shows a visible header card with Reports & Analytics and Certificate Report context.
- Certificate report filters are inside a visible card with labels, inputs/selects, Apply Filters, and Reset actions.
- Certificate report KPI values are inside separate cards and do not appear as loose vertical text.
- Certificate report table/list area is inside a visible content card with headers, row count, read-only badge, and empty state when needed.
- CSV export area appears as styled export cards/buttons on the reports overview and preserves existing admin export links.
- Check `/admin/reports/courses`, `/admin/reports/payments`, and `/admin/reports/learning` for the same header, filter, KPI, table, and empty-state pattern.
- Report tables scroll horizontally on narrow mobile widths without breaking the admin layout.
- If Filament dark mode is enabled, report cards, filters, KPI cards, tables, and export buttons remain readable.
- CSV downloads still work from the export center and remain admin-only.
- Guest, student, instructor, and mentor accounts cannot access admin report pages.

## Phase 36A Role Dashboard Shell and Login Redirect QA

- Student login redirects to `/student/dashboard`.
- Approved instructor login redirects to `/instructor/dashboard`.
- Approved mentor login redirects to `/mentor/dashboard`.
- Admin login through the public login form redirects to `/admin`.
- Pending, rejected, and suspended instructor or mentor accounts are blocked with a clear approval message.
- Student, instructor, and mentor dashboards show the shared sidebar and topbar shell.
- Sidebar links open pages that remain inside the dashboard shell, without the public navbar or footer.
- Student dashboard cards wrap into a responsive grid on mobile, tablet, and desktop.
- Student dashboard quick action buttons do not overflow on phone-width screens.
- Dashboard mobile menu opens and shows role-specific links, Settings, Notifications, and Logout.
- Notification link points to the existing role notification route and shows an unread badge only when unread notifications exist.
- Logout works from the sidebar and mobile dashboard menu.
- Settings links open `/student/settings`, `/instructor/settings`, and `/mentor/settings` for the right roles only.
- Wrong roles remain blocked from student, instructor, and mentor dashboard pages.

## Phase 36B Dashboard Shell QA and Notification Badge Polish

- Student dashboard, my courses, learning page, assignments, quizzes, certificates, documents, applications, payments, subscriptions, mentorship, live classes, notifications, and settings all render inside the dashboard shell.
- Instructor dashboard, courses, course detail, students, submissions, quiz attempts, live classes, notifications, and settings all render inside the dashboard shell.
- Mentor dashboard, assigned students, check-ins, notifications, and settings all render inside the dashboard shell.
- Dashboard sidebar active states highlight the current section on desktop and mobile menu links.
- Topbar notification link points to the correct role notification route.
- Notification badge is hidden when unread count is zero.
- Notification badge appears when unread notifications exist and caps high counts as `99+`.
- Marking one notification as read updates the unread badge state.
- Notifications page uses the dashboard shell, has a friendly empty state, and does not show the public navbar/footer.
- Dashboard mobile menu remains usable on phone-width screens without horizontal overflow.
- Student dashboard cards remain in one column on mobile, two on tablet, and two/three on desktop depending on content width.
- Approved student, instructor, mentor, and admin users still redirect to the correct dashboard after login.
- Pending, rejected, and suspended instructor/mentor users remain blocked from protected workspaces.
- Wrong roles remain blocked from student, instructor, and mentor workspaces.

## Phase 37A Full Browser UI Audit QA

- `/admin/reports` and every report subpage render inside the polished admin report shell, not as raw text.
- Admin report filters, KPI cards, tables, and export cards have readable spacing, contrast, and mobile stacking.
- Student workspace pages render inside the dashboard shell with no public navbar/footer and no duplicated public container margins.
- Instructor workspace pages render inside the dashboard shell with readable cards/tables and no mobile horizontal page overflow.
- Mentor workspace pages render inside the dashboard shell with readable cards/tables and no mobile horizontal page overflow.
- Notification badge remains hidden at zero unread notifications, visible for unread notifications, and capped at `99+`.
- Dashboard tables scroll inside their card region on phone-width screens instead of forcing the whole page wider.
- Old public-page wrappers such as `mk-container` and `py-16` do not create oversized spacing inside dashboard pages.
- Empty states remain visible and readable on student, instructor, mentor, and admin report pages.
- Manual browser pass checks desktop and mobile widths for admin reports, student workspace, instructor workspace, mentor workspace, and notifications.

## Assignment Submission Review UI QA

- Admin assignment submission edit page shows assignment, student, submitted date, status, text answer, question answers, file, external link, score, and feedback in structured panels.
- Question answers render as separate readable cards with `Question 1`, `Question 2`, and clean answer blocks.
- Textarea answers preserve line breaks and long answers wrap without horizontal overflow.
- Empty question answers show `No answer provided` or a clean empty state.
- Submitted file display shows a clean basename and `Download file` only when the public storage file exists.
- Missing submitted files show a safe missing-file message and no broken download action.
- External links render as escaped text plus a styled `Open link` action only for safe HTTP/HTTPS URLs.
- Instructor course submissions preview uses the same readable question-answer card format.
- Score and feedback fields in the admin grading panel have clear spacing and remain editable.
- Mobile review pages keep file names, URLs, and long answers wrapped inside their cards.

## Phase 37B Student Learning Page Redesign QA

- Desktop `/student/courses/{course}/learn` uses the modern learning workspace with dashboard shell, no public navbar, and no public footer.
- Course content appears in a floating/collapsible `Learning path` panel with the current lesson highlighted.
- `Course content` / sidebar toggle is visible and usable on desktop and mobile.
- Completed lessons show check indicators and current lesson shows a clear current state.
- Center lesson area contains breadcrumb/module context, lesson title, type/duration/completion badges, and summary.
- Valid YouTube lessons render a large responsive 16:9 iframe through the safe embed helper.
- Missing or invalid video URLs show a clean empty state and no broken iframe.
- Fullscreen action is visible near the video and YouTube native fullscreen remains available.
- Lesson notes preserve line breaks, escape HTML/scripts, wrap long content, and remain readable.
- Resources/materials area shows attached resources or a clean empty state.
- Right tools panel shows clean Quiz, Assignment, Materials, Progress, and Live Classes cards.
- Quiz and assignment cards link to the existing student quiz/assignment routes.
- Previous/next lesson navigation is readable and not crowded.
- Notification badge remains in the dashboard topbar and role navigation still works.
- Enrolled students can access the page; unenrolled or unpaid students remain blocked by existing access rules.
- Mobile layout stacks cleanly with no horizontal page overflow.
