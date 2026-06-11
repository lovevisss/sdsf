<?php

namespace App\Http\Controllers;

use App\Models\EthicsAcademicViolation;
use App\Models\EthicsDisciplineViolation;
use App\Models\EthicsEducationViolation;
use App\Models\EthicsPoliticalViolation;
use App\Models\EthicsProfessionalViolation;
use App\Models\EthicsProfile;
use App\Models\EthicsWarning;
use App\Models\Staff;
use App\Services\Ethics\EthicsScoreService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EthicsDashboardController extends Controller
{
    public function __construct(private readonly EthicsScoreService $scoreService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $this->authorize('viewAny', EthicsProfile::class);

        $year = (int) $request->query('year', now()->year);
        $selectedStaffNo = $request->query('staff_no');

        if ((! is_string($selectedStaffNo) || $selectedStaffNo === '') && $user->role === 'advisor') {
            $selectedStaffNo = $user->ethicsProfile?->staff_no;
        }

        $warningQuery = EthicsWarning::query();
        $violationQueries = $this->scopedViolationQueries($user, $year);
        $staffNos = $this->staffNosFromQueries($violationQueries);
        $profilesByStaffNo = EthicsProfile::query()
            ->with(['user:id,name', 'department:id,name'])
            ->whereIn('staff_no', $staffNos)
            ->get()
            ->keyBy('staff_no');

        if ($user->role === 'leader') {
            $warningQuery->whereHas('profile', function (Builder $query) use ($user): void {
                $query->where('department_id', $user->department_id);
            });
        }

        if ($user->role === 'advisor') {
            $warningQuery->whereHas('profile', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id);
            });
        }

        $autoWarningPeople = $this->scoreService
            ->summariesForStaffNos($staffNos, $year)
            ->map(function (array $item) use ($profilesByStaffNo, $year): array {
                $staffNo = $item['staff_no'];
                $summary = $item['summary'];
                $profile = $profilesByStaffNo->get($staffNo);

                return [
                    'ethics_profile_id' => $profile?->id,
                    'staff_no' => $staffNo,
                    'name' => $profile?->user?->name,
                    'unit_name' => $profile?->department?->name,
                    'warning_level' => $summary['warningLevel'],
                    'status' => $summary['warningLevel'] === null ? null : 'open',
                    'detected_at' => now(),
                    'annual_deduction' => $summary['totalDeduction'],
                    'total_score' => $summary['totalScore'],
                    'profile_url' => route('ethics.profiles.staff.show', ['staffNo' => $staffNo, 'year' => $year]),
                ];
            })
            ->filter(fn (array $item): bool => in_array($item['warning_level'], ['blue', 'yellow', 'red'], true))
            ->sortBy([
                ['warning_level', 'desc'],
                ['annual_deduction', 'desc'],
            ])
            ->groupBy('warning_level');

        $selectedSummary = is_string($selectedStaffNo) && $selectedStaffNo !== ''
            ? $this->scoreService->summary($selectedStaffNo, $year)
            : null;

        return Inertia::render('Ethics/Dashboard', [
            'stats' => [
                'year' => $year,
                'selectedStaffNo' => $selectedStaffNo,
                'profileCount' => $this->archiveProfileCount($user),
                'openWarningCount' => (clone $warningQuery)->where('status', '!=', 'closed')->count(),
                'politicalViolationCount' => (clone $violationQueries['political'])->count(),
                'educationViolationCount' => (clone $violationQueries['education'])->count(),
                'academicViolationCount' => (clone $violationQueries['academic'])->count(),
                'professionalViolationCount' => (clone $violationQueries['professional'])->count(),
                'disciplineViolationCount' => (clone $violationQueries['discipline'])->count(),
                'selectedSummary' => $selectedSummary,
                'politicalSelectedDeductionTotal' => $selectedSummary['deductions']['political'] ?? 0,
                'politicalSelectedRemainingScore' => $selectedSummary['modules']['political'] ?? 20,
                'educationSelectedDeductionTotal' => $selectedSummary['deductions']['education'] ?? 0,
                'educationSelectedRemainingScore' => $selectedSummary['modules']['education'] ?? 20,
                'academicSelectedDeductionTotal' => $selectedSummary['deductions']['academic'] ?? 0,
                'academicSelectedRemainingScore' => $selectedSummary['modules']['academic'] ?? 20,
                'professionalSelectedDeductionTotal' => $selectedSummary['deductions']['professional'] ?? 0,
                'professionalSelectedRemainingScore' => $selectedSummary['modules']['professional'] ?? 20,
                'disciplineSelectedDeductionTotal' => $selectedSummary['deductions']['discipline'] ?? 0,
                'disciplineSelectedRemainingScore' => $selectedSummary['modules']['discipline'] ?? 20,
                'redWarningPersonCount' => ($autoWarningPeople->get('red') ?? collect())->count(),
                'yellowWarningPersonCount' => ($autoWarningPeople->get('yellow') ?? collect())->count(),
                'blueWarningPersonCount' => ($autoWarningPeople->get('blue') ?? collect())->count(),
            ],
            'dimensions' => EthicsScoreService::DIMENSIONS,
            'autoWarningPeople' => [
                'red' => ($autoWarningPeople->get('red') ?? collect())->values(),
                'yellow' => ($autoWarningPeople->get('yellow') ?? collect())->values(),
                'blue' => ($autoWarningPeople->get('blue') ?? collect())->values(),
            ],
            'recentWarnings' => (clone $warningQuery)
                ->with(['profile.user:id,name'])
                ->latest('detected_at')
                ->limit(10)
                ->get(),
        ]);
    }

    /**
     * @return array<string, Builder>
     */
    private function scopedViolationQueries($user, int $year): array
    {
        $queries = [
            'political' => EthicsPoliticalViolation::query()->whereYear('violation_at', $year),
            'education' => EthicsEducationViolation::query()->forAnnualYear($year),
            'academic' => EthicsAcademicViolation::query()->whereYear('violation_at', $year),
            'professional' => EthicsProfessionalViolation::query()->whereYear('violation_at', $year),
            'discipline' => EthicsDisciplineViolation::query()->whereYear('violation_at', $year),
        ];

        foreach ($queries as $query) {
            if ($user->role === 'leader') {
                $query->whereHas('profile', function (Builder $builder) use ($user): void {
                    $builder->where('department_id', $user->department_id);
                });
            }

            if ($user->role === 'advisor') {
                $query->where('violator_user_id', $user->id);
            }
        }

        return $queries;
    }

    /**
     * @param array<string, Builder> $queries
     * @return array<int, string>
     */
    private function staffNosFromQueries(array $queries): array
    {
        return collect($queries)
            ->flatMap(fn (Builder $query) => (clone $query)->distinct()->pluck('staff_no'))
            ->map(fn (mixed $staffNo): string => (string) $staffNo)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function archiveProfileCount($user): int
    {
        try {
            $staffQuery = Staff::query()->where('xbm', '!=', '0');

            if ($user->role === 'leader' && ! ($user->is_admin ?? false)) {
                $departmentCode = strtoupper(trim((string) ($user->department?->code ?? '')));

                if ($departmentCode !== '') {
                    $staffQuery->whereRaw('UPPER(TRIM(szdwbm)) = ?', [$departmentCode]);
                }
            }

            if ($user->role === 'advisor') {
                $staffNo = (string) ($user->ethicsProfile?->staff_no ?? '');

                if ($staffNo === '') {
                    return 0;
                }

                $staffQuery->where('gh', $staffNo);
            }

            return (int) $staffQuery->count('gh');
        } catch (\Throwable) {
            $fallback = EthicsProfile::query();

            if ($user->role === 'leader' && ! ($user->is_admin ?? false)) {
                $fallback->where('department_id', $user->department_id);
            }

            if ($user->role === 'advisor') {
                $fallback->where('user_id', $user->id);
            }

            return (int) $fallback->count();
        }
    }
}
