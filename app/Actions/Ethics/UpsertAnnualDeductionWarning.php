<?php

namespace App\Actions\Ethics;

use App\Models\EthicsProfile;
use App\Models\EthicsWarning;
use App\Services\Ethics\EthicsScoreService;

class UpsertAnnualDeductionWarning
{
    private const AUTO_REASON_PREFIX = 'AUTO_YEARLY_DEDUCTION';

    public function __construct(private readonly EthicsScoreService $scoreService)
    {
    }

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

        $summary = $this->scoreService->summary($normalizedStaffNo, $year);
        $targetLevel = $summary['warningLevel'];

        if ($targetLevel === null) {
            return;
        }

        $existing = EthicsWarning::query()
            ->where('ethics_profile_id', $profile->id)
            ->where('source_type', 'scoring')
            ->where('reason', 'like', self::AUTO_REASON_PREFIX.'|'.$year.'|%')
            ->latest('id')
            ->first();

        if ($existing === null) {
            EthicsWarning::query()->create([
                'ethics_profile_id' => $profile->id,
                'assignee_id' => $profile->user_id,
                'warning_level' => $targetLevel,
                'source_type' => 'scoring',
                'reason' => $this->buildReason($year, $summary, $targetLevel),
                'status' => 'open',
                'detected_at' => now(),
                'closed_at' => null,
            ]);

            return;
        }

        if ($this->isUpgrade($existing->warning_level, $targetLevel) || $existing->status === 'closed') {
            $existing->update([
                'warning_level' => $this->isUpgrade($existing->warning_level, $targetLevel) ? $targetLevel : $existing->warning_level,
                'reason' => $this->buildReason($year, $summary, $targetLevel),
                'status' => 'open',
                'detected_at' => now(),
                'closed_at' => null,
            ]);
        }
    }

    private function isUpgrade(string $currentLevel, string $targetLevel): bool
    {
        return $this->rank($targetLevel) > $this->rank($currentLevel);
    }

    private function rank(string $level): int
    {
        return match ($level) {
            'blue' => 1,
            'yellow', 'orange' => 2,
            'red' => 3,
            default => 0,
        };
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function buildReason(int $year, array $summary, string $level): string
    {
        $label = ['blue' => '蓝色', 'yellow' => '黄色', 'red' => '红色'][$level] ?? $level;

        return sprintf(
            '%s|%d|年度总分%.2f，年度封顶扣分%.2f，达到%s预警阈值。',
            self::AUTO_REASON_PREFIX,
            $year,
            (float) $summary['totalScore'],
            (float) $summary['totalDeduction'],
            $label,
        );
    }
}
