<?php

namespace App\Http\Controllers;

use App\Models\EthicsCase;
use App\Models\EthicsAcademicViolation;
use App\Models\EthicsEducationViolation;
use App\Models\EthicsPoliticalViolation;
use App\Models\EthicsProfessionalViolation;
use App\Models\EthicsProfile;
use App\Models\EthicsWarning;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EthicsDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $this->authorize('viewAny', EthicsProfile::class);

        $year = (int) $request->query('year', now()->year);
        $selectedStaffNo = $request->query('staff_no');

        if ((! is_string($selectedStaffNo) || $selectedStaffNo === '') && $user->role === 'advisor') {
            $selectedStaffNo = $user->ethicsProfile?->staff_no;
        }

        $profileQuery = EthicsProfile::query();
        $caseQuery = EthicsCase::query();
        $warningQuery = EthicsWarning::query();
        $politicalViolationQuery = EthicsPoliticalViolation::query()->whereYear('violation_at', $year);
        $educationViolationQuery = EthicsEducationViolation::query()->whereYear('violation_at', $year);
        $academicViolationQuery = EthicsAcademicViolation::query()->whereYear('violation_at', $year);
        $professionalViolationQuery = EthicsProfessionalViolation::query()->whereYear('violation_at', $year);

        if ($user->role === 'leader') {
            $profileQuery->where('department_id', $user->department_id);
            $caseQuery->where('department_id', $user->department_id);
            $warningQuery->whereHas('profile', function (Builder $query) use ($user): void {
                $query->where('department_id', $user->department_id);
            });
            $politicalViolationQuery->whereHas('profile', function (Builder $query) use ($user): void {
                $query->where('department_id', $user->department_id);
            });
            $educationViolationQuery->whereHas('profile', function (Builder $query) use ($user): void {
                $query->where('department_id', $user->department_id);
            });
            $academicViolationQuery->whereHas('profile', function (Builder $query) use ($user): void {
                $query->where('department_id', $user->department_id);
            });
            $professionalViolationQuery->whereHas('profile', function (Builder $query) use ($user): void {
                $query->where('department_id', $user->department_id);
            });
        }

        if ($user->role === 'advisor') {
            $profileQuery->where('user_id', $user->id);
            $caseQuery->whereHas('profile', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id);
            });
            $warningQuery->whereHas('profile', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id);
            });
            $politicalViolationQuery->where('violator_user_id', $user->id);
            $educationViolationQuery->where('violator_user_id', $user->id);
            $academicViolationQuery->where('violator_user_id', $user->id);
            $professionalViolationQuery->where('violator_user_id', $user->id);
        }

        $politicalSelectedQuery = clone $politicalViolationQuery;
        $educationSelectedQuery = clone $educationViolationQuery;

        if (is_string($selectedStaffNo) && $selectedStaffNo !== '') {
            $politicalSelectedQuery->where('staff_no', $selectedStaffNo);
            $educationSelectedQuery->where('staff_no', $selectedStaffNo);
        } else {
            $politicalSelectedQuery->whereRaw('1 = 0');
            $educationSelectedQuery->whereRaw('1 = 0');
        }

        $politicalSelectedDeductionTotal = (float) $politicalSelectedQuery->sum('deduction_points');
        $educationSelectedDeductionTotal = (float) $educationSelectedQuery->sum('deduction_points');

        $academicSelectedQuery = clone $academicViolationQuery;
        $professionalSelectedQuery = clone $professionalViolationQuery;

        if (is_string($selectedStaffNo) && $selectedStaffNo !== '') {
            $academicSelectedQuery->where('staff_no', $selectedStaffNo);
            $professionalSelectedQuery->where('staff_no', $selectedStaffNo);
        } else {
            $academicSelectedQuery->whereRaw('1 = 0');
            $professionalSelectedQuery->whereRaw('1 = 0');
        }

        $academicSelectedDeductionTotal = (float) $academicSelectedQuery->sum('deduction_points');
        $professionalSelectedDeductionTotal = (float) $professionalSelectedQuery->sum('deduction_points');

        $politicalTotals = (clone $politicalViolationQuery)
            ->selectRaw('staff_no, SUM(deduction_points) as total_deduction')
            ->groupBy('staff_no')
            ->pluck('total_deduction', 'staff_no')
            ->mapWithKeys(fn (mixed $value, mixed $staffNo): array => [
                (string) $staffNo => (float) $value,
            ]);
//        dd($politicalTotals);
        $educationTotals = (clone $educationViolationQuery)
            ->selectRaw('staff_no, SUM(deduction_points) as total_deduction')
            ->groupBy('staff_no')
            ->pluck('total_deduction', 'staff_no')
            ->mapWithKeys(fn (mixed $value, mixed $staffNo): array => [
                (string) $staffNo => (float) $value,
            ]);

        $academicTotals = (clone $academicViolationQuery)
            ->selectRaw('staff_no, SUM(deduction_points) as total_deduction')
            ->groupBy('staff_no')
            ->pluck('total_deduction', 'staff_no')
            ->mapWithKeys(fn (mixed $value, mixed $staffNo): array => [
                (string) $staffNo => (float) $value,
            ]);

        $professionalTotals = (clone $professionalViolationQuery)
            ->selectRaw('staff_no, SUM(deduction_points) as total_deduction')
            ->groupBy('staff_no')
            ->pluck('total_deduction', 'staff_no')
            ->mapWithKeys(fn (mixed $value, mixed $staffNo): array => [
                (string) $staffNo => (float) $value,
            ]);

        $allStaffNos = collect([
            ...array_keys($politicalTotals->all()),
            ...array_keys($educationTotals->all()),
            ...array_keys($academicTotals->all()),
            ...array_keys($professionalTotals->all()),
        ])->map(fn (mixed $staffNo): string => (string) $staffNo)
            ->filter(fn (string $staffNo): bool => $staffNo !== '')
            ->unique()
            ->values();
        $profilesByStaffNo = EthicsProfile::query()
            ->with(['user:id,name'])
            ->whereIn('staff_no', $allStaffNos)
            ->get()
            ->keyBy('staff_no');

        $autoWarningPeople = $allStaffNos
            ->map(function (string $staffNo) use ($politicalTotals, $educationTotals, $academicTotals, $professionalTotals, $profilesByStaffNo, $year): array {
                $annualDeduction = round((float) (
                    $politicalTotals->get($staffNo, 0.0)
                    + $educationTotals->get($staffNo, 0.0)
                    + $academicTotals->get($staffNo, 0.0)
                    + $professionalTotals->get($staffNo, 0.0)
                ), 2);

                $warningLevel = null;

                if ($annualDeduction >= 10) {
                    $warningLevel = 'red';
                } elseif ($annualDeduction >= 5) {
                    $warningLevel = 'yellow';
                }

                $profile = $profilesByStaffNo->get($staffNo);

                return [
                    'ethics_profile_id' => $profile?->id,
                    'staff_no' => $staffNo,
                    'name' => $profile?->user?->name,
                    'warning_level' => $warningLevel,
                    'status' => $warningLevel === null ? null : 'open',
                    'detected_at' => now(),
                    'annual_deduction' => $annualDeduction,
                    'profile_url' => $staffNo !== ''
                        ? route('ethics.profiles.staff.show', ['staffNo' => $staffNo, 'year' => $year])
                        : null,
                ];
            })
            ->filter(fn (array $item): bool => in_array($item['warning_level'], ['yellow', 'red'], true))
            ->sortByDesc('annual_deduction')
            ->values();
        $redWarningPeople = $autoWarningPeople
            ->where('warning_level', 'red')
            ->values();

        $yellowWarningPeople = $autoWarningPeople
            ->where('warning_level', 'yellow')
            ->values();

        $profileCount = $this->archiveProfileCount($user);

        return Inertia::render('Ethics/Dashboard', [
            'stats' => [
                'year' => $year,
                'selectedStaffNo' => $selectedStaffNo,
                'profileCount' => $profileCount,
                'openCaseCount' => (clone $caseQuery)->whereNotIn('status', ['closed', 'rejected'])->count(),
                'highRiskCaseCount' => (clone $caseQuery)->where('risk_level', 'high')->count(),
                'openWarningCount' => (clone $warningQuery)->where('status', '!=', 'closed')->count(),
                'politicalViolationCount' => (clone $politicalViolationQuery)->count(),
                'politicalSelectedDeductionTotal' => round($politicalSelectedDeductionTotal, 2),
                'politicalSelectedRemainingScore' => max(0, round(25 - $politicalSelectedDeductionTotal, 2)),
                'educationViolationCount' => (clone $educationViolationQuery)->count(),
                'educationSelectedDeductionTotal' => round($educationSelectedDeductionTotal, 2),
                'educationSelectedRemainingScore' => max(0, round(25 - $educationSelectedDeductionTotal, 2)),
                'academicViolationCount' => (clone $academicViolationQuery)->count(),
                'academicSelectedDeductionTotal' => round($academicSelectedDeductionTotal, 2),
                'academicSelectedRemainingScore' => max(0, round(25 - $academicSelectedDeductionTotal, 2)),
                'professionalViolationCount' => (clone $professionalViolationQuery)->count(),
                'professionalSelectedDeductionTotal' => round($professionalSelectedDeductionTotal, 2),
                'professionalSelectedRemainingScore' => max(0, round(25 - $professionalSelectedDeductionTotal, 2)),
                'redWarningPersonCount' => $redWarningPeople->count(),
                'yellowWarningPersonCount' => $yellowWarningPeople->count(),
            ],
            'autoWarningPeople' => [
                'red' => $redWarningPeople,
                'yellow' => $yellowWarningPeople,
            ],
            'recentCases' => (clone $caseQuery)
                ->with(['profile.user:id,name'])
                ->latest('reported_at')
                ->limit(10)
                ->get(),
            'recentWarnings' => (clone $warningQuery)
                ->with(['profile.user:id,name'])
                ->latest('detected_at')
                ->limit(10)
                ->get(),
        ]);
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

