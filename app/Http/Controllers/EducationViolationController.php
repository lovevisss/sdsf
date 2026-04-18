<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEthicsEducationViolationRequest;
use App\Models\EthicsEducationViolation;
use App\Models\EthicsProfile;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class EducationViolationController extends Controller
{
    private const MAX_EDUCATION_SCORE = 25.0;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EthicsEducationViolation::class);

        $user = $request->user();
        $year = (int) $request->query('year', now()->year);
        $selectedStaffNo = $request->query('staff_no');

        $scopedQuery = EthicsEducationViolation::query()
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
            ->map(function (EthicsEducationViolation $item): array {
                $totalDeduction = (float) $item->total_deduction;

                return [
                    'staff_no' => $item->staff_no,
                    'staff_name' => $item->staff_name,
                    'staff_unit_name' => $item->staff_unit_name,
                    'violation_count' => (int) $item->violation_count,
                    'total_deduction' => round($totalDeduction, 2),
                    'remaining_score' => max(0, round(self::MAX_EDUCATION_SCORE - $totalDeduction, 2)),
                ];
            })
            ->values();

        $selectedStaffSummary = null;

        if (is_string($selectedStaffNo) && $selectedStaffNo !== '') {
            $selectedStaffSummary = $staffSummaries->firstWhere('staff_no', $selectedStaffNo);
        }

        $staffOptions = $this->staffOptions();

        return Inertia::render('Ethics/EducationViolations/Index', [
            'records' => $records,
            'year' => $year,
            'selectedStaffNo' => $selectedStaffNo,
            'selectedStaffSummary' => $selectedStaffSummary,
            'staffSummaries' => $staffSummaries,
            'violationTypeOptions' => [
                8 => '课堂及网络发表、转发错误观点或散布虚假/不良信息',
                9 => '无故不承担教学任务或3次及以上拒绝学院分配工作',
                10 => '违反教学纪律、敷衍教学或违规兼职兼薪',
                11 => '违反考试（评卷）管理规定影响公平公正',
                12 => '讽刺、侮辱、歧视、体罚或变相体罚学生',
                13 => '要求学生从事与教学科研社会服务无关事项',
                14 => '突发事件中不顾学生安危擅离职守',
                15 => '从事不利于学生身心健康成长的活动或言行',
            ],
            'staffOptions' => $staffOptions,
            'staffOptionsCount' => count($staffOptions),
            'staffOptionsError' => count($staffOptions) === 0 ? '未能读取 Staff 人员数据，请检查 staff_db 连接。' : null,
        ]);
    }

    public function store(StoreEthicsEducationViolationRequest $request)
    {
        $this->authorize('create', EthicsEducationViolation::class);

        $validated = $request->validated();
        $profile = EthicsProfile::query()->where('staff_no', $validated['staff_no'])->first();

        EthicsEducationViolation::query()->create([
            ...$validated,
            'ethics_profile_id' => $profile?->id,
            'violator_user_id' => $profile?->user_id,
            'recorder_user_id' => $request->user()->id,
        ]);

        return redirect()->route('ethics.education-violations.index', [
            'staff_no' => $validated['staff_no'],
        ])->with('success', '教育教学行为违规记录已保存。');
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
            Log::warning('Failed to load staff options for ethics education violations.', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }
}
