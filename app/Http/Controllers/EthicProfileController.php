<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\EthicsProfile;
use App\Models\EthicsAcademicViolation;
use App\Models\EthicsEducationViolation;
use App\Models\EthicsPoliticalViolation;
use App\Models\EthicsProfessionalViolation;
use App\Models\Staff;
use App\Models\TeacherEvaluation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class EthicProfileController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EthicsProfile::class);

        $departmentFilter = trim((string) $request->query('department', ''));
//        dd($departmentFilter);
        $normalizedDepartmentFilter = strtoupper($departmentFilter);

        try {
            $staffDepartmentCodes = Staff::query()
                ->selectRaw('UPPER(TRIM(szdwbm)) as department_code')
                ->whereNotNull('szdwbm')
                ->where('szdwbm', '!=', '')
                ->distinct()
                ->pluck('department_code')
                ->map(fn (mixed $code): string => (string) $code)
                ->values();

            if ($staffDepartmentCodes->isEmpty()) {
                throw new \RuntimeException('Staff department mapping is unavailable.');
            }

            $departmentQuery = Department::query()
                ->select(['code', 'name'])
                ->whereNotNull('code')
                ->where('code', '!=', '')
                ->orderBy('name')
                ->whereIn('code', $staffDepartmentCodes);
//            dd($departmentQuery->get());
            $departments = $departmentQuery->get();

            if ($departments->isEmpty()) {
                $departments = Department::query()
                    ->select(['code', 'name'])
                    ->whereNotNull('code')
                    ->where('code', '!=', '')
                    ->orderBy('name')
                    ->get();
            }

            $departmentMap = $departments->keyBy(fn (Department $department): string => strtoupper(trim((string) $department->code)));
            $departmentOptions = $departments
                ->map(fn (Department $department): array => [
                    'code' => $department->code,
                    'name' => $department->name,
                ])
                ->values()
                ->all();
            if($normalizedDepartmentFilter == ""){
                $staffQuery = Staff::where('xbm' ,'!=', '0');
            }else{
                $staffQuery = Staff::where('szdwbm', $normalizedDepartmentFilter);
            }
            $staffRecords = $staffQuery
                ->paginate(20)
                ->through(function (Staff $staff) use ($departmentMap): array {
                    $archive = $staff->toArchiveArray();
                    $departmentName = $departmentMap->get(strtoupper(trim((string) $staff->szdwbm)))?->name;

                    return [
                        ...$archive,
                        'unit_name' => $archive['unit_name'] ?? $departmentName,
                        'profile_url' => route('ethics.profiles.staff.show', ['staffNo' => $archive['staff_no']]),
                    ];
                })
                ->withQueryString();
        } catch (\Throwable) {
            $departments = Department::query()
                ->select(['id', 'code', 'name'])
                ->orderBy('name')
                ->get();

            $departmentOptions = $departments
                ->filter(fn (Department $department): bool => $department->code !== null && $department->code !== '')
                ->map(fn (Department $department): array => [
                    'code' => (string) $department->code,
                    'name' => $department->name,
                ])
                ->values()
                ->all();

            $profiles = EthicsProfile::query()
                ->with(['user:id,name', 'department:id,name'])
                ->latest('id');

            if ($normalizedDepartmentFilter !== '') {
                $profiles->whereHas('department', function ($query) use ($normalizedDepartmentFilter): void {
                    $query->whereRaw('UPPER(code) = ?', [$normalizedDepartmentFilter]);
                });
            }

            $staffRecords = $profiles
                ->paginate(20)
                ->through(function (EthicsProfile $profile): array {
                    return [
                        'staff_no' => $profile->staff_no,
                        'name' => $profile->user?->name,
                        'unit_name' => $profile->department?->name,
                        'profile_url' => route('ethics.profiles.show', ['user' => $profile->user_id]),
                    ];
                })
                ->withQueryString();
        }

//        dd($staffRecords);

        return Inertia::render('Ethics/Profiles/Index', [
            'staffRecords' => $staffRecords,
            'departmentFilter' => $departmentFilter,
            'departmentOptions' => $departmentOptions,
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        $this->authorize('viewAny', EthicsProfile::class);

        $staffNo = $user->ethicsProfile?->staff_no;

        if (! is_string($staffNo) || $staffNo === '') {
            $staffNo = 'U'.$user->id;
        }

        return $this->showByStaff($request, $staffNo);
    }

    public function showByStaff(Request $request, string $staffNo): Response
    {
        $this->authorize('viewAny', EthicsProfile::class);

        $currentUser = $request->user();

        if ($currentUser?->role === 'advisor') {
            $ownStaffNo = (string) ($currentUser->ethicsProfile?->staff_no ?? '');

            if ($ownStaffNo === '' || $ownStaffNo !== $staffNo) {
                abort(403);
            }
        }

        $year = (int) $request->query('year', now()->year);
        $staff = null;

        try {
            $staff = Staff::query()->find($staffNo);
        } catch (\Throwable) {
            // External staff source is optional in local/test environments.
        }

        if ($currentUser?->role === 'leader' && $currentUser->is_admin !== true) {
            $allowedDepartment = (string) ($currentUser->department?->name ?? '');
            $staffDepartment = (string) ($staff?->unit_name ?? '');

            if ($allowedDepartment !== '' && $staffDepartment !== '' && $allowedDepartment !== $staffDepartment) {
                abort(403);
            }
        }

        $politicalByYear = EthicsPoliticalViolation::query()
            ->where('staff_no', $staffNo)
            ->select(['violation_at', 'deduction_points'])
            ->get()
            ->groupBy(function (EthicsPoliticalViolation $row): int {
                $timestamp = strtotime((string) $row->violation_at);

                return $timestamp !== false ? (int) date('Y', $timestamp) : 0;
            })
            ->map(fn ($items): float => (float) collect($items)->sum('deduction_points'));

        $educationByYear = EthicsEducationViolation::query()
            ->where('staff_no', $staffNo)
            ->select(['violation_at', 'deduction_points'])
            ->get()
            ->groupBy(function (EthicsEducationViolation $row): int {
                $timestamp = strtotime((string) $row->violation_at);

                return $timestamp !== false ? (int) date('Y', $timestamp) : 0;
            })
            ->map(fn ($items): float => (float) collect($items)->sum('deduction_points'));

        $academicByYear = EthicsAcademicViolation::query()
            ->where('staff_no', $staffNo)
            ->select(['violation_at', 'deduction_points'])
            ->get()
            ->groupBy(function (EthicsAcademicViolation $row): int {
                $timestamp = strtotime((string) $row->violation_at);

                return $timestamp !== false ? (int) date('Y', $timestamp) : 0;
            })
            ->map(fn ($items): float => (float) collect($items)->sum('deduction_points'));

        $professionalByYear = EthicsProfessionalViolation::query()
            ->where('staff_no', $staffNo)
            ->select(['violation_at', 'deduction_points'])
            ->get()
            ->groupBy(function (EthicsProfessionalViolation $row): int {
                $timestamp = strtotime((string) $row->violation_at);

                return $timestamp !== false ? (int) date('Y', $timestamp) : 0;
            })
            ->map(fn ($items): float => (float) collect($items)->sum('deduction_points'));

        $automaticByYear = EthicsEducationViolation::query()
            ->where('staff_no', $staffNo)
            ->where('violation_type', 10)
            ->where('notes', '教师评价后10%')
            ->select(['violation_at'])
            ->get()
            ->groupBy(function (EthicsEducationViolation $row): int {
                $timestamp = strtotime((string) $row->violation_at);

                return $timestamp !== false ? (int) date('Y', $timestamp) : 0;
            })
            ->map(fn ($items): int => (int) count($items));

        $evaluationByYear = collect();

        try {
            $evaluationRows = TeacherEvaluation::query()
                ->where('JSBH', $staffNo)
                ->whereNotNull('XN')
                ->whereNotNull('PJCJ')
                ->select(['XN', 'PJCJ'])
                ->get();

            $evaluationByYear = $evaluationRows
                ->map(function (TeacherEvaluation $row): ?array {
                    preg_match('/\d{4}/', (string) $row->XN, $matches);

                    if (! isset($matches[0])) {
                        return null;
                    }

                    return [
                        'year' => (int) $matches[0],
                        'score' => (float) $row->PJCJ,
                    ];
                })
                ->filter()
                ->groupBy('year')
                ->map(fn ($items): float => round((float) collect($items)->avg('score'), 2));
        } catch (\Throwable) {
            // External evaluation source is optional in local/test environments.
        }

        $allYears = collect([
            ...array_keys($politicalByYear->all()),
            ...array_keys($educationByYear->all()),
            ...array_keys($academicByYear->all()),
            ...array_keys($professionalByYear->all()),
            ...array_keys($automaticByYear->all()),
            ...array_keys($evaluationByYear->all()),
            $year,
        ])->map(fn (mixed $item): int => (int) $item)
            ->filter(fn (int $item): bool => $item > 0)
            ->unique()
            ->sortDesc()
            ->values();

        $buildSummary = function (int $targetYear) use ($politicalByYear, $educationByYear, $academicByYear, $professionalByYear, $automaticByYear, $evaluationByYear): array {
            $politicalAnnualDeductionTotal = round((float) ($politicalByYear->get($targetYear, 0.0)), 2);
            $educationAnnualDeductionTotal = round((float) ($educationByYear->get($targetYear, 0.0)), 2);
            $academicAnnualDeductionTotal = round((float) ($academicByYear->get($targetYear, 0.0)), 2);
            $professionalAnnualDeductionTotal = round((float) ($professionalByYear->get($targetYear, 0.0)), 2);
            $teacherEvaluationAverage = round((float) ($evaluationByYear->get($targetYear, 0.0)), 2);
            $automaticLowEvaluationCount = (int) ($automaticByYear->get($targetYear, 0));

            $modules = [
                'political' => max(0, round(25 - $politicalAnnualDeductionTotal, 2)),
                'education' => max(0, round(25 - $educationAnnualDeductionTotal, 2)),
                'academic' => max(0, round(25 - $academicAnnualDeductionTotal, 2)),
                'professional' => max(0, round(25 - $professionalAnnualDeductionTotal, 2)),
            ];

            return [
                'year' => $targetYear,
                'politicalAnnualDeductionTotal' => $politicalAnnualDeductionTotal,
                'politicalAnnualRemainingScore' => max(0, round(25 - $politicalAnnualDeductionTotal, 2)),
                'educationAnnualDeductionTotal' => $educationAnnualDeductionTotal,
                'educationAnnualRemainingScore' => max(0, round(25 - $educationAnnualDeductionTotal, 2)),
                'academicAnnualDeductionTotal' => $academicAnnualDeductionTotal,
                'academicAnnualRemainingScore' => max(0, round(25 - $academicAnnualDeductionTotal, 2)),
                'professionalAnnualDeductionTotal' => $professionalAnnualDeductionTotal,
                'professionalAnnualRemainingScore' => max(0, round(25 - $professionalAnnualDeductionTotal, 2)),
                'teacherEvaluationAverage' => $teacherEvaluationAverage,
                'automaticLowEvaluationCount' => $automaticLowEvaluationCount,
                'modules' => $modules,
                'totalScore' => round(array_sum($modules), 2),
            ];
        };

        $yearlySummaries = $allYears
            ->map(fn (int $targetYear): array => $buildSummary($targetYear))
            ->values()
            ->all();

        $currentYearSummary = $buildSummary($year);

        return Inertia::render('Ethics/Profiles/Show', [
            'profile' => [
                'staff_no' => $staffNo,
                'name' => $staff?->name,
                'unit_name' => $staff?->unit_name,
            ],
            'summary' => [
                ...$currentYearSummary,
            ],
            'yearlySummaries' => $yearlySummaries,
        ]);
    }

    public function legacyShow(string $id): Response
    {
        $user = User::query()->findOrFail((int) $id);

        return $this->show(request(), $user);
    }

    public function fetchDetails(string $teacherId): \Illuminate\Http\JsonResponse
    {
        try {
            $politicalViolations = EthicsPoliticalViolation::query()->where('staff_no', $teacherId)->get();
            $educationViolations = EthicsEducationViolation::query()->where('staff_no', $teacherId)->get();

            return response()->json([
                'political_violations' => $politicalViolations,
                'education_violations' => $educationViolations,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['error' => 'Unable to fetch details'], 500);
        }
    }
}
