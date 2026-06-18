<?php

namespace Tests\Feature;

use App\Models\EthicsDisciplineViolation;
use App\Models\EthicsPoliticalViolation;
use App\Models\EthicsProfile;
use App\Models\EthicsWarning;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
    }

    public function test_dashboard_includes_ethics_summary_props(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $profile = EthicsProfile::factory()->create();

        EthicsWarning::factory()->create([
            'ethics_profile_id' => $profile->id,
            'warning_level' => 'blue',
            'status' => 'open',
        ]);
        EthicsWarning::factory()->create([
            'ethics_profile_id' => $profile->id,
            'warning_level' => 'red',
            'status' => 'closed',
            'closed_at' => now(),
        ]);
        EthicsPoliticalViolation::factory()->create([
            'ethics_profile_id' => $profile->id,
            'staff_no' => $profile->staff_no,
            'violation_at' => now(),
            'deduction_points' => 1,
        ]);
        EthicsDisciplineViolation::factory()->create([
            'ethics_profile_id' => $profile->id,
            'staff_no' => $profile->staff_no,
            'violation_at' => now(),
            'deduction_points' => 1,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('summary.profileCount', 1)
            ->where('summary.openWarningCount', 1)
            ->where('summary.warningLevels.blue', 1)
            ->where('summary.warningLevels.yellow', 0)
            ->where('summary.warningLevels.red', 0)
            ->where('summary.violations.political', 1)
            ->where('summary.violations.discipline', 1)
            ->where('summary.totalViolationCount', 2)
        );
    }

    public function test_dashboard_warning_counts_include_computed_score_warnings(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        EthicsPoliticalViolation::factory()->create([
            'ethics_profile_id' => null,
            'staff_no' => 'WARN-BLUE',
            'staff_name' => '蓝色预警老师',
            'staff_unit_name' => '测试学院',
            'violation_at' => now(),
            'deduction_points' => 5,
        ]);
        EthicsPoliticalViolation::factory()->create([
            'ethics_profile_id' => null,
            'staff_no' => 'WARN-YELLOW',
            'staff_name' => '黄色预警老师',
            'staff_unit_name' => '测试学院',
            'violation_at' => now(),
            'deduction_points' => 10,
        ]);
        EthicsPoliticalViolation::factory()->create([
            'ethics_profile_id' => null,
            'staff_no' => 'WARN-RED',
            'staff_name' => '红色预警老师',
            'staff_unit_name' => '测试学院',
            'violation_at' => now(),
            'deduction_points' => 20,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('summary.openWarningCount', 3)
            ->where('summary.warningLevels.blue', 1)
            ->where('summary.warningLevels.yellow', 1)
            ->where('summary.warningLevels.red', 1)
            ->where('summary.warningPeople.blue.0.staff_no', 'WARN-BLUE')
            ->where('summary.warningPeople.blue.0.name', '蓝色预警老师')
            ->where('summary.warningPeople.blue.0.unit_name', '测试学院')
            ->where('summary.warningPeople.yellow.0.staff_no', 'WARN-YELLOW')
            ->where('summary.warningPeople.yellow.0.name', '黄色预警老师')
            ->where('summary.warningPeople.blue.0.profile_url', route('ethics.profiles.staff.show', [
                'staffNo' => 'WARN-BLUE',
                'year' => now()->year,
                'from' => 'dashboard',
            ]))
            ->where('summary.warningPeople.yellow.0.profile_url', route('ethics.profiles.staff.show', [
                'staffNo' => 'WARN-YELLOW',
                'year' => now()->year,
                'from' => 'dashboard',
            ]))
        );
    }

    public function test_profile_opened_from_dashboard_returns_to_workbench_and_uses_violation_identity(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_admin' => true,
        ]);
        $this->actingAs($user);

        EthicsPoliticalViolation::factory()->create([
            'ethics_profile_id' => null,
            'staff_no' => 'UNBOUND-01',
            'staff_name' => '未绑定测试老师',
            'staff_unit_name' => '测试单位',
            'violation_at' => now(),
            'deduction_points' => 5,
        ]);

        $response = $this->get(route('ethics.profiles.staff.show', [
            'staffNo' => 'UNBOUND-01',
            'year' => now()->year,
            'from' => 'dashboard',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ethics/Profiles/Show')
            ->where('profile.staff_no', 'UNBOUND-01')
            ->where('profile.name', '未绑定测试老师')
            ->where('profile.unit_name', '测试单位')
            ->where('returnTo.url', route('dashboard'))
            ->where('returnTo.label', '返回工作台')
        );
    }
}
