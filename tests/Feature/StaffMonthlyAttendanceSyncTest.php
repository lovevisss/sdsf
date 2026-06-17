<?php

namespace Tests\Feature;

use App\Models\EthicsProfile;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StaffMonthlyAttendanceSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_late_and_early_monthly_attendance_as_two_point_discipline_violation(): void
    {
        $this->prepareStaffAttendanceDb();
        $recorder = User::factory()->withoutTwoFactor()->create();

        DB::connection('staff_db')->table('t_ejxyybt_jzgydkqtjb')->insert([
            [
                'stat_month' => '2026-03',
                'person_user_no' => 'A001',
                'person_name' => 'Teacher A',
                'dept_name' => 'Computer School',
                'late_count' => 3,
                'early_count' => 2,
                'no_access_count' => 0,
            ],
            [
                'stat_month' => '2026-03',
                'person_user_no' => 'A002',
                'person_name' => 'Teacher B',
                'dept_name' => 'Computer School',
                'late_count' => 4,
                'early_count' => 0,
                'no_access_count' => 99,
            ],
        ]);

        Artisan::call('ethics:sync-staff-monthly-attendance', [
            'statMonth' => '2026-03',
            '--recorder-user-id' => $recorder->id,
        ]);

        $this->assertDatabaseHas('ethics_discipline_violations', [
            'staff_no' => 'A001',
            'staff_name' => 'Teacher A',
            'staff_unit_name' => 'Computer School',
            'violation_type' => 35,
            'deduction_points' => 2,
            'data_source' => 'attendance_monthly_sync',
            'deduction_basis' => '月度迟到早退合计达到5次及以上',
            'violation_at' => '2026-03-01 00:00:00',
        ]);

        $this->assertDatabaseMissing('ethics_discipline_violations', [
            'staff_no' => 'A002',
            'data_source' => 'attendance_monthly_sync',
        ]);
    }

    public function test_it_does_not_duplicate_same_staff_same_month_records(): void
    {
        $this->prepareStaffAttendanceDb();
        $recorder = User::factory()->withoutTwoFactor()->create();

        DB::connection('staff_db')->table('t_ejxyybt_jzgydkqtjb')->insert([
            'stat_month' => '2026-04',
            'person_user_no' => 'D001',
            'person_name' => 'Teacher D',
            'dept_name' => 'Design School',
            'late_count' => 5,
            'early_count' => 0,
            'no_access_count' => 0,
        ]);

        Artisan::call('ethics:sync-staff-monthly-attendance', [
            'statMonth' => '2026-04',
            '--recorder-user-id' => $recorder->id,
        ]);
        Artisan::call('ethics:sync-staff-monthly-attendance', [
            'statMonth' => '2026-04',
            '--recorder-user-id' => $recorder->id,
        ]);

        $this->assertSame(
            1,
            DB::table('ethics_discipline_violations')
                ->where('staff_no', 'D001')
                ->where('violation_type', 35)
                ->where('data_source', 'attendance_monthly_sync')
                ->whereDate('violation_at', '2026-04-01')
                ->count(),
        );
    }

    public function test_it_creates_scoring_warning_after_synced_attendance_deductions_reach_warning_threshold(): void
    {
        $this->prepareStaffAttendanceDb();
        $profileUser = User::factory()->withoutTwoFactor()->create();
        $recorder = User::factory()->withoutTwoFactor()->create();

        EthicsProfile::factory()->create([
            'user_id' => $profileUser->id,
            'staff_no' => 'W001',
        ]);

        foreach (['2026-01', '2026-02', '2026-03'] as $month) {
            DB::connection('staff_db')->table('t_ejxyybt_jzgydkqtjb')->insert([
                'stat_month' => $month,
                'person_user_no' => 'W001',
                'person_name' => 'Warning Teacher',
                'dept_name' => 'Warning School',
                'late_count' => 4,
                'early_count' => 1,
                'no_access_count' => 0,
            ]);
        }

        Artisan::call('ethics:sync-staff-monthly-attendance', [
            '--year' => 2026,
            '--recorder-user-id' => $recorder->id,
        ]);

        $this->assertDatabaseHas('ethics_warnings', [
            'ethics_profile_id' => EthicsProfile::query()->where('staff_no', 'W001')->value('id'),
            'assignee_id' => $profileUser->id,
            'warning_level' => 'yellow',
            'source_type' => 'scoring',
            'status' => 'open',
        ]);
    }

    public function test_leader_can_trigger_attendance_sync_from_discipline_page(): void
    {
        $this->prepareStaffAttendanceDb();
        $leader = User::factory()->withoutTwoFactor()->create([
            'role' => 'leader',
        ]);

        DB::connection('staff_db')->table('t_ejxyybt_jzgydkqtjb')->insert([
            'stat_month' => '2026-05',
            'person_user_no' => 'L001',
            'person_name' => 'Leader Triggered',
            'dept_name' => 'Leader School',
            'late_count' => 2,
            'early_count' => 3,
            'no_access_count' => 0,
        ]);

        $response = $this->actingAs($leader)->post('/ethics/discipline-violations/sync-attendance', [
            'year' => 2026,
        ]);

        $response->assertRedirect('/ethics/discipline-violations?year=2026');
        $response->assertSessionHas('attendanceSync.inserted', 1);
        $this->assertDatabaseHas('ethics_discipline_violations', [
            'staff_no' => 'L001',
            'data_source' => 'attendance_monthly_sync',
        ]);
    }

    public function test_advisor_cannot_trigger_attendance_sync_from_discipline_page(): void
    {
        $this->prepareStaffAttendanceDb();
        $advisor = User::factory()->withoutTwoFactor()->create([
            'role' => 'advisor',
        ]);

        $this->actingAs($advisor)
            ->post('/ethics/discipline-violations/sync-attendance', ['year' => 2026])
            ->assertForbidden();
    }

    private function prepareStaffAttendanceDb(): void
    {
        $sqlitePath = database_path('staff_attendance_sync_test.sqlite');

        if (! file_exists($sqlitePath)) {
            touch($sqlitePath);
        }

        config()->set('database.connections.staff_db', [
            'driver' => 'sqlite',
            'database' => $sqlitePath,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        DB::purge('staff_db');

        Schema::connection('staff_db')->dropIfExists('t_ejxyybt_jzgydkqtjb');
        Schema::connection('staff_db')->create('t_ejxyybt_jzgydkqtjb', function (Blueprint $table): void {
            $table->float('id')->nullable();
            $table->string('stat_month', 7)->nullable();
            $table->string('person_user_no', 50)->nullable();
            $table->string('person_name', 100)->nullable();
            $table->float('dept_id')->nullable();
            $table->string('dept_name', 100)->nullable();
            $table->float('late_count')->nullable();
            $table->float('early_count')->nullable();
            $table->float('no_access_count')->nullable();
            $table->float('total_workdays')->nullable();
            $table->float('actual_workdays')->nullable();
            $table->string('escalation_status', 20)->nullable();
            $table->float('escalation_sms_sent')->nullable();
            $table->dateTime('escalation_sms_time')->nullable();
            $table->dateTime('create_time')->nullable();
            $table->dateTime('update_time')->nullable();
        });
    }
}
