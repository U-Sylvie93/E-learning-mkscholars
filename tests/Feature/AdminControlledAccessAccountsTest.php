<?php

namespace Tests\Feature;

use App\Filament\Resources\Certificates\CertificateResource;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Academy;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControlledAccessAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_define_viewer_and_content_editor_access_fields(): void
    {
        $course = $this->course();

        $viewer = $this->user(User::ROLE_VIEWER, 'viewer-account@example.test', [
            'viewer_permissions' => [User::VIEWER_PERMISSION_COURSES],
        ]);
        $contentEditor = $this->user(User::ROLE_CONTENT_EDITOR, 'content-editor@example.test', [
            'content_permissions' => [User::CONTENT_PERMISSION_COURSES_EDIT],
            'content_course_ids' => [$course->id],
        ]);

        $this->assertContains(User::ROLE_VIEWER, User::ROLES);
        $this->assertContains(User::ROLE_CONTENT_EDITOR, User::ROLES);
        $this->assertTrue($viewer->hasViewerPermission(User::VIEWER_PERMISSION_COURSES));
        $this->assertTrue($contentEditor->hasContentPermission(User::CONTENT_PERMISSION_COURSES_EDIT));
        $this->assertTrue($contentEditor->canManageContentCourse($course->id));
    }

    public function test_viewer_can_only_view_allowed_sections_and_never_mutate(): void
    {
        $viewer = $this->user(User::ROLE_VIEWER, 'viewer-courses@example.test', [
            'viewer_permissions' => [User::VIEWER_PERMISSION_COURSES],
        ]);
        $student = $this->user(User::ROLE_STUDENT, 'viewer-student-target@example.test');
        $course = $this->course();
        $certificate = Certificate::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'student_name' => $student->name,
            'course_title' => $course->title,
            'status' => Certificate::STATUS_ISSUED,
            'issued_at' => now(),
        ]);

        $this->actingAs($viewer);

        $this->assertTrue($viewer->canAccessPanel(filament()->getPanel('admin')));
        $this->assertTrue(CourseResource::canViewAny());
        $this->assertFalse(PaymentResource::canViewAny());
        $this->assertFalse(UserResource::canCreate());
        $this->assertFalse(CertificateResource::canEdit($certificate));
        $this->assertFalse(CourseResource::canDelete($course));
    }

    public function test_viewer_with_no_permissions_sees_no_resource_sections(): void
    {
        $viewer = $this->user(User::ROLE_VIEWER, 'viewer-empty@example.test');

        $this->actingAs($viewer);

        $this->assertTrue($viewer->canAccessPanel(filament()->getPanel('admin')));
        $this->assertFalse(CourseResource::canViewAny());
        $this->assertFalse(PaymentResource::canViewAny());
        $this->assertFalse(UserResource::canViewAny());
    }

    public function test_content_editor_can_manage_only_assigned_content(): void
    {
        $assignedCourse = $this->course('assigned-content-course');
        $otherCourse = $this->course('other-content-course');
        $contentEditor = $this->user(User::ROLE_CONTENT_EDITOR, 'allowed-content-editor@example.test', [
            'content_permissions' => [
                User::CONTENT_PERMISSION_COURSES_EDIT,
                User::CONTENT_PERMISSION_MODULES_MANAGE,
                User::CONTENT_PERMISSION_QUIZZES_MANAGE,
            ],
            'content_course_ids' => [$assignedCourse->id],
        ]);

        $this->actingAs($contentEditor);

        $this->assertTrue($contentEditor->canAccessPanel(filament()->getPanel('admin')));
        $this->assertTrue(CourseResource::canViewAny());
        $this->assertTrue(CourseResource::canEdit($assignedCourse));
        $this->assertFalse(CourseResource::canEdit($otherCourse));
        $this->assertFalse(PaymentResource::canViewAny());
        $this->assertFalse(UserResource::canViewAny());
        $this->assertFalse(CertificateResource::canViewAny());
    }

    public function test_content_editor_needs_create_permission_to_create_courses(): void
    {
        $contentEditor = $this->user(User::ROLE_CONTENT_EDITOR, 'create-content-editor@example.test', [
            'content_permissions' => [User::CONTENT_PERMISSION_COURSES_CREATE],
        ]);

        $this->actingAs($contentEditor);

        $this->assertTrue(CourseResource::canCreate());
        $this->assertFalse(PaymentResource::canCreate());
    }

    public function test_instructor_cannot_create_access_accounts_or_assign_permissions(): void
    {
        $instructor = $this->user(User::ROLE_INSTRUCTOR, 'blocked-access-instructor@example.test');

        $this->actingAs($instructor);

        $this->assertFalse($instructor->canAccessPanel(filament()->getPanel('admin')));
        $this->assertFalse(UserResource::canCreate());

        $this->post('/admin/users', [
            'name' => 'Blocked Viewer',
            'email' => 'blocked-viewer@example.test',
            'password' => 'password',
            'role' => User::ROLE_VIEWER,
            'viewer_permissions' => [User::VIEWER_PERMISSION_USERS],
        ])->assertForbidden();
    }

    public function test_viewer_cannot_promote_self_or_change_settings(): void
    {
        $viewer = $this->user(User::ROLE_VIEWER, 'self-promote-viewer@example.test', [
            'viewer_permissions' => [User::VIEWER_PERMISSION_USERS],
        ]);

        $this->actingAs($viewer);

        $this->assertFalse(UserResource::canEdit($viewer));

        $this->post(route('admin.account-settings.profile'), [
            'name' => 'Promoted Viewer',
            'email' => 'self-promote-viewer@example.test',
            'role' => User::ROLE_ADMIN,
        ])->assertForbidden();

        $this->assertSame(User::ROLE_VIEWER, $viewer->refresh()->role);
    }

    private function user(string $role, string $email, array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => str($role)->headline()->toString(),
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
        ], $overrides));
    }

    private function course(string $slug = 'access-course'): Course
    {
        $academy = Academy::factory()->create(['status' => Academy::STATUS_PUBLISHED]);

        return Course::factory()->create([
            'academy_id' => $academy->id,
            'slug' => $slug,
            'status' => Course::STATUS_PUBLISHED,
        ]);
    }
}
