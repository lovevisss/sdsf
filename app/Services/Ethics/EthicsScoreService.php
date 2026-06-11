<?php

namespace App\Services\Ethics;

use App\Models\EthicsAcademicViolation;
use App\Models\EthicsDisciplineViolation;
use App\Models\EthicsEducationViolation;
use App\Models\EthicsPoliticalViolation;
use App\Models\EthicsProfessionalViolation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EthicsScoreService
{
    public const DIMENSION_MAX = 20.0;

    public const TOTAL_MAX = 100.0;

    public const DIMENSIONS = [
        'political' => '政治素养',
        'education' => '教育教学',
        'academic' => '学术诚信',
        'professional' => '为人师表',
        'discipline' => '工作纪律',
    ];

    public const SEVERITY_DEDUCTIONS = [
        'A' => 5.0,
        'B' => 10.0,
        'C' => 20.0,
    ];

    /**
     * @return array<string, float>
     */
    public function deductionTotals(string $staffNo, int $year): array
    {
        return [
            'political' => $this->sumCalendarYear(EthicsPoliticalViolation::query(), $staffNo, $year),
            'education' => (float) EthicsEducationViolation::query()
                ->where('staff_no', $staffNo)
                ->forAnnualYear($year)
                ->sum('deduction_points'),
            'academic' => $this->sumCalendarYear(EthicsAcademicViolation::query(), $staffNo, $year),
            'professional' => $this->sumCalendarYear(EthicsProfessionalViolation::query(), $staffNo, $year),
            'discipline' => $this->sumCalendarYear(EthicsDisciplineViolation::query(), $staffNo, $year),
        ];
    }

    /**
     * @return array{
     *     year: int,
     *     deductions: array<string, float>,
     *     cappedDeductions: array<string, float>,
     *     modules: array<string, float>,
     *     totalDeduction: float,
     *     totalScore: float,
     *     warningLevel: string|null
     * }
     */
    public function summary(string $staffNo, int $year): array
    {
        $deductions = collect($this->deductionTotals($staffNo, $year))
            ->map(fn (float $value): float => round($value, 2))
            ->all();
        $capped = collect($deductions)
            ->map(fn (float $value): float => min(self::DIMENSION_MAX, $value))
            ->all();
        $modules = collect($capped)
            ->map(fn (float $value): float => max(0, round(self::DIMENSION_MAX - $value, 2)))
            ->all();
        $totalDeduction = round((float) array_sum($capped), 2);
        $totalScore = max(0, round(self::TOTAL_MAX - $totalDeduction, 2));

        return [
            'year' => $year,
            'deductions' => $deductions,
            'cappedDeductions' => $capped,
            'modules' => $modules,
            'totalDeduction' => $totalDeduction,
            'totalScore' => $totalScore,
            'warningLevel' => $this->warningLevel($totalScore, $capped),
        ];
    }

    /**
     * @param array<string, float> $dimensionDeductions
     */
    public function warningLevel(float $totalScore, array $dimensionDeductions): ?string
    {
        $maxDimensionDeduction = (float) max($dimensionDeductions ?: [0]);

        if ($totalScore <= 80 || $maxDimensionDeduction >= 20) {
            return 'red';
        }

        if (($totalScore >= 81 && $totalScore <= 94) || $maxDimensionDeduction >= 10) {
            return 'yellow';
        }

        if (($totalScore >= 95 && $totalScore <= 99) || $maxDimensionDeduction >= 5) {
            return 'blue';
        }

        return null;
    }

    public function deductionForSeverity(?string $severityLevel, ?float $fallback = null): float
    {
        $normalized = strtoupper(trim((string) $severityLevel));

        if (isset(self::SEVERITY_DEDUCTIONS[$normalized])) {
            return self::SEVERITY_DEDUCTIONS[$normalized];
        }

        return $fallback !== null ? round($fallback, 2) : 0.0;
    }

    /**
     * @return Collection<int, array{staff_no: string, summary: array<string, mixed>}>
     */
    public function summariesForStaffNos(iterable $staffNos, int $year): Collection
    {
        return collect($staffNos)
            ->map(fn (mixed $staffNo): string => trim((string) $staffNo))
            ->filter()
            ->unique()
            ->map(fn (string $staffNo): array => [
                'staff_no' => $staffNo,
                'summary' => $this->summary($staffNo, $year),
            ])
            ->values();
    }

    private function sumCalendarYear(Builder $query, string $staffNo, int $year): float
    {
        return (float) $query
            ->where('staff_no', $staffNo)
            ->whereYear('violation_at', $year)
            ->sum('deduction_points');
    }
}
