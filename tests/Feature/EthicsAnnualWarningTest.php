<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\EthicsEducationViolation;
use App\Models\EthicsPoliticalViolation;
use App\Models\EthicsProfile;
use App\Models\EthicsWarning;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EthicsAnnualWarningTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_yellow_warning_when_annual_deduction_reaches_five_points(): void
    {
        [$leader, $profile] = $this->createLeaderAndProfile('W-YELLOW-01');

        $this->actingAs($leader)->post('/ethics/education-violations', [
            'staff_no' => 'W-YELLOW-01',
            'staff_name' => 'Teacher Yellow',
            'staff_unit_name' => 'Test Department',
            'violation_type' => 10,
            'violation_at' => '2026-04-01 10:00:00',
            'deduction_points' => 2,
        ])->assertRedirect();

        $this->actingAs($leader)->post('/ethics/political-violations', [
            'staff_no' => 'W-YELLOW-01',
            'staff_name' => 'Teacher Yellow',
            'staff_unit_name' => 'Test Department',
            'violation_type' => 1,
            'violation_at' => '2026-04-02 10:00:00',
            'deduction_points' => 3,
        ])->assertRedirect();

        $warning = EthicsWarning::query()
            ->where('ethics_profile_id', $profile->id)
            ->where('source_type', 'teaching')
            ->latest('id')
            ->first();

        $this->assertNotNull($warning);
        $this->assertSame('yellow', $warning->warning_level);
        $this->assertStringStartsWith('AUTO_YEARLY_DEDUCTION|2026|', $warning->reason);
    }

    public function test_it_upgrades_warning_to_red_at_ten_points_without_creating_duplicates(): void
    {
        [$leader, $profile] = $this->createLeaderAndProfile('W-RED-01');

        $this->actingAs($leader)->post('/ethics/education-violations', [
            'staff_no' => 'W-RED-01',
            'staff_name' => 'Teacher Red',
            'staff_unit_name' => 'Test Department',
            'violation_type' => 10,
            'violation_at' => '2026-04-01 10:00:00',
            'deduction_points' => 5,
        ])->assertRedirect();

        $this->actingAs($leader)->post('/ethics/political-violations', [
            'staff_no' => 'W-RED-01',
            'staff_name' => 'Teacher Red',
            'staff_unit_name' => 'Test Department',
            'violation_type' => 2,
            'violation_at' => '2026-04-02 10:00:00',
            'deduction_points' => 5,
        ])->assertRedirect();

        $warnings = EthicsWarning::query()
            ->where('ethics_profile_id', $profile->id)
            ->where('source_type', 'teaching')
            ->where('reason', 'like', 'AUTO_YEARLY_DEDUCTION|2026|%')
            ->get();

        $this->assertCount(1, $warnings);
        $this->assertSame('red', $warnings->first()->warning_level);
    }

    public function test_scoring_page_staff_summary_includes_profile_link(): void
    {
        [$leader, $profile] = $this->createLeaderAndProfile('W-LINK-01');

        EthicsPoliticalViolation::factory()->create([
            'ethics_profile_id' => $profile->id,
            'violator_user_id' => $profile->user_id,
            'recorder_user_id' => $leader->id,
            'staff_no' => 'W-LINK-01',
            'staff_name' => 'Teacher Link',
            'staff_unit_name' => 'Test Department',
            'violation_at' => '2026-04-03 10:00:00',
            'deduction_points' => 4,
        ]);

        $this->actingAs($leader)
            ->get('/ethics/political-violations?year=2026')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ethics/PoliticalViolations/Index')
                ->where('staffSummaries.0.staff_no', 'W-LINK-01')
                ->where('staffSummaries.0.profile_url', route('ethics.profiles.staff.show', ['staffNo' => 'W-LINK-01', 'year' => 2026]))
            );
    }

    public function test_dashboard_shows_annual_warning_counts_and_people_list(): void
    {
        [$leader, $redProfile] = $this->createLeaderAndProfile('W-DASH-RED');

        $advisorYellow = User::factory()->withoutTwoFactor()->create([
            'role' => 'advisor',
            'department_id' => $leader->department_id,
        ]);

        $yellowProfile = EthicsProfile::factory()->create([
            'user_id' => $advisorYellow->id,
            'department_id' => $leader->department_id,
            'staff_no' => 'W-DASH-YELLOW',
        ]);

        EthicsEducationViolation::factory()->create([
            'ethics_profile_id' => $redProfile->id,
            'violator_user_id' => $redProfile->user_id,
            'recorder_user_id' => $leader->id,
            'staff_no' => 'W-DASH-RED',
            'staff_name' => 'Teacher Red',
            'staff_unit_name' => 'Test Department',
            'violation_type' => 10,
            'violation_at' => '2026-04-10 10:00:00',
            'deduction_points' => 6,
        ]);

        EthicsPoliticalViolation::factory()->create([
            'ethics_profile_id' => $redProfile->id,
            'violator_user_id' => $redProfile->user_id,
            'recorder_user_id' => $leader->id,
            'staff_no' => 'W-DASH-RED',
            'staff_name' => 'Teacher Red',
            'staff_unit_name' => 'Test Department',
            'violation_type' => 1,
            'violation_at' => '2026-04-11 10:00:00',
            'deduction_points' => 5,
        ]);

        EthicsEducationViolation::factory()->create([
            'ethics_profile_id' => $yellowProfile->id,
            'violator_user_id' => $yellowProfile->user_id,
            'recorder_user_id' => $leader->id,
            'staff_no' => 'W-DASH-YELLOW',
            'staff_name' => 'Teacher Yellow',
            'staff_unit_name' => 'Test Department',
            'violation_type' => 10,
            'violation_at' => '2026-04-12 10:00:00',
            'deduction_points' => 6,
        ]);

        $this->actingAs($leader)
            ->get('/ethics/dashboard?year=2026')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ethics/Dashboard')
                ->where('stats.redWarningPersonCount', 1)
                ->where('stats.yellowWarningPersonCount', 1)
                ->where('autoWarningPeople.red.0.staff_no', 'W-DASH-RED')
                ->where('autoWarningPeople.yellow.0.staff_no', 'W-DASH-YELLOW')
            );
    }

    /**
     * @return array{0: User, 1: EthicsProfile}
     */
    private function createLeaderAndProfile(string $staffNo): array
    {
        $department = Department::factory()->create([
            'name' => 'Test Department',
            'code' => 'TEST',
        ]);

        $leader = User::factory()->withoutTwoFactor()->create([
            'role' => 'leader',
            'department_id' => $department->id,
        ]);

        $advisor = User::factory()->withoutTwoFactor()->create([
            'role' => 'advisor',
            'department_id' => $department->id,
        ]);

        $profile = EthicsProfile::factory()->create([
            'user_id' => $advisor->id,
            'department_id' => $department->id,
            'staff_no' => $staffNo,
        ]);

        return [$leader, $profile];
    }
}

