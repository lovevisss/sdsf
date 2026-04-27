<?php

namespace App\Http\Controllers;

use App\Actions\Ethics\UpsertAnnualDeductionWarning;
use App\Http\Requests\StoreEthicsAcademicViolationRequest;
use App\Models\EthicsAcademicViolation;
use App\Models\EthicsProfile;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class EthicsAcademicViolationController extends Controller
{
    private const MAX_ACADEMIC_SCORE = 25.0;

    public function __construct(private readonly UpsertAnnualDeductionWarning $upsertAnnualDeductionWarning)
    {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EthicsAcademicViolation::class);

        $user = $request->user();
        $year = (int) $request->query('year', now()->year);
        $selectedStaffNo = $request->query('staff_no');

        $scopedQuery = EthicsAcademicViolation::query()
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
            ->map(function (EthicsAcademicViolation $item) use ($year): array {
                $totalDeduction = (float) $item->total_deduction;

                return [
                    'staff_no' => $item->staff_no,
                    'staff_name' => $item->staff_name,
                    'staff_unit_name' => $item->staff_unit_name,
                    'violation_count' => (int) $item->violation_count,
                    'total_deduction' => round($totalDeduction, 2),
                    'remaining_score' => max(0, round(self::MAX_ACADEMIC_SCORE - $totalDeduction, 2)),
                    'profile_url' => route('ethics.profiles.staff.show', ['staffNo' => $item->staff_no, 'year' => $year]),
                ];
            })
            ->values();

        $selectedStaffSummary = null;

        if (is_string($selectedStaffNo) && $selectedStaffNo !== '') {
            $selectedStaffSummary = $staffSummaries->firstWhere('staff_no', $selectedStaffNo);
        }

        $staffOptions = $this->staffOptions();

        return Inertia::render('Ethics/AcademicViolations/Index', [
            'records' => $records,
            'year' => $year,
            'selectedStaffNo' => $selectedStaffNo,
            'selectedStaffSummary' => $selectedStaffSummary,
            'staffSummaries' => $staffSummaries,
            'violationTypeOptions' => [
                16 => '伪造学历、学位、资历、成果等行为',
                17 => '违规使用科研经费，牟取不正当利益',
                18 => '科研弄虚作假、抄袭剽窃、篡改成果数据等',
                19 => '成果署名不当、一稿多投或重复发表申报',
                20 => '滥用学术资源和影响干扰他人科研活动',
                21 => '参与隐匿学术劣迹或学术造假',
                22 => '评审考核中捏造事实、虚假学术信息、恶意诬告',
            ],
            'staffOptions' => $staffOptions,
            'staffOptionsCount' => count($staffOptions),
            'staffOptionsError' => count($staffOptions) === 0 ? '未能读取 Staff 人员数据，请检查 staff_db 连接。' : null,
        ]);
    }

    public function store(StoreEthicsAcademicViolationRequest $request)
    {
        $this->authorize('create', EthicsAcademicViolation::class);

        $validated = $request->validated();
        $profile = EthicsProfile::query()->where('staff_no', $validated['staff_no'])->first();

        EthicsAcademicViolation::query()->create([
            ...$validated,
            'ethics_profile_id' => $profile?->id,
            'violator_user_id' => $profile?->user_id,
            'recorder_user_id' => $request->user()->id,
        ]);

        $violationTimestamp = strtotime((string) $validated['violation_at']);
        $violationYear = $violationTimestamp !== false ? (int) date('Y', $violationTimestamp) : now()->year;
        $this->upsertAnnualDeductionWarning->handle((string) $validated['staff_no'], $violationYear);

        return redirect()->route('ethics.academic-violations.index', [
            'staff_no' => $validated['staff_no'],
        ])->with('success', '学术科研道德违规记录已保存。');
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
            Log::warning('Failed to load staff options for ethics academic violations.', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }
}
