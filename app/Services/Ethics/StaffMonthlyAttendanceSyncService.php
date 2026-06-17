<?php

namespace App\Services\Ethics;

use App\Actions\Ethics\UpsertAnnualDeductionWarning;
use App\Models\EthicsDisciplineViolation;
use App\Models\EthicsProfile;
use App\Models\StaffMonthlyAttendanceStatistic;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StaffMonthlyAttendanceSyncService
{
    public const DATA_SOURCE = 'attendance_monthly_sync';

    private const VIOLATION_TYPE = 35;

    private const DEDUCTION_POINTS = 2.0;

    private const THRESHOLD = 5.0;

    public function __construct(private readonly UpsertAnnualDeductionWarning $upsertAnnualDeductionWarning)
    {
    }

    /**
     * @return array{inserted: int, skipped: int, below_threshold: int, read: int}
     */
    public function sync(?string $statMonth, ?int $year, int $recorderUserId, bool $dryRun = false): array
    {
        $normalizedMonth = $this->normalizeStatMonth($statMonth);
        $targetYear = $normalizedMonth === null ? ($year ?: now()->year) : null;

        if ($targetYear !== null && ($targetYear < 1 || $targetYear > 9999)) {
            throw new InvalidArgumentException('Invalid attendance sync year.');
        }

        $query = StaffMonthlyAttendanceStatistic::query()
            ->select([
                'stat_month',
                'person_user_no',
                'person_name',
                'dept_name',
                'late_count',
                'early_count',
            ])
            ->whereNotNull('stat_month')
            ->whereNotNull('person_user_no');

        if ($normalizedMonth !== null) {
            $query->where('stat_month', $normalizedMonth);
        } elseif ($targetYear !== null) {
            $query->where('stat_month', 'like', sprintf('%04d-%%', $targetYear));
        }

        $rows = $query->get();

        $inserted = 0;
        $skipped = 0;
        $belowThreshold = 0;

        foreach ($rows as $row) {
            $rowMonth = $this->normalizeStatMonth((string) $row->stat_month);
            $staffNo = trim((string) $row->person_user_no);

            if ($rowMonth === null || $staffNo === '') {
                $skipped++;
                continue;
            }

            $lateCount = $this->numberValue($row->late_count);
            $earlyCount = $this->numberValue($row->early_count);
            $abnormalityCount = $lateCount + $earlyCount;

            if ($abnormalityCount < self::THRESHOLD) {
                $belowThreshold++;
                continue;
            }

            $violationAt = Carbon::createFromFormat('Y-m-d H:i:s', "{$rowMonth}-01 00:00:00");

            $exists = EthicsDisciplineViolation::query()
                ->where('staff_no', $staffNo)
                ->where('violation_type', self::VIOLATION_TYPE)
                ->where('data_source', self::DATA_SOURCE)
                ->whereDate('violation_at', $violationAt->toDateString())
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $inserted++;
                continue;
            }

            $staffName = trim((string) ($row->person_name ?? '')) ?: $staffNo;
            $profile = EthicsProfile::query()->where('staff_no', $staffNo)->first();

            DB::transaction(function () use ($profile, $staffNo, $staffName, $row, $rowMonth, $lateCount, $earlyCount, $abnormalityCount, $violationAt, $recorderUserId): void {
                EthicsDisciplineViolation::query()->create([
                    'ethics_profile_id' => $profile?->id,
                    'violator_user_id' => $profile?->user_id,
                    'recorder_user_id' => $recorderUserId,
                    'staff_no' => $staffNo,
                    'staff_name' => $staffName,
                    'staff_unit_name' => trim((string) ($row->dept_name ?? '')) ?: null,
                    'violation_type' => self::VIOLATION_TYPE,
                    'severity_level' => null,
                    'violation_at' => $violationAt,
                    'deduction_points' => self::DEDUCTION_POINTS,
                    'data_source' => self::DATA_SOURCE,
                    'handler_department' => '系统同步',
                    'handler_user_id' => $recorderUserId,
                    'deduction_basis' => '月度迟到早退合计达到5次及以上',
                    'evidence_attachments' => [],
                    'verification_status' => 'verified',
                    'verified_by' => $recorderUserId,
                    'verified_at' => now(),
                    'notes' => sprintf(
                        'stat_month=%s; late_count=%s; early_count=%s; total=%s',
                        $rowMonth,
                        $this->formatNumber($lateCount),
                        $this->formatNumber($earlyCount),
                        $this->formatNumber($abnormalityCount),
                    ),
                ]);
            });

            $this->upsertAnnualDeductionWarning->handle($staffNo, (int) $violationAt->format('Y'));
            $inserted++;
        }

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
            'below_threshold' => $belowThreshold,
            'read' => $rows->count(),
        ];
    }

    private function normalizeStatMonth(?string $statMonth): ?string
    {
        $value = trim((string) $statMonth);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) !== 1) {
            throw new InvalidArgumentException('Invalid attendance stat_month format.');
        }

        return $value;
    }

    private function numberValue(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
