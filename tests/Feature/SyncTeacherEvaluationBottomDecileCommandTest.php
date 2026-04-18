<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SyncTeacherEvaluationBottomDecileCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_academic_year_as_violation_year_instead_of_tstamp(): void
    {
        $this->prepareStaffDbForTeacherEvaluations();

        DB::connection('staff_db')->table('t_ejxyybt_jspjxxb')->insert([
            [
                'WYBS' => '1',
                'XN' => '2019-2020',
                'XQ' => '1',
                'KKBM' => 'D001',
                'JSBH' => 'T0001',
                'JSXM' => 'Teacher One',
                'KCMC' => 'Course A',
                'PJCJ' => 60,
                'KCDM' => 'C001',
                'TSTAMP' => '2020-06-01 09:00:00',
            ],
            [
                'WYBS' => '2',
                'XN' => '2019-2020',
                'XQ' => '1',
                'KKBM' => 'D001',
                'JSBH' => 'T0002',
                'JSXM' => 'Teacher Two',
                'KCMC' => 'Course B',
                'PJCJ' => 95,
                'KCDM' => 'C002',
                'TSTAMP' => '2020-06-01 09:00:00',
            ],
        ]);

        $recorder = User::factory()->withoutTwoFactor()->create();

        Artisan::call('ethics:sync-teacher-evaluation-bottom-decile', [
            'academicYear' => '2019-2020',
            '--recorder-user-id' => $recorder->id,
        ]);

        $this->assertDatabaseHas('ethics_education_violations', [
            'staff_no' => 'T0001',
            'violation_type' => 10,
            'notes' => '教师评价后10%',
            'violation_at' => '2019-01-01 00:00:00',
        ]);
    }

    public function test_it_does_not_duplicate_same_teacher_same_academic_year_records(): void
    {
        $this->prepareStaffDbForTeacherEvaluations();

        DB::connection('staff_db')->table('t_ejxyybt_jspjxxb')->insert([
            [
                'WYBS' => '3',
                'XN' => '2019-2020',
                'XQ' => '1',
                'KKBM' => 'D002',
                'JSBH' => 'T1001',
                'JSXM' => 'Teacher A',
                'KCMC' => 'Course X',
                'PJCJ' => 70,
                'KCDM' => 'CX',
                'TSTAMP' => '2020-07-01 09:00:00',
            ],
            [
                'WYBS' => '4',
                'XN' => '2019-2020',
                'XQ' => '1',
                'KKBM' => 'D002',
                'JSBH' => 'T1002',
                'JSXM' => 'Teacher B',
                'KCMC' => 'Course Y',
                'PJCJ' => 98,
                'KCDM' => 'CY',
                'TSTAMP' => '2020-07-01 09:00:00',
            ],
        ]);

        $recorder = User::factory()->withoutTwoFactor()->create();

        Artisan::call('ethics:sync-teacher-evaluation-bottom-decile', [
            'academicYear' => '2019-2020',
            '--recorder-user-id' => $recorder->id,
        ]);

        Artisan::call('ethics:sync-teacher-evaluation-bottom-decile', [
            'academicYear' => '2019-2020',
            '--recorder-user-id' => $recorder->id,
        ]);

        $this->assertSame(
            1,
            DB::table('ethics_education_violations')
                ->where('staff_no', 'T1001')
                ->where('violation_type', 10)
                ->where('notes', '教师评价后10%')
                ->whereYear('violation_at', 2019)
                ->count(),
        );
    }

    private function prepareStaffDbForTeacherEvaluations(): void
    {
        $sqlitePath = database_path('staff_eval_sync_test.sqlite');

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

        Schema::connection('staff_db')->dropIfExists('t_ejxyybt_jspjxxb');
        Schema::connection('staff_db')->create('t_ejxyybt_jspjxxb', function (Blueprint $table): void {
            $table->string('WYBS')->primary();
            $table->string('XN')->nullable();
            $table->string('XQ')->nullable();
            $table->string('KKBM')->nullable();
            $table->string('JSBH')->nullable();
            $table->string('JSXM')->nullable();
            $table->string('KCMC')->nullable();
            $table->decimal('PJCJ', 8, 2)->nullable();
            $table->string('KCDM')->nullable();
            $table->dateTime('TSTAMP')->nullable();
        });
    }
}

