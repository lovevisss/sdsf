<?php

namespace App\Http\Controllers;

use App\Models\EthicsProfile;
use App\Models\Staff;
use App\Models\User;
use App\Models\Violation;
use App\Models\Score;
use App\Models\Alert;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EthicProfileController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', EthicsProfile::class);

        try {
            $staffRecords = Staff::query()
                ->select(['gh', 'xm', 'dwmc'])
                ->orderBy('gh')
                ->paginate(20)
                ->through(fn (Staff $staff): array => $staff->toArchiveArray())
                ->withQueryString();
        } catch (\Throwable) {
            // Fallback keeps local development and test environment available
            // when the external staff source is not configured.
            $staffRecords = EthicsProfile::query()
                ->with(['user:id,name', 'department:id,name'])
                ->latest('id')
                ->paginate(20)
                ->through(function (EthicsProfile $profile): array {
                    return [
                        'staff_no' => $profile->staff_no,
                        'name' => $profile->user?->name,
                        'unit_name' => $profile->department?->name,
                    ];
                })
                ->withQueryString();
        }

        return Inertia::render('Ethics/Profiles/Index', [
            'staffRecords' => $staffRecords,
        ]);
    }

    public function show(Request $request, User $user)
    {
        $profile = EthicsProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'department_id' => $user->department_id,
                'staff_no' => 'U'.$user->id,
                'status' => 'active',
                'last_synced_at' => now(),
            ],
        );

        $this->authorize('view', $profile);

        $profile->load([
            'user:id,name,email,role,department_id',
            'department:id,name',
            'assessments' => fn ($query) => $query->latest('year')->limit(10),
            'warnings' => fn ($query) => $query->latest('detected_at')->limit(10),
            'cases' => fn ($query) => $query->latest('reported_at')->limit(10),
        ]);

        return Inertia::render('Ethics/Profiles/Show', [
            'profile' => $profile,
            'summary' => [
                'assessmentCount' => $profile->assessments->count(),
                'openWarningCount' => $profile->warnings->where('status', '!=', 'closed')->count(),
                'caseCount' => $profile->cases->count(),
            ],
        ]);
    }

    public function legacyShow(string $id)
    {
        $user = User::query()->findOrFail((int) $id);

        return $this->show(request(), $user);
    

    } // End legacyShow

    public function fetchDetails(string $teacherId): \Illuminate\Http\JsonResponse {
        try {
            $violations = Violation::where('teacher_id', $teacherId)->get();
            $scores = Score::where('teacher_id', $teacherId)->get();
            $alerts = Alert::where('teacher_id', $teacherId)->get();

            return response()->json([
                'violations' => $violations,
                'scores' => $scores,
                'alerts' => $alerts,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error($e->getMessage());
                        return response()->json(['error' => 'Unable to fetch details'], 500);
}
    }
}
