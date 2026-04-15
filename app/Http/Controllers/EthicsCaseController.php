<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEthicsCaseActionRequest;
use App\Http\Requests\StoreEthicsCaseRequest;
use App\Http\Requests\UpdateEthicsCaseStatusRequest;
use App\Models\EthicsCase;
use App\Models\EthicsCaseAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EthicsCaseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $this->authorize('viewAny', EthicsCase::class);
        $status = $request->query('status');

        $query = EthicsCase::query()->with(['profile.user:id,name', 'reporter:id,name', 'department:id,name']);

        if ($user->role === 'leader') {
            $query->where('department_id', $user->department_id);
        }

        if ($user->role === 'advisor') {
            $query->whereHas('profile', function (Builder $builder) use ($user): void {
                $builder->where('user_id', $user->id);
            });
        }

        if ($user->role === 'student') {
            $query->where('reporter_id', $user->id);
        }

        $cases = $query
            ->when($status, fn (Builder $builder, string $currentStatus) => $builder->where('status', $currentStatus))
            ->latest('reported_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Ethics/Cases/Index', [
            'cases' => $cases,
            'currentStatus' => $status,
        ]);
    }

    public function store(StoreEthicsCaseRequest $request)
    {
        $this->authorize('create', EthicsCase::class);

        $case = EthicsCase::query()->create([
            ...$request->validated(),
            'reporter_id' => $request->user()->id,
            'reported_at' => now(),
            'status' => 'reported',
        ]);

        EthicsCaseAction::query()->create([
            'ethics_case_id' => $case->id,
            'actor_id' => $request->user()->id,
            'action_type' => 'note',
            'notes' => '案件已提交，等待受理。',
            'happened_at' => now(),
        ]);

        return redirect()->route('ethics.cases.index')->with('success', '投诉举报已提交。');
    }

    public function updateStatus(UpdateEthicsCaseStatusRequest $request, EthicsCase $case)
    {
        $this->authorize('updateStatus', $case);

        $status = $request->validated('status');

        $case->update([
            'status' => $status,
            'accepted_at' => $status === 'accepted' ? now() : $case->accepted_at,
            'closed_at' => in_array($status, ['closed', 'rejected'], true) ? now() : null,
        ]);

        $actionMap = [
            'accepted' => 'accept',
            'assigned' => 'assign',
            'investigating' => 'investigate',
            'resolved' => 'rectify',
            'closed' => 'close',
            'rejected' => 'reject',
        ];

        EthicsCaseAction::query()->create([
            'ethics_case_id' => $case->id,
            'actor_id' => $request->user()->id,
            'action_type' => $actionMap[$status],
            'notes' => $request->validated('notes') ?? '状态更新为 '.$status,
            'happened_at' => now(),
        ]);

        return redirect()->back()->with('success', '案件状态已更新。');
    }

    public function storeAction(StoreEthicsCaseActionRequest $request, EthicsCase $case)
    {
        $this->authorize('addAction', $case);

        EthicsCaseAction::query()->create([
            'ethics_case_id' => $case->id,
            ...$request->validated(),
            'actor_id' => $request->user()->id,
            'happened_at' => now(),
        ]);

        return redirect()->back()->with('success', '处置记录已添加。');
    }
}

