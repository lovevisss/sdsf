<?php

namespace App\Console\Commands;

use App\Actions\Ethics\TeacherEvaluationBottomDecileSelector;
use App\Actions\Ethics\UpsertAnnualDeductionWarning;
use App\Models\EthicsEducationViolation;
use App\Models\EthicsProfile;
use App\Models\Staff;
use App\Models\TeachingPerformanceAssessment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SyncTeachingPerformanceBottomDecile extends Command
{
    private const SOURCE_NOTE = '教学业绩考核后10%';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ethics:sync-teaching-performance-bottom-decile
                            {academicYear? : Academic year in XN format, e.g. 2025-2026}
                            {--recorder-user-id= : Local user id used as recorder}
                            {--dry-run : Preview only, do not write records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync yearly bottom 10% teaching performance assessment scores into ethics education violations.';

    public function __construct(
        private readonly TeacherEvaluationBottomDecileSelector $selector,
        private readonly UpsertAnnualDeductionWarning $upsertAnnualDeductionWarning,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $recorderUserId = $this->resolveRecorderUserId();
        if ($recorderUserId === null) {
            return self::FAILURE;
        }

        $academicYear = $this->argument('academicYear');

        $query = TeachingPerformanceAssessment::query()
            ->select(['DID', 'FS', 'BZ', 'DWMC', 'KHDJ', 'XM', 'GH', 'XN'])
            ->whereNotNull('XN')
            ->whereNotNull('GH')
            ->whereNotNull('FS');

        if (is_string($academicYear) && $academicYear !== '') {
            $query->where('XN', $academicYear);
        }

        try {
            /** @var Collection<int, TeachingPerformanceAssessment> $rows */
            $rows = $query->get();
        } catch (\Throwable $exception) {
            $this->error('Failed to read teaching performance assessments: '.$exception->getMessage());

            return self::FAILURE;
        }

        $normalizedRows = $rows
            ->map(function (TeachingPerformanceAssessment $row): ?array {
                $score = $this->normalizeScore($row->FS);
                $academicYear = trim((string) $row->XN);
                $teacherNo = trim((string) $row->GH);

                if ($score === null || $academicYear === '' || $teacherNo === '') {
                    return null;
                }

                $evaluationYear = $this->resolveEvaluationYear($academicYear);

                return [
                    'academic_year' => $academicYear,
                    'teacher_no' => $teacherNo,
                    'teacher_name' => trim((string) ($row->XM ?? '')),
                    'staff_unit_name' => trim((string) ($row->DWMC ?? '')) ?: null,
                    'average_score' => $score,
                    'evaluation_year' => $evaluationYear,
                    'evaluated_at' => sprintf('%04d-01-01 00:00:00', $evaluationYear),
                ];
            })
            ->filter()
            ->values();

        if ($normalizedRows->isEmpty()) {
            $this->warn('No teaching performance assessment data found for sync.');

            return self::SUCCESS;
        }

        $inserted = 0;
        $skipped = 0;

        foreach ($normalizedRows->groupBy('academic_year') as $groupAcademicYear => $yearRows) {
            $bottomRows = $this->selector->select($yearRows);

            foreach ($bottomRows as $row) {
                $wasInserted = $this->syncViolationRecord(
                    academicYear: (string) ($row['academic_year'] ?? $groupAcademicYear),
                    teacherNo: $row['teacher_no'],
                    teacherName: $row['teacher_name'],
                    staffUnitName: $row['staff_unit_name'] ?? null,
                    score: (float) $row['average_score'],
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
        ?string $staffUnitName,
        float $score,
        int $evaluationYear,
        int $recorderUserId,
        bool $dryRun,
    ): bool {
        $violationYear = $evaluationYear > 0 ? $evaluationYear : now()->year;
        $violationAt = sprintf('%04d-01-01 00:00:00', $violationYear);

        $exists = EthicsEducationViolation::query()
            ->where('staff_no', $teacherNo)
            ->where('violation_type', 10)
            ->where('notes', self::SOURCE_NOTE)
            ->where(function ($query) use ($academicYear, $violationYear): void {
                $query->where('academic_year', $academicYear)
                    ->orWhere(function ($fallbackQuery) use ($violationYear): void {
                        $fallbackQuery->whereNull('academic_year')
                            ->whereYear('violation_at', $violationYear);
                    });
            })
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
        $resolvedUnitName = $staff?->unit_name ?? $staffUnitName;
        $profile = EthicsProfile::query()->where('staff_no', $teacherNo)->first();

        if ($dryRun) {
            $this->line("[dry-run] {$academicYear} {$teacherNo} {$staffName} score={$score}");

            return true;
        }

        DB::transaction(function () use ($profile, $academicYear, $teacherNo, $staffName, $resolvedUnitName, $violationAt, $recorderUserId): void {
            EthicsEducationViolation::query()->create([
                'ethics_profile_id' => $profile?->id,
                'violator_user_id' => $profile?->user_id,
                'recorder_user_id' => $recorderUserId,
                'staff_no' => $teacherNo,
                'staff_name' => $staffName,
                'staff_unit_name' => $resolvedUnitName,
                'academic_year' => $academicYear,
                'violation_type' => 10,
                'violation_at' => $violationAt,
                'deduction_points' => 5,
                'notes' => self::SOURCE_NOTE,
            ]);
        });

        $this->upsertAnnualDeductionWarning->handle($teacherNo, $violationYear);

        return true;
    }

    private function normalizeScore(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = str_replace(',', '', trim($value));

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function resolveEvaluationYear(string $academicYear): int
    {
        if (preg_match('/(\d{4})/', $academicYear, $matches) === 1) {
            return (int) $matches[1];
        }

        return now()->year;
    }
}
