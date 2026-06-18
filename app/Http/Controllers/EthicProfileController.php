<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\EthicsProfile;
use App\Models\EthicsAcademicViolation;
use App\Models\EthicsDisciplineViolation;
use App\Models\EthicsEducationViolation;
use App\Models\EthicsPoliticalViolation;
use App\Models\EthicsProfessionalViolation;
use App\Models\Staff;
use App\Models\TeacherEvaluation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\Ethics\EthicsScoreService;

class EthicProfileController extends Controller
{
    public function __construct(private readonly EthicsScoreService $scoreService)
    {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EthicsProfile::class);

        $departmentFilter = trim((string) $request->query('department', ''));
        $nameFilter = trim((string) $request->query('name', $request->query('unit', '')));
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
            $staffQuery = Staff::query();

            if ($normalizedDepartmentFilter !== '') {
                $staffQuery->whereRaw('UPPER(TRIM(szdwbm)) = ?', [$normalizedDepartmentFilter]);
            }

            if ($nameFilter !== '') {
                $staffQuery->where('xm', 'like', "%{$nameFilter}%");
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

            $staffRecords = $this->appendLatestModuleScores($staffRecords);
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

            if ($nameFilter !== '') {
                $profiles->whereHas('user', function ($query) use ($nameFilter): void {
                    $query->where('name', 'like', "%{$nameFilter}%");
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

            $staffRecords = $this->appendLatestModuleScores($staffRecords);
        }

        return Inertia::render('Ethics/Profiles/Index', [
            'staffRecords' => $staffRecords,
            'departmentFilter' => $departmentFilter,
            'nameFilter' => $nameFilter,
            'departmentOptions' => $departmentOptions,
        ]);
    }

    private function appendLatestModuleScores(LengthAwarePaginator $staffRecords): LengthAwarePaginator
    {
        $rows = collect($staffRecords->items())
            ->map(fn (mixed $row): array => is_array($row) ? $row : (array) $row);

        $staffNos = $rows
            ->pluck('staff_no')
            ->filter(fn (mixed $staffNo): bool => is_string($staffNo) && $staffNo !== '')
            ->values()
            ->all();

        if ($staffNos === []) {
            return $staffRecords;
        }

        $political = $this->deductionsByStaffAndYear(EthicsPoliticalViolation::class, $staffNos);
        $education = $this->deductionsByStaffAndYear(EthicsEducationViolation::class, $staffNos);
        $academic = $this->deductionsByStaffAndYear(EthicsAcademicViolation::class, $staffNos);
        $professional = $this->deductionsByStaffAndYear(EthicsProfessionalViolation::class, $staffNos);
        $discipline = $this->deductionsByStaffAndYear(EthicsDisciplineViolation::class, $staffNos);

        $enriched = $rows->map(function (array $row) use ($political, $education, $academic, $professional): array {
            $staffNo = (string) ($row['staff_no'] ?? '');

            $years = collect([
                ...array_keys($political[$staffNo] ?? []),
                ...array_keys($education[$staffNo] ?? []),
                ...array_keys($academic[$staffNo] ?? []),
                ...array_keys($professional[$staffNo] ?? []),
                ...array_keys($discipline[$staffNo] ?? []),
            ])->map(fn (mixed $value): int => (int) $value)
                ->filter(fn (int $value): bool => $value > 0)
                ->sortDesc()
                ->values();

            $latestYear = (int) ($years->first() ?? now()->year);

            $politicalDeduction = (float) ($political[$staffNo][$latestYear] ?? 0.0);
            $educationDeduction = (float) ($education[$staffNo][$latestYear] ?? 0.0);
            $academicDeduction = (float) ($academic[$staffNo][$latestYear] ?? 0.0);
            $professionalDeduction = (float) ($professional[$staffNo][$latestYear] ?? 0.0);
            $disciplineDeduction = (float) ($discipline[$staffNo][$latestYear] ?? 0.0);

            return [
                ...$row,
                'latest_year' => $latestYear,
                'latest_scores' => [
                    'political' => max(0, round(20 - min(20, $politicalDeduction), 2)),
                    'education' => max(0, round(20 - min(20, $educationDeduction), 2)),
                    'academic' => max(0, round(20 - min(20, $academicDeduction), 2)),
                    'professional' => max(0, round(20 - min(20, $professionalDeduction), 2)),
                    'discipline' => max(0, round(20 - min(20, $disciplineDeduction), 2)),
                ],
            ];
        });

        $staffRecords->setCollection($enriched);

        return $staffRecords;
    }

    /**
     * @param class-string<EthicsPoliticalViolation|EthicsEducationViolation|EthicsAcademicViolation|EthicsProfessionalViolation|EthicsDisciplineViolation> $modelClass
     * @param array<int, string> $staffNos
     * @return array<string, array<int, float>>
     */
    private function deductionsByStaffAndYear(string $modelClass, array $staffNos): array
    {
        $columns = ['staff_no', 'violation_at', 'deduction_points'];

        if ($modelClass === EthicsEducationViolation::class) {
            $columns[] = 'academic_year';
        }

        $rows = $modelClass::query()
            ->whereIn('staff_no', $staffNos)
            ->select($columns)
            ->get();

        $totals = [];

        foreach ($rows as $row) {
            $staffNo = (string) $row->staff_no;

            if ($staffNo === '') {
                continue;
            }

            $year = $modelClass === EthicsEducationViolation::class
                ? $this->resolveEducationAnnualYear($row)
                : $this->resolveCalendarYearFromViolationAt((string) $row->violation_at);

            if ($year < 1) {
                continue;
            }

            $totals[$staffNo][$year] = (float) ($totals[$staffNo][$year] ?? 0.0) + (float) $row->deduction_points;
        }

        return $totals;
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
        $profile = EthicsProfile::query()
            ->with(['user:id,name', 'department:id,name'])
            ->where('staff_no', $staffNo)
            ->first();
        $violationIdentity = $this->violationIdentityForStaffNo($staffNo);

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
            ->select(['academic_year', 'violation_at', 'deduction_points'])
            ->get()
            ->groupBy(function (EthicsEducationViolation $row): int {
                return $this->resolveEducationAnnualYear($row);
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

        $disciplineByYear = EthicsDisciplineViolation::query()
            ->where('staff_no', $staffNo)
            ->select(['violation_at', 'deduction_points'])
            ->get()
            ->groupBy(function (EthicsDisciplineViolation $row): int {
                $timestamp = strtotime((string) $row->violation_at);

                return $timestamp !== false ? (int) date('Y', $timestamp) : 0;
            })
            ->map(fn ($items): float => (float) collect($items)->sum('deduction_points'));

        $automaticByYear = EthicsEducationViolation::query()
            ->where('staff_no', $staffNo)
            ->where('violation_type', 10)
            ->where('notes', '教师评价后10%')
            ->select(['academic_year', 'violation_at'])
            ->get()
            ->groupBy(function (EthicsEducationViolation $row): int {
                return $this->resolveEducationAnnualYear($row);
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
            ...array_keys($disciplineByYear->all()),
            ...array_keys($automaticByYear->all()),
            ...array_keys($evaluationByYear->all()),
            $year,
        ])->map(fn (mixed $item): int => (int) $item)
            ->filter(fn (int $item): bool => $item > 0)
            ->unique()
            ->sortDesc()
            ->values();

        $buildSummary = function (int $targetYear) use ($staffNo, $politicalByYear, $educationByYear, $academicByYear, $professionalByYear, $disciplineByYear, $automaticByYear, $evaluationByYear): array {
            $scoreSummary = $this->scoreService->summary($staffNo, $targetYear);
            $politicalAnnualDeductionTotal = round((float) ($politicalByYear->get($targetYear, 0.0)), 2);
            $educationAnnualDeductionTotal = round((float) ($educationByYear->get($targetYear, 0.0)), 2);
            $academicAnnualDeductionTotal = round((float) ($academicByYear->get($targetYear, 0.0)), 2);
            $professionalAnnualDeductionTotal = round((float) ($professionalByYear->get($targetYear, 0.0)), 2);
            $disciplineAnnualDeductionTotal = round((float) ($disciplineByYear->get($targetYear, 0.0)), 2);
            $teacherEvaluationAverage = round((float) ($evaluationByYear->get($targetYear, 0.0)), 2);
            $automaticLowEvaluationCount = (int) ($automaticByYear->get($targetYear, 0));

            return [
                'year' => $targetYear,
                'politicalAnnualDeductionTotal' => $politicalAnnualDeductionTotal,
                'politicalAnnualRemainingScore' => $scoreSummary['modules']['political'],
                'educationAnnualDeductionTotal' => $educationAnnualDeductionTotal,
                'educationAnnualRemainingScore' => $scoreSummary['modules']['education'],
                'academicAnnualDeductionTotal' => $academicAnnualDeductionTotal,
                'academicAnnualRemainingScore' => $scoreSummary['modules']['academic'],
                'professionalAnnualDeductionTotal' => $professionalAnnualDeductionTotal,
                'professionalAnnualRemainingScore' => $scoreSummary['modules']['professional'],
                'disciplineAnnualDeductionTotal' => $disciplineAnnualDeductionTotal,
                'disciplineAnnualRemainingScore' => $scoreSummary['modules']['discipline'],
                'teacherEvaluationAverage' => $teacherEvaluationAverage,
                'automaticLowEvaluationCount' => $automaticLowEvaluationCount,
                'modules' => $scoreSummary['modules'],
                'cappedDeductions' => $scoreSummary['cappedDeductions'],
                'warningLevel' => $scoreSummary['warningLevel'],
                'totalScore' => $scoreSummary['totalScore'],
            ];
        };

        $yearlySummaries = $allYears
            ->map(fn (int $targetYear): array => $buildSummary($targetYear))
            ->values()
            ->all();

        $currentYearSummary = $buildSummary($year);
        $deductionRecords = $this->buildDeductionRecords($staffNo);
        $returnTo = $request->query('from') === 'dashboard'
            ? ['url' => route('dashboard'), 'label' => '返回工作台']
            : ['url' => route('ethics.profiles.index'), 'label' => '返回列表'];

        return Inertia::render('Ethics/Profiles/Show', [
            'profile' => [
                'staff_no' => $staffNo,
                'name' => $staff?->name ?? $profile?->user?->name ?? $violationIdentity['name'],
                'unit_name' => $staff?->unit_name ?? $profile?->department?->name ?? $violationIdentity['unit_name'],
            ],
            'summary' => [
                ...$currentYearSummary,
            ],
            'yearlySummaries' => $yearlySummaries,
            'deductionRecords' => $deductionRecords,
            'returnTo' => $returnTo,
        ]);
    }

    /**
     * @return array{name: string|null, unit_name: string|null}
     */
    private function violationIdentityForStaffNo(string $staffNo): array
    {
        $latestRecord = collect([
            EthicsPoliticalViolation::class,
            EthicsEducationViolation::class,
            EthicsAcademicViolation::class,
            EthicsProfessionalViolation::class,
            EthicsDisciplineViolation::class,
        ])
            ->map(fn (string $modelClass) => $modelClass::query()
                ->where('staff_no', $staffNo)
                ->select(['staff_name', 'staff_unit_name', 'violation_at'])
                ->latest('violation_at')
                ->first())
            ->filter()
            ->sortByDesc(function ($row): int {
                $timestamp = strtotime((string) $row->violation_at);

                return $timestamp !== false ? $timestamp : 0;
            })
            ->first();

        return [
            'name' => $latestRecord?->staff_name,
            'unit_name' => $latestRecord?->staff_unit_name,
        ];
    }

    /**
     * @return array<int, array{
     *     module: string,
     *     module_key: string,
     *     violation_type: int,
     *     violation_type_label: string,
     *     violation_at: string,
     *     deduction_points: float,
     *     notes: string|null,
     *     recorder_name: string|null
     * }>
     */
    private function buildDeductionRecords(string $staffNo): array
    {
        $politicalRecords = EthicsPoliticalViolation::query()
            ->with('recorder:id,name')
            ->where('staff_no', $staffNo)
            ->get()
            ->map(fn (EthicsPoliticalViolation $row): array => [
                'module' => '思想政治素养',
                'module_key' => 'political',
                'violation_type' => (int) $row->violation_type,
                'violation_type_label' => $this->politicalTypeLabel((int) $row->violation_type),
                'violation_at' => (string) $row->violation_at,
                'deduction_points' => round((float) $row->deduction_points, 2),
                'notes' => $row->notes,
                'recorder_name' => $row->recorder?->name,
            ]);

        $educationRecords = EthicsEducationViolation::query()
            ->with('recorder:id,name')
            ->where('staff_no', $staffNo)
            ->get()
            ->map(fn (EthicsEducationViolation $row): array => [
                'module' => '教育教学行为',
                'module_key' => 'education',
                'violation_type' => (int) $row->violation_type,
                'violation_type_label' => $this->educationTypeLabel((int) $row->violation_type),
                'violation_at' => (string) $row->violation_at,
                'deduction_points' => round((float) $row->deduction_points, 2),
                'notes' => $row->notes,
                'recorder_name' => $row->recorder?->name,
            ]);

        $academicRecords = EthicsAcademicViolation::query()
            ->with('recorder:id,name')
            ->where('staff_no', $staffNo)
            ->get()
            ->map(fn (EthicsAcademicViolation $row): array => [
                'module' => '学术科研道德',
                'module_key' => 'academic',
                'violation_type' => (int) $row->violation_type,
                'violation_type_label' => $this->academicTypeLabel((int) $row->violation_type),
                'violation_at' => (string) $row->violation_at,
                'deduction_points' => round((float) $row->deduction_points, 2),
                'notes' => $row->notes,
                'recorder_name' => $row->recorder?->name,
            ]);

        $professionalRecords = EthicsProfessionalViolation::query()
            ->with('recorder:id,name')
            ->where('staff_no', $staffNo)
            ->get()
            ->map(fn (EthicsProfessionalViolation $row): array => [
                'module' => '为人师表',
                'module_key' => 'professional',
                'violation_type' => (int) $row->violation_type,
                'violation_type_label' => $this->professionalTypeLabel((int) $row->violation_type),
                'violation_at' => (string) $row->violation_at,
                'deduction_points' => round((float) $row->deduction_points, 2),
                'notes' => $row->notes,
                'recorder_name' => $row->recorder?->name,
            ]);

        $disciplineRecords = EthicsDisciplineViolation::query()
            ->with('recorder:id,name')
            ->where('staff_no', $staffNo)
            ->get()
            ->map(fn (EthicsDisciplineViolation $row): array => [
                'module' => '工作纪律',
                'module_key' => 'discipline',
                'violation_type' => (int) $row->violation_type,
                'violation_type_label' => $this->disciplineTypeLabel((int) $row->violation_type),
                'violation_at' => (string) $row->violation_at,
                'deduction_points' => round((float) $row->deduction_points, 2),
                'notes' => $row->notes,
                'recorder_name' => $row->recorder?->name,
            ]);

        return collect()
            ->merge($politicalRecords)
            ->merge($educationRecords)
            ->merge($academicRecords)
            ->merge($professionalRecords)
            ->merge($disciplineRecords)
            ->sortByDesc(function (array $item): int {
                $timestamp = strtotime($item['violation_at']);

                return $timestamp !== false ? $timestamp : 0;
            })
            ->values()
            ->all();
    }

    private function politicalTypeLabel(int $type): string
    {
        return [
            1 => '损害党中央权威，违背路线方针政策',
            2 => '损害国家/社会/学院/学生合法权益',
            3 => '涉外活动危害国家安全和尊严利益',
            4 => '违反保密规定导致泄密',
            5 => '校园内外组织或参与非法宗教活动',
            6 => '传播低俗文化或非法/违禁出版物',
            7 => '宣传或参与封建迷信、邪教活动',
        ][$type] ?? "类型{$type}";
    }

    private function educationTypeLabel(int $type): string
    {
        return [
            8 => '课堂及网络发表、转发错误观点或散布虚假/不良信息',
            9 => '无故不承担教学任务或3次及以上拒绝学院分配工作',
            10 => '违反教学纪律、敷衍教学或违规兼职兼薪',
            11 => '违反考试（评卷）管理规定影响公平公正',
            12 => '讽刺、侮辱、歧视、体罚或变相体罚学生',
            13 => '要求学生从事与教学科研社会服务无关事项',
            14 => '突发事件中不顾学生安危擅离职守',
            15 => '从事不利于学生身心健康成长的活动或言行',
        ][$type] ?? "类型{$type}";
    }

    private function academicTypeLabel(int $type): string
    {
        return [
            16 => '伪造学历、学位、资历、成果等行为',
            17 => '违规使用科研经费，牟取不正当利益',
            18 => '科研弄虚作假、抄袭剽窃、篡改成果数据等',
            19 => '成果署名不当、一稿多投或重复发表申报',
            20 => '滥用学术资源和影响干扰他人科研活动',
            21 => '参与隐匿学术劣迹或学术造假',
            22 => '评审考核中捏造事实、虚假学术信息、恶意诬告',
        ][$type] ?? "类型{$type}";
    }

    private function professionalTypeLabel(int $type): string
    {
        return [
            23 => '不执行、不落实学院重大决策与部署',
            24 => '造谣传谣，侮辱诽谤和人身攻击他人',
            25 => '组织参与非法集会、违法上访等活动',
            26 => '辱骂殴打威胁学院工作人员，扰乱秩序',
            27 => '性骚扰、猥亵、虐待或与学生不正当关系',
            28 => '群组管理失责导致违法违规信息传播',
            29 => '组织参与黄赌毒及传销活动',
            30 => '擅自利用学院资产资源谋取私利',
            31 => '招生招聘评审等徇私舞弊弄虚作假',
            32 => '利用职务向学生家长索要收受财物',
            33 => '向学生家长推销牟利或利用家长资源谋私',
            34 => '其他有损教师职业声誉的言行',
        ][$type] ?? "类型{$type}";
    }

    private function resolveEducationAnnualYear(EthicsEducationViolation $row): int
    {
        $academicYear = trim((string) ($row->academic_year ?? ''));

        if ($academicYear !== '' && preg_match('/(\d{4})/', $academicYear, $matches) === 1) {
            return (int) $matches[1];
        }

        return $this->resolveCalendarYearFromViolationAt((string) $row->violation_at);
    }

    private function disciplineTypeLabel(int $type): string
    {
        return [
            35 => '迟到早退、缺勤脱岗或未按规定履行请假手续',
            36 => '未经审批办理或持有出国（境）证件',
            37 => '私自出国（境）、擅自变更行程或逾期滞留不归',
            38 => '违规校外兼职或未按要求报备',
            39 => '其他违反学院工作纪律的行为',
        ][$type] ?? "类型{$type}";
    }

    private function resolveCalendarYearFromViolationAt(string $violationAt): int
    {
        $timestamp = strtotime($violationAt);

        if ($timestamp === false) {
            return 0;
        }

        return (int) date('Y', $timestamp);
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
