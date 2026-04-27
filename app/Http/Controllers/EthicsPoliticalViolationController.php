<?php

namespace App\Http\Controllers;

use App\Actions\Ethics\UpsertAnnualDeductionWarning;
use App\Http\Requests\StoreEthicsPoliticalViolationRequest;
use App\Models\EthicsPoliticalViolation;
use App\Models\EthicsProfile;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EthicsPoliticalViolationController extends Controller
{
    private const MAX_POLITICAL_SCORE = 25.0;

    public function __construct(private readonly UpsertAnnualDeductionWarning $upsertAnnualDeductionWarning)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', EthicsPoliticalViolation::class);

        $user = $request->user();
        $year = (int) $request->query('year', now()->year);
        $selectedStaffNo = $request->query('staff_no');

        $scopedQuery = EthicsPoliticalViolation::query()
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
            ->map(function (EthicsPoliticalViolation $item) use ($year): array {
                $totalDeduction = (float) $item->total_deduction;

                return [
                    'staff_no' => $item->staff_no,
                    'staff_name' => $item->staff_name,
                    'staff_unit_name' => $item->staff_unit_name,
                    'violation_count' => (int) $item->violation_count,
                    'total_deduction' => round($totalDeduction, 2),
                    'remaining_score' => max(0, round(self::MAX_POLITICAL_SCORE - $totalDeduction, 2)),
                    'profile_url' => route('ethics.profiles.staff.show', ['staffNo' => $item->staff_no, 'year' => $year]),
                ];
            })
            ->values();

        $selectedStaffSummary = null;

        if (is_string($selectedStaffNo) && $selectedStaffNo !== '') {
            $selectedStaffSummary = $staffSummaries->firstWhere('staff_no', $selectedStaffNo);
        }

        $staffOptions = $this->staffOptions();

        return Inertia::render('Ethics/PoliticalViolations/Index', [
            'records' => $records,
            'year' => $year,
            'selectedStaffNo' => $selectedStaffNo,
            'selectedStaffSummary' => $selectedStaffSummary,
            'staffSummaries' => $staffSummaries,
            'violationTypeOptions' => [
                1 => '损害党中央权威，违背路线方针政策',
                2 => '损害国家/社会/学院/学生合法权益',
                3 => '涉外活动危害国家安全和尊严利益',
                4 => '违反保密规定导致泄密',
                5 => '校园内外组织或参与非法宗教活动',
                6 => '传播低俗文化或非法/违禁出版物',
                7 => '宣传或参与封建迷信、邪教活动',
            ],
            'staffOptions' => $staffOptions,
            'staffOptionsCount' => count($staffOptions),
            'staffOptionsError' => count($staffOptions) === 0 ? '未能读取 Staff 人员数据，请检查 staff_db 连接。' : null,
        ]);
    }

    public function store(StoreEthicsPoliticalViolationRequest $request)
    {
        $this->authorize('create', EthicsPoliticalViolation::class);

        $validated = $request->validated();

        $profile = EthicsProfile::query()->where('staff_no', $validated['staff_no'])->first();

        EthicsPoliticalViolation::query()->create([
            ...$validated,
            'ethics_profile_id' => $profile?->id,
            'violator_user_id' => $profile?->user_id,
            'recorder_user_id' => $request->user()->id,
        ]);

        $violationTimestamp = strtotime((string) $validated['violation_at']);
        $violationYear = $violationTimestamp !== false ? (int) date('Y', $violationTimestamp) : now()->year;
        $this->upsertAnnualDeductionWarning->handle((string) $validated['staff_no'], $violationYear);

        return redirect()->route('ethics.political-violations.index', [
            'staff_no' => $validated['staff_no'],
        ])->with('success', '思想政治素养违规记录已保存。');
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
            Log::warning('Failed to load staff options for ethics political violations.', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }
}

