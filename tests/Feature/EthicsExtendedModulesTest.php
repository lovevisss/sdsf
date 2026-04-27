<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\EthicsAcademicViolation;
use App\Models\EthicsProfessionalViolation;
use App\Models\EthicsProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EthicsExtendedModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_can_store_academic_violation_record(): void
    {
        $department = Department::factory()->create();

        $leader = User::factory()->withoutTwoFactor()->create([
            'role' => 'leader',
            'department_id' => $department->id,
        ]);

        $response = $this->actingAs($leader)->post('/ethics/academic-violations', [
            'staff_no' => 'A1001',
            'staff_name' => '学术测试老师',
            'staff_unit_name' => '测试学院',
            'violation_type' => 18,
            'violation_at' => now()->toDateTimeString(),
            'deduction_points' => 3,
            'notes' => '测试学术科研道德违规登记。',
        ]);

        $response->assertRedirect('/ethics/academic-violations?staff_no=A1001');

        $this->assertDatabaseHas('ethics_academic_violations', [
            'staff_no' => 'A1001',
            'staff_name' => '学术测试老师',
            'recorder_user_id' => $leader->id,
            'violation_type' => 18,
        ]);
    }

    public function test_leader_can_store_professional_violation_record(): void
    {
        $department = Department::factory()->create();

        $leader = User::factory()->withoutTwoFactor()->create([
            'role' => 'leader',
            'department_id' => $department->id,
        ]);

        $response = $this->actingAs($leader)->post('/ethics/professional-violations', [
            'staff_no' => 'P2001',
            'staff_name' => '师表测试老师',
            'staff_unit_name' => '测试学院',
            'violation_type' => 24,
            'violation_at' => now()->toDateTimeString(),
            'deduction_points' => 2,
            'notes' => '测试为人师表违规登记。',
        ]);

        $response->assertRedirect('/ethics/professional-violations?staff_no=P2001');

        $this->assertDatabaseHas('ethics_professional_violations', [
            'staff_no' => 'P2001',
            'staff_name' => '师表测试老师',
            'recorder_user_id' => $leader->id,
            'violation_type' => 24,
        ]);
    }

    public function test_profile_detail_calculates_academic_and_professional_scores(): void
    {
        $department = Department::factory()->create();

        $advisor = User::factory()->withoutTwoFactor()->create([
            'role' => 'advisor',
            'department_id' => $department->id,
        ]);

        $profile = EthicsProfile::factory()->create([
            'user_id' => $advisor->id,
            'department_id' => $department->id,
            'staff_no' => 'M3001',
        ]);

        EthicsAcademicViolation::factory()->create([
            'ethics_profile_id' => $profile->id,
            'violator_user_id' => $advisor->id,
            'recorder_user_id' => $advisor->id,
            'staff_no' => 'M3001',
            'staff_name' => '老师甲',
            'staff_unit_name' => '测试学院',
            'violation_at' => '2026-04-12 10:00:00',
            'deduction_points' => 4,
        ]);

        EthicsProfessionalViolation::factory()->create([
            'ethics_profile_id' => $profile->id,
            'violator_user_id' => $advisor->id,
            'recorder_user_id' => $advisor->id,
            'staff_no' => 'M3001',
            'staff_name' => '老师甲',
            'staff_unit_name' => '测试学院',
            'violation_at' => '2026-04-15 10:00:00',
            'deduction_points' => 3,
        ]);

        $this->actingAs($advisor)
            ->get('/ethics/profiles/staff/M3001?year=2026')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ethics/Profiles/Show')
                ->where('summary.academicAnnualDeductionTotal', 4)
                ->where('summary.professionalAnnualDeductionTotal', 3)
                ->where('summary.modules.academic', 21)
                ->where('summary.modules.professional', 22)
            );
    }
}

