<?php

namespace App\Http\Controllers;

use App\Models\EthicsCase;
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

        $profileQuery = EthicsProfile::query();
        $caseQuery = EthicsCase::query();
        $warningQuery = EthicsWarning::query();
        $politicalViolationQuery = EthicsPoliticalViolation::query()->whereYear('violation_at', now()->year);

        if ($user->role === 'leader') {
            $profileQuery->where('department_id', $user->department_id);
            $caseQuery->where('department_id', $user->department_id);
            $warningQuery->whereHas('profile', function (Builder $query) use ($user): void {
                $query->where('department_id', $user->department_id);
            });
            $politicalViolationQuery->whereHas('profile', function (Builder $query) use ($user): void {
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
        }

        $politicalDeductionTotal = (float) (clone $politicalViolationQuery)->sum('deduction_points');

        return Inertia::render('Ethics/Dashboard', [
            'stats' => [
                'profileCount' => (clone $profileQuery)->count(),
                'openCaseCount' => (clone $caseQuery)->whereNotIn('status', ['closed', 'rejected'])->count(),
                'highRiskCaseCount' => (clone $caseQuery)->where('risk_level', 'high')->count(),
                'openWarningCount' => (clone $warningQuery)->where('status', '!=', 'closed')->count(),
                'politicalViolationCount' => (clone $politicalViolationQuery)->count(),
                'politicalDeductionTotal' => round($politicalDeductionTotal, 2),
                'politicalRemainingScore' => max(0, round(25 - $politicalDeductionTotal, 2)),
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

