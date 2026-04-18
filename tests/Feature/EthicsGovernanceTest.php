<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\EthicsCase;
use App\Models\EthicsEducationViolation;
use App\Models\EthicsPoliticalViolation;
use App\Models\EthicsProfile;
use App\Models\EthicsWarning;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EthicsGovernanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_view_ethics_dashboard(): void
    {
        $student = User::factory()->withoutTwoFactor()->create([
            'role' => 'student',
        ]);

        $response = $this->actingAs($student)->get('/ethics/dashboard');

        $response->assertForbidden();
    }

    public function test_legacy_admin_flag_can_view_ethics_dashboard(): void
    {
        $legacyAdmin = User::factory()->withoutTwoFactor()->create([
            'role' => 'student',
            'is_admin' => true,
        ]);

        $response = $this->actingAs($legacyAdmin)->get('/ethics/dashboard');

        $response->assertOk();
    }

    public function test_advisor_can_view_staff_archive_list_in_index(): void
    {
        $department = Department::factory()->create();

        $advisor = User::factory()->withoutTwoFactor()->create([
            'role' => 'advisor',
            'department_id' => $department->id,
        ]);

        $otherAdvisor = User::factory()->withoutTwoFactor()->create([
            'role' => 'advisor',
            'department_id' => $department->id,
        ]);

        EthicsProfile::factory()->create([
            'user_id' => $advisor->id,
            'department_id' => $department->id,
        ]);

        EthicsProfile::factory()->create([
            'user_id' => $otherAdvisor->id,
            'department_id' => $department->id,
        ]);

        $response = $this->actingAs($advisor)->get('/ethics/profiles');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Ethics/Profiles/Index')
            ->has('staffRecords.data')
            ->where('staffRecords.current_page', 1)
        );
    }

    public function test_student_can_submit_ethics_case(): void
    {
        $department = Department::factory()->create();
        $student = User::factory()->withoutTwoFactor()->create(['role' => 'student']);

        $response = $this->actingAs($student)->post('/ethics/cases', [
            'department_id' => $department->id,
            'channel' => 'mobile',
            'is_anonymous' => false,
            'title' => '课堂言行问题',
            'content' => '描述具体情况用于测试。',
            'risk_level' => 'medium',
        ]);

        $response->assertRedirect('/ethics/cases');

        $this->assertDatabaseHas('ethics_cases', [
            'reporter_id' => $student->id,
            'title' => '课堂言行问题',
            'status' => 'reported',
            'risk_level' => 'medium',
        ]);
    }

    public function test_leader_can_update_case_status_and_write_action_log(): void
    {
        $department = Department::factory()->create();

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
        ]);

        $case = EthicsCase::factory()->create([
            'ethics_profile_id' => $profile->id,
            'department_id' => $department->id,
            'status' => 'reported',
        ]);

        $response = $this->actingAs($leader)->patch("/ethics/cases/{$case->id}/status", [
            'status' => 'accepted',
            'notes' => '已受理并进入核查流程。',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('ethics_cases', [
            'id' => $case->id,
            'status' => 'accepted',
        ]);

        $this->assertDatabaseHas('ethics_case_actions', [
            'ethics_case_id' => $case->id,
            'actor_id' => $leader->id,
            'action_type' => 'accept',
        ]);
    }

    public function test_leader_can_create_and_close_warning_in_own_department(): void
    {
        $department = Department::factory()->create();

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
        ]);

        $createResponse = $this->actingAs($leader)->post('/ethics/warnings', [
            'ethics_profile_id' => $profile->id,
            'warning_level' => 'yellow',
            'source_type' => 'manual',
            'reason' => '测试预警创建。',
        ]);

        $createResponse->assertRedirect();

        $warning = EthicsWarning::query()->where('ethics_profile_id', $profile->id)->latest('id')->firstOrFail();

        $closeResponse = $this->actingAs($leader)->patch("/ethics/warnings/{$warning->id}/close");

        $closeResponse->assertRedirect();

        $this->assertDatabaseHas('ethics_warnings', [
            'id' => $warning->id,
            'status' => 'closed',
        ]);
    }

    public function test_leader_cannot_create_warning_for_other_department_profile(): void
    {
        $leaderDepartment = Department::factory()->create();
        $otherDepartment = Department::factory()->create();

        $leader = User::factory()->withoutTwoFactor()->create([
            'role' => 'leader',
            'department_id' => $leaderDepartment->id,
        ]);

        $otherAdvisor = User::factory()->withoutTwoFactor()->create([
            'role' => 'advisor',
            'department_id' => $otherDepartment->id,
        ]);

        $otherProfile = EthicsProfile::factory()->create([
            'user_id' => $otherAdvisor->id,
            'department_id' => $otherDepartment->id,
        ]);

        $response = $this->actingAs($leader)->post('/ethics/warnings', [
            'ethics_profile_id' => $otherProfile->id,
            'warning_level' => 'orange',
            'source_type' => 'manual',
            'reason' => '跨部门预警应被拦截。',
        ]);

        $response->assertSessionHasErrors('ethics_profile_id');

        $this->assertDatabaseMissing('ethics_warnings', [
            'ethics_profile_id' => $otherProfile->id,
            'reason' => '跨部门预警应被拦截。',
        ]);
    }

    public function test_admin_can_create_warning_for_other_department_profile(): void
    {
        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();

        $admin = User::factory()->withoutTwoFactor()->create([
            'role' => 'admin',
            'department_id' => $departmentA->id,
        ]);

        $advisor = User::factory()->withoutTwoFactor()->create([
            'role' => 'advisor',
            'department_id' => $departmentB->id,
        ]);

        $profile = EthicsProfile::factory()->create([
            'user_id' => $advisor->id,
            'department_id' => $departmentB->id,
        ]);

        $response = $this->actingAs($admin)->post('/ethics/warnings', [
            'ethics_profile_id' => $profile->id,
            'warning_level' => 'red',
            'source_type' => 'manual',
            'reason' => '管理员允许跨部门创建。',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('ethics_warnings', [
            'ethics_profile_id' => $profile->id,
            'warning_level' => 'red',
            'reason' => '管理员允许跨部门创建。',
        ]);
    }

    public function test_leader_can_store_political_violation_record(): void
    {
        $department = Department::factory()->create();

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
        ]);

        $response = $this->actingAs($leader)->post('/ethics/political-violations', [
            'staff_no' => 'T0001',
            'staff_name' => '测试老师',
            'staff_unit_name' => '计算机科学系',
            'violation_type' => 4,
            'violation_at' => now()->toDateTimeString(),
            'deduction_points' => 3.5,
            'notes' => '测试手工登记保密违规。',
        ]);

        $response->assertRedirect('/ethics/political-violations?staff_no=T0001');

        $this->assertDatabaseHas('ethics_political_violations', [
            'ethics_profile_id' => null,
            'staff_no' => 'T0001',
            'staff_name' => '测试老师',
            'recorder_user_id' => $leader->id,
            'violation_type' => 4,
        ]);
    }

    public function test_student_cannot_store_political_violation_record(): void
    {
        $department = Department::factory()->create();

        $student = User::factory()->withoutTwoFactor()->create([
            'role' => 'student',
            'department_id' => $department->id,
        ]);

        $advisor = User::factory()->withoutTwoFactor()->create([
            'role' => 'advisor',
            'department_id' => $department->id,
        ]);

        $profile = EthicsProfile::factory()->create([
            'user_id' => $advisor->id,
            'department_id' => $department->id,
        ]);

        $response = $this->actingAs($student)->post('/ethics/political-violations', [
            'staff_no' => 'T0002',
            'staff_name' => '无权限老师',
            'staff_unit_name' => '计算机科学系',
            'violation_type' => 1,
            'violation_at' => now()->toDateTimeString(),
            'deduction_points' => 1,
        ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('ethics_political_violations', 0);
    }

    public function test_leader_can_store_education_violation_record(): void
    {
        $department = Department::factory()->create();

        $leader = User::factory()->withoutTwoFactor()->create([
            'role' => 'leader',
            'department_id' => $department->id,
        ]);

        $response = $this->actingAs($leader)->post('/ethics/education-violations', [
            'staff_no' => 'T1001',
            'staff_name' => '教育测试老师',
            'staff_unit_name' => '计算机科学系',
            'violation_type' => 10,
            'violation_at' => now()->toDateTimeString(),
            'deduction_points' => 2.5,
            'notes' => '测试教育教学行为违规登记。',
        ]);

        $response->assertRedirect('/ethics/education-violations?staff_no=T1001');

        $this->assertDatabaseHas('ethics_education_violations', [
            'staff_no' => 'T1001',
            'staff_name' => '教育测试老师',
            'recorder_user_id' => $leader->id,
            'violation_type' => 10,
        ]);
    }

    public function test_dashboard_political_deduction_is_calculated_per_staff_and_per_year(): void
    {
        $department = Department::factory()->create();

        $leader = User::factory()->withoutTwoFactor()->create([
            'role' => 'leader',
            'department_id' => $department->id,
        ]);

        $advisorA = User::factory()->withoutTwoFactor()->create([
            'role' => 'advisor',
            'department_id' => $department->id,
        ]);

        $advisorB = User::factory()->withoutTwoFactor()->create([
            'role' => 'advisor',
            'department_id' => $department->id,
        ]);

        $profileA = EthicsProfile::factory()->create([
            'user_id' => $advisorA->id,
            'department_id' => $department->id,
            'staff_no' => 'S0001',
        ]);

        $profileB = EthicsProfile::factory()->create([
            'user_id' => $advisorB->id,
            'department_id' => $department->id,
            'staff_no' => 'S0002',
        ]);

        EthicsPoliticalViolation::factory()->create([
            'ethics_profile_id' => $profileA->id,
            'violator_user_id' => $advisorA->id,
            'recorder_user_id' => $leader->id,
            'staff_no' => 'S0001',
            'staff_name' => '老师A',
            'staff_unit_name' => '测试学院',
            'violation_at' => '2026-04-01 10:00:00',
            'deduction_points' => 3,
        ]);

        EthicsPoliticalViolation::factory()->create([
            'ethics_profile_id' => $profileA->id,
            'violator_user_id' => $advisorA->id,
            'recorder_user_id' => $leader->id,
            'staff_no' => 'S0001',
            'staff_name' => '老师A',
            'staff_unit_name' => '测试学院',
            'violation_at' => '2025-12-20 10:00:00',
            'deduction_points' => 4,
        ]);

        EthicsPoliticalViolation::factory()->create([
            'ethics_profile_id' => $profileB->id,
            'violator_user_id' => $advisorB->id,
            'recorder_user_id' => $leader->id,
            'staff_no' => 'S0002',
            'staff_name' => '老师B',
            'staff_unit_name' => '测试学院',
            'violation_at' => '2026-04-01 10:00:00',
            'deduction_points' => 6,
        ]);

        $response = $this->actingAs($leader)->get('/ethics/dashboard?staff_no=S0001&year=2026');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Ethics/Dashboard')
            ->where('stats.politicalSelectedDeductionTotal', 3)
            ->where('stats.politicalSelectedRemainingScore', 22)
        );
    }

    public function test_dashboard_education_deduction_is_calculated_per_staff_and_per_year(): void
    {
        $department = Department::factory()->create();

        $leader = User::factory()->withoutTwoFactor()->create([
            'role' => 'leader',
            'department_id' => $department->id,
        ]);

        $advisorA = User::factory()->withoutTwoFactor()->create([
            'role' => 'advisor',
            'department_id' => $department->id,
        ]);

        $advisorB = User::factory()->withoutTwoFactor()->create([
            'role' => 'advisor',
            'department_id' => $department->id,
        ]);

        $profileA = EthicsProfile::factory()->create([
            'user_id' => $advisorA->id,
            'department_id' => $department->id,
            'staff_no' => 'E0001',
        ]);

        $profileB = EthicsProfile::factory()->create([
            'user_id' => $advisorB->id,
            'department_id' => $department->id,
            'staff_no' => 'E0002',
        ]);

        EthicsEducationViolation::factory()->create([
            'ethics_profile_id' => $profileA->id,
            'violator_user_id' => $advisorA->id,
            'recorder_user_id' => $leader->id,
            'staff_no' => 'E0001',
            'staff_name' => '教师甲',
            'staff_unit_name' => '测试学院',
            'violation_at' => '2026-04-02 10:00:00',
            'deduction_points' => 5,
        ]);

        EthicsEducationViolation::factory()->create([
            'ethics_profile_id' => $profileA->id,
            'violator_user_id' => $advisorA->id,
            'recorder_user_id' => $leader->id,
            'staff_no' => 'E0001',
            'staff_name' => '教师甲',
            'staff_unit_name' => '测试学院',
            'violation_at' => '2025-10-02 10:00:00',
            'deduction_points' => 8,
        ]);

        EthicsEducationViolation::factory()->create([
            'ethics_profile_id' => $profileB->id,
            'violator_user_id' => $advisorB->id,
            'recorder_user_id' => $leader->id,
            'staff_no' => 'E0002',
            'staff_name' => '教师乙',
            'staff_unit_name' => '测试学院',
            'violation_at' => '2026-04-02 10:00:00',
            'deduction_points' => 7,
        ]);

        $response = $this->actingAs($leader)->get('/ethics/dashboard?staff_no=E0001&year=2026');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Ethics/Dashboard')
            ->where('stats.educationSelectedDeductionTotal', 5)
            ->where('stats.educationSelectedRemainingScore', 20)
        );
    }

    public function test_profile_detail_shows_annual_political_and_education_scores(): void
    {
        $department = Department::factory()->create();

        $advisor = User::factory()->withoutTwoFactor()->create([
            'role' => 'advisor',
            'department_id' => $department->id,
        ]);

        $otherAdvisor = User::factory()->withoutTwoFactor()->create([
            'role' => 'advisor',
            'department_id' => $department->id,
        ]);

        $profile = EthicsProfile::factory()->create([
            'user_id' => $advisor->id,
            'department_id' => $department->id,
            'staff_no' => 'P0001',
        ]);

        $otherProfile = EthicsProfile::factory()->create([
            'user_id' => $otherAdvisor->id,
            'department_id' => $department->id,
            'staff_no' => 'P0002',
        ]);

        EthicsPoliticalViolation::factory()->create([
            'ethics_profile_id' => $profile->id,
            'violator_user_id' => $advisor->id,
            'recorder_user_id' => $advisor->id,
            'staff_no' => 'P0001',
            'staff_name' => '老师甲',
            'staff_unit_name' => '测试学院',
            'violation_at' => '2026-04-10 10:00:00',
            'deduction_points' => 4,
        ]);

        EthicsPoliticalViolation::factory()->create([
            'ethics_profile_id' => $profile->id,
            'violator_user_id' => $advisor->id,
            'recorder_user_id' => $advisor->id,
            'staff_no' => 'P0001',
            'staff_name' => '老师甲',
            'staff_unit_name' => '测试学院',
            'violation_at' => '2025-04-10 10:00:00',
            'deduction_points' => 6,
        ]);

        EthicsEducationViolation::factory()->create([
            'ethics_profile_id' => $profile->id,
            'violator_user_id' => $advisor->id,
            'recorder_user_id' => $advisor->id,
            'staff_no' => 'P0001',
            'staff_name' => '老师甲',
            'staff_unit_name' => '测试学院',
            'violation_at' => '2026-04-12 10:00:00',
            'deduction_points' => 5,
        ]);

        EthicsEducationViolation::factory()->create([
            'ethics_profile_id' => $otherProfile->id,
            'violator_user_id' => $otherAdvisor->id,
            'recorder_user_id' => $otherAdvisor->id,
            'staff_no' => 'P0002',
            'staff_name' => '老师乙',
            'staff_unit_name' => '测试学院',
            'violation_at' => '2026-04-12 10:00:00',
            'deduction_points' => 9,
        ]);

        $response = $this->actingAs($advisor)->get("/ethics/profiles/{$advisor->id}?year=2026");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Ethics/Profiles/Show')
            ->where('summary.year', 2026)
            ->where('summary.politicalAnnualDeductionTotal', 4)
            ->where('summary.politicalAnnualRemainingScore', 21)
            ->where('summary.educationAnnualDeductionTotal', 5)
            ->where('summary.educationAnnualRemainingScore', 20)
        );
    }
}

