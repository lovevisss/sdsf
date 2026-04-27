<?php

namespace App\Console\Commands;

use App\Actions\Ethics\TeacherEvaluationBottomDecileSelector;
use App\Actions\Ethics\UpsertAnnualDeductionWarning;
use App\Models\EthicsEducationViolation;
use App\Models\EthicsProfile;
use App\Models\Staff;
use App\Models\TeacherEvaluation;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SyncTeacherEvaluationBottomDecile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ethics:sync-teacher-evaluation-bottom-decile
                            {academicYear? : Academic year in XN format, e.g. 2025-2026}
                            {--recorder-user-id= : Local user id used as recorder}
                            {--dry-run : Preview only, do not write records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync yearly bottom 10% teacher evaluations into ethics education violations.';

    public function __construct(
        private readonly TeacherEvaluationBottomDecileSelector $selector,
        private readonly UpsertAnnualDeductionWarning $upsertAnnualDeductionWarning,
    )
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $recorderUserId = $this->resolveRecorderUserId();
        if ($recorderUserId === null) {
            return self::FAILURE;
        }

        $academicYear = $this->argument('academicYear');

        $query = TeacherEvaluation::query()
            ->selectRaw('XN as academic_year, JSBH as teacher_no, MAX(JSXM) as teacher_name, AVG(PJCJ) as average_score, MAX(TSTAMP) as evaluated_at')
            ->whereNotNull('XN')
            ->whereNotNull('JSBH')
            ->whereNotNull('PJCJ')
            ->groupBy('XN', 'JSBH');

        if (is_string($academicYear) && $academicYear !== '') {
            $query->where('XN', $academicYear);
        }

        try {
            /** @var Collection<int, object{academic_year: string, teacher_no: string, teacher_name: string|null, average_score: float|int|string, evaluated_at: string|null}> $aggregated */
            $aggregated = $query->get();
        } catch (\Throwable $exception) {
            $this->error('Failed to read teacher evaluations: '.$exception->getMessage());

            return self::FAILURE;
        }

        if ($aggregated->isEmpty()) {
            $this->warn('No teacher evaluation data found for sync.');

            return self::SUCCESS;
        }

        $inserted = 0;
        $skipped = 0;

        foreach ($aggregated->groupBy('academic_year') as $groupAcademicYear => $yearRows) {
            $normalizedRows = $yearRows->map(function (object $row) use ($groupAcademicYear, $academicYear): array {
                $academicYearSource = is_string($academicYear) && $academicYear !== ''
                    ? $academicYear
                    : $this->extractAcademicYearFromRow($row, (string) $groupAcademicYear);

                $evaluationYear = $this->resolveEvaluationYear(
                    $academicYearSource,
                    (string) ($row->evaluated_at ?? ''),
                );

                return [
                    'academic_year' => $academicYearSource,
                    'teacher_no' => trim((string) $row->teacher_no),
                    'teacher_name' => trim((string) ($row->teacher_name ?? '')),
                    'average_score' => (float) $row->average_score,
                    'evaluation_year' => $evaluationYear,
                    'evaluated_at' => sprintf('%04d-01-01 00:00:00', $evaluationYear),
                ];
            })->filter(fn (array $row): bool => $row['teacher_no'] !== '')->values();

            $bottomRows = $this->selector->select(
                $normalizedRows->map(fn (array $row): array => [
                    'academic_year' => $row['academic_year'],
                    'teacher_no' => $row['teacher_no'],
                    'teacher_name' => $row['teacher_name'],
                    'average_score' => $row['average_score'],
                    'evaluation_year' => $row['evaluation_year'],
                    'evaluated_at' => $row['evaluated_at'],
                ]),
            );

            foreach ($bottomRows as $row) {
                $wasInserted = $this->syncViolationRecord(
                    academicYear: $row['academic_year'] ?? (string) $normalizedRows->first()['academic_year'],
                    teacherNo: $row['teacher_no'],
                    teacherName: $row['teacher_name'],
                    averageScore: (float) $row['average_score'],
                    evaluationYear: (int) ($row['evaluation_year'] ?? now()->year),
                    recorderUserId: $recorderUserId,
                    dryRun: (bool) $this->option('dry-run'),
                );

                if ($wasInserted) {
                    $inserted++;
                } else {
                    $skipped++;
                }
            }
        }

        $this->info("Sync completed. inserted={$inserted}, skipped={$skipped}");

        return self::SUCCESS;
    }

    private function resolveRecorderUserId(): ?int
    {
        $optionValue = $this->option('recorder-user-id');
        $configuredValue = config('ethics.auto_recorder_user_id');
        $raw = $optionValue !== null && $optionValue !== '' ? $optionValue : $configuredValue;

        if ($raw === null || $raw === '') {
            $this->error('Missing recorder user id. Configure ETHICS_AUTO_RECORDER_USER_ID or use --recorder-user-id.');

            return null;
        }

        $userId = (int) $raw;

        if ($userId < 1 || ! User::query()->whereKey($userId)->exists()) {
            $this->error("Recorder user id {$userId} does not exist.");

            return null;
        }

        return $userId;
    }

    private function syncViolationRecord(
        string $academicYear,
        string $teacherNo,
        string $teacherName,
        float $averageScore,
        int $evaluationYear,
        int $recorderUserId,
        bool $dryRun,
    ): bool {
        $violationYear = $evaluationYear > 0 ? $evaluationYear : now()->year;
        $violationAt = sprintf('%04d-01-01 00:00:00', $violationYear);

        $exists = EthicsEducationViolation::query()
            ->where('staff_no', $teacherNo)
            ->where('violation_type', 10)
            ->where('notes', '教师评价后10%')
            ->whereYear('violation_at', $violationYear)
            ->exists();

        if ($exists) {
            return false;
        }

        $staff = null;

        try {
            $staff = Staff::query()->find($teacherNo);
        } catch (\Throwable) {
            // Keep sync running even when the external staff source is unavailable.
        }
        $staffName = $staff?->name ?? ($teacherName !== '' ? $teacherName : $teacherNo);
        $staffUnitName = $staff?->unit_name;

        $profile = EthicsProfile::query()->where('staff_no', $teacherNo)->first();

        if ($dryRun) {
            $this->line("[dry-run] {$academicYear} {$teacherNo} {$staffName} avg={$averageScore}");

            return true;
        }

        DB::transaction(function () use ($profile, $teacherNo, $staffName, $staffUnitName, $violationAt, $recorderUserId): void {
            EthicsEducationViolation::query()->create([
                'ethics_profile_id' => $profile?->id,
                'violator_user_id' => $profile?->user_id,
                'recorder_user_id' => $recorderUserId,
                'staff_no' => $teacherNo,
                'staff_name' => $staffName,
                'staff_unit_name' => $staffUnitName,
                'violation_type' => 10,
                'violation_at' => $violationAt,
                'deduction_points' => 2,
                'notes' => '教师评价后10%',
            ]);
        });

        $this->upsertAnnualDeductionWarning->handle($teacherNo, $violationYear);

        return true;
    }

    private function resolveEvaluationYear(string $academicYear, string $evaluatedAt): int
    {
        if (preg_match('/(\d{4})/', $academicYear, $matches) === 1) {
            return (int) $matches[1];
        }

        $timestamp = strtotime($evaluatedAt);

        if ($timestamp !== false) {
            return (int) date('Y', $timestamp);
        }

        return now()->year;
    }

    private function extractAcademicYearFromRow(object $row, string $fallback): string
    {
        $rowArray = (array) $row;

        $candidates = [
            $rowArray['academic_year'] ?? null,
            $rowArray['XN'] ?? null,
            $rowArray['xn'] ?? null,
            $fallback,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return $fallback;
    }
}
