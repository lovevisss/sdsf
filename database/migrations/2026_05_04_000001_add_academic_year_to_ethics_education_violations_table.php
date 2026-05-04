<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ethics_education_violations', function (Blueprint $table): void {
            $table->string('academic_year')->nullable()->after('staff_unit_name');
            $table->index(['academic_year', 'staff_unit_name']);
        });

        DB::table('ethics_education_violations')
            ->orderBy('id')
            ->select(['id', 'violation_at', 'notes'])
            ->chunkById(200, function ($records): void {
                foreach ($records as $record) {
                    $timestamp = strtotime((string) $record->violation_at);

                    if ($timestamp === false) {
                        continue;
                    }

                    DB::table('ethics_education_violations')
                        ->where('id', $record->id)
                        ->update(['academic_year' => $this->academicYearForBackfill($timestamp, (string) $record->notes)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('ethics_education_violations', function (Blueprint $table): void {
            $table->dropIndex(['academic_year', 'staff_unit_name']);
            $table->dropColumn('academic_year');
        });
    }

    private function academicYearForBackfill(int $timestamp, string $notes): string
    {
        $year = (int) date('Y', $timestamp);

        if ($notes === '教师评价后10%') {
            return $year.'-'.($year + 1);
        }

        $month = (int) date('n', $timestamp);
        $startYear = $month >= 9 ? $year : $year - 1;

        return $startYear.'-'.($startYear + 1);
    }
};
