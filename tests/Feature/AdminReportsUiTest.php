<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_reports_page_loads_with_dashboard_structure_and_export_links(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'reports-admin@mkscholars.test');

        $this->actingAs($admin)
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('data-testid="admin-report-shell"', false)
            ->assertSee('data-testid="report-header-card"', false)
            ->assertSee('data-testid="report-kpi-grid"', false)
            ->assertSee('data-testid="report-content-card"', false)
            ->assertSee('data-testid="report-export-center"', false)
            ->assertSee('mk-report-card', false)
            ->assertSee('mk-report-kpi-card', false)
            ->assertSee('mk-report-table', false)
            ->assertSee('Reports &amp; Analytics', false)
            ->assertSee('Download CSV Reports')
            ->assertSee(route('admin.reports.exports.students'), false)
            ->assertSee(route('admin.reports.exports.payments'), false)
            ->assertDontSee('password_hash')
            ->assertDontSee('provider_payload');
    }

    public function test_certificate_report_page_loads_with_filter_kpi_table_cards(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'reports-certificates-admin@mkscholars.test');

        $this->actingAs($admin)
            ->get('/admin/reports/certificates')
            ->assertOk()
            ->assertSee('data-testid="admin-report-shell"', false)
            ->assertSee('data-testid="report-header-card"', false)
            ->assertSee('data-testid="report-filter-card"', false)
            ->assertSee('data-testid="report-kpi-grid"', false)
            ->assertSee('data-testid="report-content-card"', false)
            ->assertSee('Certificate Report')
            ->assertSee('Issued certificates')
            ->assertSee('Revoked certificates')
            ->assertSee('Eligible students')
            ->assertSee('Certificates by course')
            ->assertSee('mk-report-filter-card', false)
            ->assertSee('mk-report-kpi-card', false)
            ->assertSee('mk-report-content-card', false);
    }

    public function test_other_report_subpage_uses_same_polished_structure(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'reports-payments-admin@mkscholars.test');

        $this->actingAs($admin)
            ->get('/admin/reports/payments')
            ->assertOk()
            ->assertSee('data-testid="admin-report-shell"', false)
            ->assertSee('data-testid="report-header-card"', false)
            ->assertSee('data-testid="report-filter-card"', false)
            ->assertSee('data-testid="report-kpi-grid"', false)
            ->assertSee('data-testid="report-content-card"', false)
            ->assertSee('Payment Report')
            ->assertSee('Subscription payment health')
            ->assertSee('Revenue by course')
            ->assertSee('Apply Filters')
            ->assertSee('Reset');
    }

    public function test_all_admin_report_subpages_use_polished_report_shell(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'reports-all-subpages-admin@mkscholars.test');

        $paths = [
            '/admin/reports/students',
            '/admin/reports/courses',
            '/admin/reports/payments',
            '/admin/reports/learning',
            '/admin/reports/live-classes',
            '/admin/reports/mentorship',
            '/admin/reports/certificates',
            '/admin/reports/opportunities',
        ];

        foreach ($paths as $path) {
            $this->actingAs($admin)
                ->get($path)
                ->assertOk()
                ->assertSee('data-testid="admin-report-shell"', false)
                ->assertSee('data-testid="report-header-card"', false)
                ->assertSee('data-testid="report-filter-card"', false)
                ->assertSee('data-testid="report-kpi-grid"', false)
                ->assertSee('data-testid="report-content-card"', false)
                ->assertSee('mk-report-card', false)
                ->assertSee('mk-report-table', false)
                ->assertDontSee('password_hash')
                ->assertDontSee('provider_payload');
        }
    }
    public function test_guest_cannot_access_admin_reports_page(): void
    {
        $this->get('/admin/reports')
            ->assertRedirect('/admin/login');
    }

    public function test_non_admin_users_cannot_access_admin_reports_page(): void
    {
        foreach ([User::ROLE_STUDENT, User::ROLE_INSTRUCTOR, User::ROLE_MENTOR] as $role) {
            $user = $this->user($role, "reports-{$role}@mkscholars.test");

            $this->actingAs($user)
                ->get('/admin/reports')
                ->assertForbidden();
        }
    }

    private function user(string $role, string $email): User
    {
        return User::create([
            'name' => str($role)->headline()->toString(),
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);
    }
}
