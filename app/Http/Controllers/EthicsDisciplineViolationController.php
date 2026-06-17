<?php

namespace App\Http\Controllers;

use App\Actions\Ethics\UpsertAnnualDeductionWarning;
use App\Http\Controllers\Concerns\StoresEthicsViolation;
use App\Http\Requests\StoreEthicsDisciplineViolationRequest;
use App\Models\EthicsDisciplineViolation;
use App\Models\EthicsProfile;
use App\Models\Staff;
use App\Services\Ethics\StaffMonthlyAttendanceSyncService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class EthicsDisciplineViolationController extends Controller
{
    use StoresEthicsViolation;

    private const MAX_DISCIPLINE_SCORE = 20.0;

    public function __construct(
        private readonly UpsertAnnualDeductionWarning $upsertAnnualDeductionWarning,
        private readonly StaffMonthlyAttendanceSyncService $attendanceSyncService,
    )
    {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EthicsDisciplineViolation::class);

        $user = $request->user();
        $year = (int) $request->query('year', now()->year);
        $selectedStaffNo = $request->query('staff_no');

        $scopedQuery = EthicsDisciplineViolation::query()
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
            ->map(function (EthicsDisciplineViolation $item) use ($year): array {
                $totalDeduction = (float) $item->total_deduction;

                return [
                    'staff_no' => $item->staff_no,
                    'staff_name' => $item->staff_name,
                    'staff_unit_name' => $item->staff_unit_name,
                    'violation_count' => (int) $item->violation_count,
                    'total_deduction' => round($totalDeduction, 2),
                    'remaining_score' => max(0, round(self::MAX_DISCIPLINE_SCORE - min(self::MAX_DISCIPLINE_SCORE, $totalDeduction), 2)),
                    'profile_url' => route('ethics.profiles.staff.show', ['staffNo' => $item->staff_no, 'year' => $year]),
                ];
            })
            ->values();

        return Inertia::render('Ethics/DisciplineViolations/Index', [
            'records' => $records,
            'year' => $year,
            'selectedStaffNo' => $selectedStaffNo,
            'selectedStaffSummary' => is_string($selectedStaffNo) && $selectedStaffNo !== ''
                ? $staffSummaries->firstWhere('staff_no', $selectedStaffNo)
                : null,
            'staffSummaries' => $staffSummaries,
            'violationTypeOptions' => $this->violationTypeOptions(),
            'staffOptions' => $this->staffOptions(),
        ]);
    }

    public function store(StoreEthicsDisciplineViolationRequest $request)
    {
        $this->authorize('create', EthicsDisciplineViolation::class);

        $validated = $this->prepareViolationPayload($request->validated(), $request);
        $profile = EthicsProfile::query()->where('staff_no', $validated['staff_no'])->first();

        EthicsDisciplineViolation::query()->create([
            ...$validated,
            'ethics_profile_id' => $profile?->id,
            'violator_user_id' => $profile?->user_id,
            'recorder_user_id' => $request->user()->id,
        ]);

        $violationTimestamp = strtotime((string) $validated['violation_at']);
        $violationYear = $violationTimestamp !== false ? (int) date('Y', $violationTimestamp) : now()->year;
        $this->upsertAnnualDeductionWarning->handle((string) $validated['staff_no'], $violationYear);

        return redirect()->route('ethics.discipline-violations.index', [
            'staff_no' => $validated['staff_no'],
        ])->with('success', '工作纪律违规记录已保存。');
    }

    public function syncAttendance(Request $request)
    {
        $this->authorize('create', EthicsDisciplineViolation::class);

        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:1900', 'max:9999'],
            'stat_month' => ['nullable', 'date_format:Y-m'],
        ]);

        try {
            $result = $this->attendanceSyncService->sync(
                statMonth: $validated['stat_month'] ?? null,
                year: isset($validated['year']) ? (int) $validated['year'] : now()->year,
                recorderUserId: $request->user()->id,
            );
        } catch (\Throwable $exception) {
            Log::warning('Failed to sync staff monthly attendance into ethics discipline violations.', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('ethics.discipline-violations.index', [
                'year' => $validated['year'] ?? now()->year,
            ])->with('error', '月度考勤同步失败：'.$exception->getMessage());
        }

        return redirect()->route('ethics.discipline-violations.index', [
            'year' => $validated['year'] ?? now()->year,
        ])->with('attendanceSync', $result)
            ->with('success', sprintf(
                '月度考勤同步完成：读取 %d 条，新增 %d 条，跳过 %d 条，未达阈值 %d 条。',
                $result['read'],
                $result['inserted'],
                $result['skipped'],
                $result['below_threshold'],
            ));
    }

    /**
     * @return array<int, string>
     */
    private function violationTypeOptions(): array
    {
        return [
            35 => '迟到早退、缺勤脱岗或未按规定履行请假手续',
            36 => '未经审批办理或持有出国（境）证件',
            37 => '私自出国（境）、擅自变更行程或逾期滞留不归',
            38 => '违规校外兼职或未按要求报备',
            39 => '其他违反学院工作纪律的行为',
        ];
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
            Log::warning('Failed to load staff options for ethics discipline violations.', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }
}
