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
        ]);
        EthicsDisciplineViolation::factory()->create([
            'ethics_profile_id' => $profile->id,
            'staff_no' => $profile->staff_no,
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
}
