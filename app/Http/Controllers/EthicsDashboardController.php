<?php

namespace App\Http\Controllers;

use App\Models\EthicsCase;
use App\Models\EthicsEducationViolation;
use App\Models\EthicsPoliticalViolation;
use App\Models\EthicsProfile;
use App\Models\EthicsWarning;
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

        return Inertia::render('Ethics/Dashboard', [
            'stats' => [
                'year' => $year,
                'selectedStaffNo' => $selectedStaffNo,
                'profileCount' => (clone $profileQuery)->count(),
                'openCaseCount' => (clone $caseQuery)->whereNotIn('status', ['closed', 'rejected'])->count(),
                'highRiskCaseCount' => (clone $caseQuery)->where('risk_level', 'high')->count(),
                'openWarningCount' => (clone $warningQuery)->where('status', '!=', 'closed')->count(),
                'politicalViolationCount' => (clone $politicalViolationQuery)->count(),
                'politicalSelectedDeductionTotal' => round($politicalSelectedDeductionTotal, 2),
                'politicalSelectedRemainingScore' => max(0, round(25 - $politicalSelectedDeductionTotal, 2)),
                'educationViolationCount' => (clone $educationViolationQuery)->count(),
                'educationSelectedDeductionTotal' => round($educationSelectedDeductionTotal, 2),
                'educationSelectedRemainingScore' => max(0, round(25 - $educationSelectedDeductionTotal, 2)),
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
}

