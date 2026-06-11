<?php

namespace App\Http\Controllers;

use App\Models\EthicsAcademicViolation;
use App\Models\EthicsDisciplineViolation;
use App\Models\EthicsEducationViolation;
use App\Models\EthicsPoliticalViolation;
use App\Models\EthicsProfessionalViolation;
use App\Models\EthicsProfile;
use App\Models\EthicsWarning;
use App\Services\Ethics\EthicsScoreService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EthicsReportExportController extends Controller
{
    public function __construct(private readonly EthicsScoreService $scoreService)
    {
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', EthicsProfile::class);

        $type = (string) $request->query('type', 'profile_details');
        $year = (int) $request->query('year', now()->year);

        return match ($type) {
            'department_summary' => $this->download("ethics-department-summary-{$year}.csv", $this->departmentSummaryRows($request, $year)),
            'dimension_summary' => $this->download("ethics-dimension-summary-{$year}.csv", $this->dimensionSummaryRows($request, $year)),
            'warning_details' => $this->download("ethics-warning-details-{$year}.csv", $this->warningRows($request, $year)),
            default => $this->download("ethics-profile-details-{$year}.csv", $this->profileRows($request, $year)),
        };
    }

    /**
     * @return array<int, array<int, scalar|null>>
     */
    private function profileRows(Request $request, int $year): array
    {
        $rows = [['工号', '姓名', '部门', '政治素养', '教育教学', '学术诚信', '为人师表', '工作纪律', '年度总分', '预警级别']];

        foreach ($this->visibleProfiles($request)->get() as $profile) {
            if (! is_string($profile->staff_no) || $profile->staff_no === '') {
                continue;
            }

            $summary = $this->scoreService->summary($profile->staff_no, $year);

            $rows[] = [
                $profile->staff_no,
                $profile->user?->name,
                $profile->department?->name,
                $summary['modules']['political'],
                $summary['modules']['education'],
                $summary['modules']['academic'],
                $summary['modules']['professional'],
                $summary['modules']['discipline'],
                $summary['totalScore'],
                $summary['warningLevel'] ?? '',
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<int, scalar|null>>
     */
    private function departmentSummaryRows(Request $request, int $year): array
    {
        $rows = [['部门', '人数', '平均分', '蓝色预警', '黄色预警', '红色预警']];

        $profiles = $this->visibleProfiles($request)->get()->groupBy(fn (EthicsProfile $profile): string => (string) ($profile->department?->name ?? '未归属'));

        foreach ($profiles as $departmentName => $items) {
            $summaries = $items
                ->filter(fn (EthicsProfile $profile): bool => is_string($profile->staff_no) && $profile->staff_no !== '')
                ->map(fn (EthicsProfile $profile): array => $this->scoreService->summary((string) $profile->staff_no, $year));

            $rows[] = [
                $departmentName,
                $summaries->count(),
                round((float) $summaries->avg('totalScore'), 2),
                $summaries->where('warningLevel', 'blue')->count(),
                $summaries->where('warningLevel', 'yellow')->count(),
                $summaries->where('warningLevel', 'red')->count(),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<int, scalar|null>>
     */
    private function dimensionSummaryRows(Request $request, int $year): array
    {
        $rows = [['维度', '记录数', '原始扣分合计', '封顶扣分合计']];
        $staffNos = $this->visibleProfiles($request)->pluck('staff_no')->filter()->values()->all();

        foreach (EthicsScoreService::DIMENSIONS as $key => $label) {
            $records = $this->dimensionQuery($key, $year)->whereIn('staff_no', $staffNos);
            $rawTotal = (float) (clone $records)->sum('deduction_points');
            $cappedTotal = $this->scoreService
                ->summariesForStaffNos((clone $records)->distinct()->pluck('staff_no'), $year)
                ->sum(fn (array $item): float => (float) $item['summary']['cappedDeductions'][$key]);

            $rows[] = [$label, (clone $records)->count(), round($rawTotal, 2), round($cappedTotal, 2)];
        }

        return $rows;
    }

    /**
     * @return array<int, array<int, scalar|null>>
     */
    private function warningRows(Request $request, int $year): array
    {
        $rows = [['教师', '工号', '部门', '级别', '状态', '原因', '时间']];
        $profileIds = $this->visibleProfiles($request)->pluck('id');

        $warnings = EthicsWarning::query()
            ->with(['profile.user:id,name', 'profile.department:id,name'])
            ->whereIn('ethics_profile_id', $profileIds)
            ->whereYear('detected_at', $year)
            ->latest('detected_at')
            ->get();

        foreach ($warnings as $warning) {
            $rows[] = [
                $warning->profile?->user?->name,
                $warning->profile?->staff_no,
                $warning->profile?->department?->name,
                $warning->warning_level,
                $warning->status,
                $warning->reason,
                optional($warning->detected_at)->toDateTimeString(),
            ];
        }

        return $rows;
    }

    private function visibleProfiles(Request $request): Builder
    {
        $user = $request->user();
        $query = EthicsProfile::query()->with(['user:id,name', 'department:id,name']);

        if ($user->role === 'leader' && ! ($user->is_admin ?? false)) {
            $query->where('department_id', $user->department_id);
        }

        if ($user->role === 'advisor') {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    private function dimensionQuery(string $key, int $year): Builder
    {
        return match ($key) {
            'political' => EthicsPoliticalViolation::query()->whereYear('violation_at', $year),
            'education' => EthicsEducationViolation::query()->forAnnualYear($year),
            'academic' => EthicsAcademicViolation::query()->whereYear('violation_at', $year),
            'professional' => EthicsProfessionalViolation::query()->whereYear('violation_at', $year),
            'discipline' => EthicsDisciplineViolation::query()->whereYear('violation_at', $year),
            default => EthicsPoliticalViolation::query()->whereRaw('1 = 0'),
        };
    }

    /**
     * @param array<int, array<int, scalar|null>> $rows
     */
    private function download(string $filename, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            echo "\xEF\xBB\xBF";
            $handle = fopen('php://output', 'w');

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
