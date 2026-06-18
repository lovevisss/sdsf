<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\EthicsDisciplineViolation;
use App\Models\EthicsEducationViolation;
use App\Models\EthicsPoliticalViolation;
use App\Models\EthicsProfile;
use App\Models\EthicsWarning;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EthicsScoringWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_severity_level_drives_deduction_points(): void
    {
        [$leader, $profile] = $this->createLeaderAndProfile('SCORE-A');

        $this->actingAs($leader)->post('/ethics/political-violations', [
            'staff_no' => $profile->staff_no,
            'staff_name' => 'Teacher A',
            'staff_unit_name' => 'Test Department',
            'violation_type' => 1,
            'severity_level' => 'B',
            'violation_at' => '2026-04-01 10:00:00',
        ])->assertRedirect();

        $this->assertDatabaseHas('ethics_political_violations', [
            'staff_no' => 'SCORE-A',
            'severity_level' => 'B',
            'deduction_points' => 10,
            'verification_status' => 'verified',
        ]);
    }

    public function test_dimension_score_is_capped_at_twenty_and_red_warning_is_created(): void
    {
        [$leader, $profile] = $this->createLeaderAndProfile('SCORE-CAP');

        EthicsPoliticalViolation::factory()->create([
            'ethics_profile_id' => $profile->id,
            'violator_user_id' => $profile->user_id,
            'recorder_user_id' => $leader->id,
            'staff_no' => 'SCORE-CAP',
            'staff_name' => 'Teacher Cap',
            'violation_at' => '2026-04-01 10:00:00',
            'deduction_points' => 10,
        ]);

        $this->actingAs($leader)->post('/ethics/political-violations', [
            'staff_no' => 'SCORE-CAP',
            'staff_name' => 'Teacher Cap',
            'staff_unit_name' => 'Test Department',
            'violation_type' => 2,
            'severity_level' => 'C',
            'violation_at' => '2026-04-02 10:00:00',
        ])->assertRedirect();

        $this->actingAs($leader)
            ->get('/ethics/profiles/staff/SCORE-CAP?year=2026')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ethics/Profiles/Show')
                ->where('summary.modules.political', 0)
                ->where('summary.totalScore', 80)
                ->where('summary.warningLevel', 'red')
            );

        $this->assertDatabaseHas('ethics_warnings', [
            'ethics_profile_id' => $profile->id,
            'warning_level' => 'red',
            'source_type' => 'scoring',
        ]);
    }

    public function test_blue_and_yellow_warning_thresholds_are_applied_without_duplicates(): void
    {
        [$leader, $profile] = $this->createLeaderAndProfile('SCORE-WARN');

        $this->actingAs($leader)->post('/ethics/education-violations', [
            'staff_no' => 'SCORE-WARN',
            'staff_name' => 'Teacher Warn',
            'staff_unit_name' => 'Test Department',
            'violation_type' => 10,
            'severity_level' => 'A',
            'violation_at' => '2026-04-01 10:00:00',
        ])->assertRedirect();

        $this->assertDatabaseHas('ethics_warnings', [
            'ethics_profile_id' => $profile->id,
            'warning_level' => 'blue',
        ]);

        $this->actingAs($leader)->post('/ethics/education-violations', [
            'staff_no' => 'SCORE-WARN',
            'staff_name' => 'Teacher Warn',
            'staff_unit_name' => 'Test Department',
            'violation_type' => 11,
            'severity_level' => 'A',
            'violation_at' => '2026-04-02 10:00:00',
        ])->assertRedirect();

        $warnings = EthicsWarning::query()
            ->where('ethics_profile_id', $profile->id)
            ->where('source_type', 'scoring')
            ->where('reason', 'like', 'AUTO_YEARLY_DEDUCTION|2025|%')
            ->get();

        $this->assertCount(1, $warnings);
        $this->assertSame('yellow', $warnings->first()->warning_level);
    }

    public function test_leader_can_store_discipline_violation_and_profile_shows_it(): void
    {
        [$leader, $profile] = $this->createLeaderAndProfile('DISC-01');

        $this->actingAs($leader)->post('/ethics/discipline-violations', [
            'staff_no' => 'DISC-01',
            'staff_name' => 'Teacher Discipline',
            'staff_unit_name' => 'Test Department',
            'violation_type' => 35,
            'deduction_points' => 3.5,
            'violation_at' => '2026-05-01 10:00:00',
            'deduction_basis' => '考勤异常月度汇总',
        ])->assertRedirect('/ethics/discipline-violations?staff_no=DISC-01');

        $this->assertDatabaseHas('ethics_discipline_violations', [
            'ethics_profile_id' => $profile->id,
            'staff_no' => 'DISC-01',
            'deduction_points' => 3.5,
        ]);

        $this->actingAs($leader)
            ->get('/ethics/profiles/staff/DISC-01?year=2026')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ethics/Profiles/Show')
                ->where('summary.modules.discipline', 16.5)
                ->where('deductionRecords.0.module_key', 'discipline')
            );
    }

    public function test_profile_detail_export_downloads_csv(): void
    {
        [$leader, $profile] = $this->createLeaderAndProfile('EXPORT-01');

        EthicsDisciplineViolation::factory()->create([
            'ethics_profile_id' => $profile->id,
            'violator_user_id' => $profile->user_id,
            'recorder_user_id' => $leader->id,
            'staff_no' => 'EXPORT-01',
            'staff_name' => 'Teacher Export',
            'violation_at' => '2026-04-01 10:00:00',
            'deduction_points' => 5,
        ]);

        $response = $this->actingAs($leader)->get('/ethics/reports/export?type=profile_details&year=2026');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
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

        $teacher = User::factory()->withoutTwoFactor()->create([
            'name' => 'Teacher '.$staffNo,
            'role' => 'advisor',
            'department_id' => $department->id,
        ]);

        $profile = EthicsProfile::factory()->create([
            'user_id' => $teacher->id,
            'department_id' => $department->id,
            'staff_no' => $staffNo,
        ]);

        return [$leader, $profile];
    }
}
