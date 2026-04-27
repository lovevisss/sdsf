<?php

namespace App\Actions\Ethics;

use App\Models\EthicsEducationViolation;
use App\Models\EthicsPoliticalViolation;
use App\Models\EthicsAcademicViolation;
use App\Models\EthicsProfessionalViolation;
use App\Models\EthicsProfile;
use App\Models\EthicsWarning;

class UpsertAnnualDeductionWarning
{
    private const AUTO_REASON_PREFIX = 'AUTO_YEARLY_DEDUCTION';

    public function handle(string $staffNo, int $year): void
    {
        $normalizedStaffNo = trim($staffNo);

        if ($normalizedStaffNo === '' || $year < 1) {
            return;
        }

        $profile = EthicsProfile::query()->where('staff_no', $normalizedStaffNo)->first();

        if ($profile === null) {
            return;
        }

        $annualDeductionTotal = $this->annualDeductionTotal($normalizedStaffNo, $year);
        $targetLevel = $this->targetWarningLevel($annualDeductionTotal);

        if ($targetLevel === null) {
            return;
        }

        $existing = EthicsWarning::query()
            ->where('ethics_profile_id', $profile->id)
            ->where('source_type', 'teaching')
            ->where('reason', 'like', self::AUTO_REASON_PREFIX.'|'.$year.'|%')
            ->latest('id')
            ->first();

        if ($existing === null) {
            EthicsWarning::query()->create([
                'ethics_profile_id' => $profile->id,
                'assignee_id' => $profile->user_id,
                'warning_level' => $targetLevel,
                'source_type' => 'teaching',
                'reason' => $this->buildReason($year, $annualDeductionTotal, $targetLevel),
                'status' => 'open',
                'detected_at' => now(),
                'closed_at' => null,
            ]);

            return;
        }

        if ($this->isUpgrade($existing->warning_level, $targetLevel) || $existing->status === 'closed') {
            $existing->update([
                'warning_level' => $this->isUpgrade($existing->warning_level, $targetLevel) ? $targetLevel : $existing->warning_level,
                'reason' => $this->buildReason($year, $annualDeductionTotal, $targetLevel),
                'status' => 'open',
                'detected_at' => now(),
                'closed_at' => null,
            ]);
        }
    }

    private function annualDeductionTotal(string $staffNo, int $year): float
    {
        $political = (float) EthicsPoliticalViolation::query()
            ->where('staff_no', $staffNo)
            ->whereYear('violation_at', $year)
            ->sum('deduction_points');

        $education = (float) EthicsEducationViolation::query()
            ->where('staff_no', $staffNo)
            ->whereYear('violation_at', $year)
            ->sum('deduction_points');

        $academic = (float) EthicsAcademicViolation::query()
            ->where('staff_no', $staffNo)
            ->whereYear('violation_at', $year)
            ->sum('deduction_points');

        $professional = (float) EthicsProfessionalViolation::query()
            ->where('staff_no', $staffNo)
            ->whereYear('violation_at', $year)
            ->sum('deduction_points');

        return round($political + $education + $academic + $professional, 2);
    }

    private function targetWarningLevel(float $annualDeductionTotal): ?string
    {
        if ($annualDeductionTotal >= 10) {
            return 'red';
        }

        if ($annualDeductionTotal >= 5) {
            return 'yellow';
        }

        return null;
    }

    private function isUpgrade(string $currentLevel, string $targetLevel): bool
    {
        return $this->rank($targetLevel) > $this->rank($currentLevel);
    }

    private function rank(string $level): int
    {
        return match ($level) {
            'yellow' => 1,
            'orange' => 2,
            'red' => 3,
            default => 0,
        };
    }

    private function buildReason(int $year, float $annualDeductionTotal, string $level): string
    {
        $label = $level === 'red' ? '红色' : '黄色';

        return sprintf(
            '%s|%d|年度累计扣分%.2f分，达到%s预警阈值。',
            self::AUTO_REASON_PREFIX,
            $year,
            $annualDeductionTotal,
            $label,
        );
    }
}

