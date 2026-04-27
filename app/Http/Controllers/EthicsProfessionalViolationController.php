<?php

namespace App\Http\Controllers;

use App\Actions\Ethics\UpsertAnnualDeductionWarning;
use App\Http\Requests\StoreEthicsProfessionalViolationRequest;
use App\Models\EthicsProfessionalViolation;
use App\Models\EthicsProfile;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class EthicsProfessionalViolationController extends Controller
{
    private const MAX_PROFESSIONAL_SCORE = 25.0;

    public function __construct(private readonly UpsertAnnualDeductionWarning $upsertAnnualDeductionWarning)
    {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EthicsProfessionalViolation::class);

        $user = $request->user();
        $year = (int) $request->query('year', now()->year);
        $selectedStaffNo = $request->query('staff_no');

        $scopedQuery = EthicsProfessionalViolation::query()
            ->with(['profile.user:id,name,department_id', 'recorder:id,name'])
            ->whereYear('violation_at', $year);

        if ($user->role === 'leader' && ! ($user->is_admin ?? false)) {
            $departmentName = $user->department?->name;

            $scopedQuery->where(function (Builder $builder) use ($user, $departmentName): void {
                $builder->whereHas('profile', function (Builder $profileQuery) use ($user): void {
                    $profileQuery->where('department_id', $user->department_id);
                });

                if ($departmentName !== null) {
                    $builder->orWhere('staff_unit_name', $departmentName);
                }
            });
        }

        if ($user->role === 'advisor') {
            $scopedQuery->where('violator_user_id', $user->id);
        }

        $query = clone $scopedQuery;

        if (is_string($selectedStaffNo) && $selectedStaffNo !== '') {
            $query->where('staff_no', $selectedStaffNo);
        }

        $records = $query->latest('violation_at')->paginate(15)->withQueryString();

        $staffSummaries = (clone $scopedQuery)
            ->selectRaw('staff_no, staff_name, staff_unit_name, COUNT(*) as violation_count, SUM(deduction_points) as total_deduction')
            ->groupBy('staff_no', 'staff_name', 'staff_unit_name')
            ->orderByDesc('total_deduction')
            ->limit(200)
            ->get()
            ->map(function (EthicsProfessionalViolation $item) use ($year): array {
                $totalDeduction = (float) $item->total_deduction;

                return [
                    'staff_no' => $item->staff_no,
                    'staff_name' => $item->staff_name,
                    'staff_unit_name' => $item->staff_unit_name,
                    'violation_count' => (int) $item->violation_count,
                    'total_deduction' => round($totalDeduction, 2),
                    'remaining_score' => max(0, round(self::MAX_PROFESSIONAL_SCORE - $totalDeduction, 2)),
                    'profile_url' => route('ethics.profiles.staff.show', ['staffNo' => $item->staff_no, 'year' => $year]),
                ];
            })
            ->values();

        $selectedStaffSummary = null;

        if (is_string($selectedStaffNo) && $selectedStaffNo !== '') {
            $selectedStaffSummary = $staffSummaries->firstWhere('staff_no', $selectedStaffNo);
        }

        $staffOptions = $this->staffOptions();

        return Inertia::render('Ethics/ProfessionalViolations/Index', [
            'records' => $records,
            'year' => $year,
            'selectedStaffNo' => $selectedStaffNo,
            'selectedStaffSummary' => $selectedStaffSummary,
            'staffSummaries' => $staffSummaries,
            'violationTypeOptions' => [
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
            ],
            'staffOptions' => $staffOptions,
            'staffOptionsCount' => count($staffOptions),
            'staffOptionsError' => count($staffOptions) === 0 ? '未能读取 Staff 人员数据，请检查 staff_db 连接。' : null,
        ]);
    }

    public function store(StoreEthicsProfessionalViolationRequest $request)
    {
        $this->authorize('create', EthicsProfessionalViolation::class);

        $validated = $request->validated();
        $profile = EthicsProfile::query()->where('staff_no', $validated['staff_no'])->first();

        EthicsProfessionalViolation::query()->create([
            ...$validated,
            'ethics_profile_id' => $profile?->id,
            'violator_user_id' => $profile?->user_id,
            'recorder_user_id' => $request->user()->id,
        ]);

        $violationTimestamp = strtotime((string) $validated['violation_at']);
        $violationYear = $violationTimestamp !== false ? (int) date('Y', $violationTimestamp) : now()->year;
        $this->upsertAnnualDeductionWarning->handle((string) $validated['staff_no'], $violationYear);

        return redirect()->route('ethics.professional-violations.index', [
            'staff_no' => $validated['staff_no'],
        ])->with('success', '为人师表违规记录已保存。');
    }

    /**
     * @return array<int, array{staff_no: string|null, name: string|null, unit_name: string|null}>
     */
    private function staffOptions(): array
    {
        try {
            return Staff::query()
                ->select(['gh', 'xm', 'dwmc'])
                ->orderBy('gh')
                ->get()
                ->map(fn (Staff $staff): array => $staff->toArchiveArray())
                ->all();
        } catch (\Throwable $exception) {
            Log::warning('Failed to load staff options for ethics professional violations.', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }
}
