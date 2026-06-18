<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\EthicsAcademicViolation;
use App\Models\EthicsDisciplineViolation;
use App\Models\EthicsEducationViolation;
use App\Models\EthicsPoliticalViolation;
use App\Models\EthicsProfessionalViolation;
use App\Models\EthicsProfile;
use App\Models\EthicsWarning;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\ViolationController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\CasController;
use App\Services\Ethics\EthicsScoreService;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('/test',function(){
    return Inertia::render('Test');
});

Route::get('/auth/cas/redirect', [CasController::class, 'redirect'])
    ->middleware('guest')
    ->name('cas.redirect');
Route::get('/auth/cas/callback', [CasController::class, 'callback'])
    ->middleware('guest')
    ->name('cas.callback');
Route::get('/auth/cas/logout', [CasController::class, 'logout'])
    ->middleware('auth')
    ->name('cas.logout');

Route::get('testing/create/{model}', function ($model) {
    $modelClass = 'App\\Models\\' . ucfirst($model);

    if (! class_exists($modelClass) || ! is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class)) {
        abort(404);
    }

    if (! method_exists($modelClass, 'factory')) {
        abort(400, 'Model factory is not available.');
    }

    return $modelClass::factory()->create();
});

Route::get('/tasks/{task}', [\App\Http\Controllers\TaskController::class, 'show']);

Route::get('projects/create', [App\Http\Controllers\ProjectController::class, 'create'])->name('projects.create');

Route::post('/projects', [App\Http\Controllers\ProjectController::class, 'store'])->name('projects.store');

Route::get('dashboard', function () {
    $year = now()->year;
    $openWarningQuery = EthicsWarning::query()->where('status', '!=', 'closed');

    $violationQueries = [
        'political' => EthicsPoliticalViolation::query(),
        'education' => EthicsEducationViolation::query(),
        'academic' => EthicsAcademicViolation::query(),
        'professional' => EthicsProfessionalViolation::query(),
        'discipline' => EthicsDisciplineViolation::query(),
    ];

    $annualViolationQueries = [
        'political' => EthicsPoliticalViolation::query()->whereYear('violation_at', $year),
        'education' => EthicsEducationViolation::query()->forAnnualYear($year),
        'academic' => EthicsAcademicViolation::query()->whereYear('violation_at', $year),
        'professional' => EthicsProfessionalViolation::query()->whereYear('violation_at', $year),
        'discipline' => EthicsDisciplineViolation::query()->whereYear('violation_at', $year),
    ];

    $violationCounts = collect($violationQueries)
        ->map(fn ($query): int => (clone $query)->count())
        ->all();

    $staffNos = collect($annualViolationQueries)
        ->flatMap(fn ($query) => (clone $query)->distinct()->pluck('staff_no'))
        ->map(fn (mixed $staffNo): string => (string) $staffNo)
        ->filter()
        ->unique()
        ->values();

    $profilesByStaffNo = EthicsProfile::query()
        ->with(['user:id,name', 'department:id,name'])
        ->whereIn('staff_no', $staffNos)
        ->get()
        ->keyBy('staff_no');

    $staffFallbacks = collect($annualViolationQueries)
        ->flatMap(fn ($query) => (clone $query)
            ->whereIn('staff_no', $staffNos)
            ->select(['staff_no', 'staff_name', 'staff_unit_name'])
            ->get()
            ->map(fn ($row): array => [
                'staff_no' => (string) $row->staff_no,
                'name' => $row->staff_name,
                'unit_name' => $row->staff_unit_name,
            ]))
        ->filter(fn (array $row): bool => $row['staff_no'] !== '')
        ->groupBy('staff_no')
        ->map(fn ($rows): array => [
            'name' => $rows->pluck('name')->filter()->first(),
            'unit_name' => $rows->pluck('unit_name')->filter()->first(),
        ]);

    $computedWarningPeople = app(EthicsScoreService::class)
        ->summariesForStaffNos($staffNos, $year)
        ->map(function (array $item) use ($profilesByStaffNo, $staffFallbacks, $year): array {
            $staffNo = $item['staff_no'];
            $summary = $item['summary'];
            $profile = $profilesByStaffNo->get($staffNo);
            $fallback = $staffFallbacks->get($staffNo, []);

            return [
                'staff_no' => $staffNo,
                'name' => $profile?->user?->name ?? ($fallback['name'] ?? null),
                'unit_name' => $profile?->department?->name ?? ($fallback['unit_name'] ?? null),
                'warning_level' => $summary['warningLevel'],
                'annual_deduction' => $summary['totalDeduction'],
                'total_score' => $summary['totalScore'],
                'profile_url' => route('ethics.profiles.staff.show', ['staffNo' => $staffNo, 'year' => $year, 'from' => 'dashboard']),
            ];
        })
        ->filter(fn (array $item): bool => in_array($item['warning_level'], ['blue', 'yellow', 'red'], true))
        ->sortBy([
            ['warning_level', 'desc'],
            ['annual_deduction', 'desc'],
        ])
        ->groupBy('warning_level');

    $computedWarningLevels = $computedWarningPeople
        ->map(fn ($people): int => $people->count());

    $databaseWarningPeople = (clone $openWarningQuery)
        ->with(['profile.user:id,name', 'profile.department:id,name'])
        ->whereIn('warning_level', ['blue', 'yellow', 'red'])
        ->latest('detected_at')
        ->get()
        ->map(function (EthicsWarning $warning) use ($year): array {
            $profile = $warning->profile;
            $staffNo = (string) ($profile?->staff_no ?? '');

            return [
                'staff_no' => $staffNo,
                'name' => $profile?->user?->name,
                'unit_name' => $profile?->department?->name,
                'warning_level' => $warning->warning_level,
                'annual_deduction' => null,
                'total_score' => null,
                'profile_url' => $staffNo !== ''
                    ? route('ethics.profiles.staff.show', ['staffNo' => $staffNo, 'year' => $year, 'from' => 'dashboard'])
                    : null,
            ];
        })
        ->filter(fn (array $item): bool => in_array($item['warning_level'], ['blue', 'yellow', 'red'], true))
        ->groupBy('warning_level');

    $computedWarningCount = (int) $computedWarningLevels->sum();
    $databaseWarningLevels = [
        'blue' => (clone $openWarningQuery)->where('warning_level', 'blue')->count(),
        'yellow' => (clone $openWarningQuery)->where('warning_level', 'yellow')->count(),
        'red' => (clone $openWarningQuery)->where('warning_level', 'red')->count(),
    ];

    $warningLevels = $computedWarningCount > 0
        ? [
            'blue' => (int) ($computedWarningLevels->get('blue') ?? 0),
            'yellow' => (int) ($computedWarningLevels->get('yellow') ?? 0),
            'red' => (int) ($computedWarningLevels->get('red') ?? 0),
        ]
        : $databaseWarningLevels;

    return Inertia::render('Dashboard', [
        'summary' => [
            'profileCount' => EthicsProfile::query()->count(),
            'openWarningCount' => $computedWarningCount > 0 ? $computedWarningCount : (clone $openWarningQuery)->count(),
            'warningLevels' => $warningLevels,
            'warningPeople' => [
                'blue' => ($computedWarningCount > 0 ? $computedWarningPeople : $databaseWarningPeople)->get('blue', collect())->values(),
                'yellow' => ($computedWarningCount > 0 ? $computedWarningPeople : $databaseWarningPeople)->get('yellow', collect())->values(),
                'red' => ($computedWarningCount > 0 ? $computedWarningPeople : $databaseWarningPeople)->get('red', collect())->values(),
            ],
            'violations' => $violationCounts,
            'totalViolationCount' => array_sum($violationCounts),
        ],
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/admin/coupons', [\App\Http\Controllers\CouponController::class,'store'])->middleware('admin');

Route::apiResource('posts', \App\Http\Controllers\PostController::class)->middleware('auth');

// Comment routes
Route::middleware('auth')->group(function () {
    // Comments on posts
    Route::post('/posts/{post}/comments', [\App\Http\Controllers\PostCommentController::class, 'store']);
    // Replies to comments
    Route::post('/comments/{comment}/replies', [\App\Http\Controllers\PostCommentController::class, 'storeReply']);
    // Update and delete comments
    Route::patch('/comments/{comment}', [\App\Http\Controllers\PostCommentController::class, 'update']);
    Route::delete('/comments/{comment}', [\App\Http\Controllers\PostCommentController::class, 'destroy']);
});

// 师德师风模块
Route::middleware('auth')->group(function () {
    Route::apiResource('ethics/scores', \App\Http\Controllers\ScoreController::class);
    Route::apiResource('ethics/violations', \App\Http\Controllers\ViolationController::class);
    Route::get('/ethics/education-violations', [\App\Http\Controllers\EducationViolationController::class, 'index'])
        ->name('ethics.education-violations.index');
    Route::post('/ethics/education-violations', [\App\Http\Controllers\EducationViolationController::class, 'store'])
        ->name('ethics.education-violations.store');
    Route::apiResource('ethics/alerts', \App\Http\Controllers\AlertController::class);
});
Route::middleware('auth')->group(function () {
    Route::get('/ethics/dashboard', [\App\Http\Controllers\EthicsDashboardController::class, 'index'])
        ->name('ethics.dashboard');
    Route::get('/ethics/profiles', [\App\Http\Controllers\EthicProfileController::class, 'index'])
        ->name('ethics.profiles.index');
    Route::get('/ethics/profiles/staff/{staffNo}', [\App\Http\Controllers\EthicProfileController::class, 'showByStaff'])
        ->name('ethics.profiles.staff.show');
    Route::get('/ethics/profiles/{user}', [\App\Http\Controllers\EthicProfileController::class, 'show'])
        ->name('ethics.profiles.show');

    // Legacy endpoint retained to avoid breaking previous links.
    Route::get('/ethics/profile/{id}', [\App\Http\Controllers\EthicProfileController::class, 'legacyShow'])
        ->whereNumber('id')
        ->name('ethics.profile.legacy');

    Route::get('/ethics/cases', [\App\Http\Controllers\EthicsCaseController::class, 'index'])
        ->name('ethics.cases.index');
    Route::post('/ethics/cases', [\App\Http\Controllers\EthicsCaseController::class, 'store'])
        ->name('ethics.cases.store');
    Route::patch('/ethics/cases/{case}/status', [\App\Http\Controllers\EthicsCaseController::class, 'updateStatus'])
        ->name('ethics.cases.update-status');
    Route::post('/ethics/cases/{case}/actions', [\App\Http\Controllers\EthicsCaseController::class, 'storeAction'])
        ->name('ethics.cases.store-action');

    Route::post('/ethics/warnings', [\App\Http\Controllers\EthicsWarningController::class, 'store'])
        ->name('ethics.warnings.store');
    Route::patch('/ethics/warnings/{warning}/close', [\App\Http\Controllers\EthicsWarningController::class, 'close'])
        ->name('ethics.warnings.close');

    Route::get('/ethics/political-violations', [\App\Http\Controllers\EthicsPoliticalViolationController::class, 'index'])
        ->name('ethics.political-violations.index');
    Route::post('/ethics/political-violations', [\App\Http\Controllers\EthicsPoliticalViolationController::class, 'store'])
        ->name('ethics.political-violations.store');

    Route::get('/ethics/academic-violations', [\App\Http\Controllers\EthicsAcademicViolationController::class, 'index'])
        ->name('ethics.academic-violations.index');
    Route::post('/ethics/academic-violations', [\App\Http\Controllers\EthicsAcademicViolationController::class, 'store'])
        ->name('ethics.academic-violations.store');

    Route::get('/ethics/professional-violations', [\App\Http\Controllers\EthicsProfessionalViolationController::class, 'index'])
        ->name('ethics.professional-violations.index');
    Route::post('/ethics/professional-violations', [\App\Http\Controllers\EthicsProfessionalViolationController::class, 'store'])
        ->name('ethics.professional-violations.store');

    Route::get('/ethics/discipline-violations', [\App\Http\Controllers\EthicsDisciplineViolationController::class, 'index'])
        ->name('ethics.discipline-violations.index');
    Route::post('/ethics/discipline-violations/sync-attendance', [\App\Http\Controllers\EthicsDisciplineViolationController::class, 'syncAttendance'])
        ->name('ethics.discipline-violations.sync-attendance');
    Route::post('/ethics/discipline-violations', [\App\Http\Controllers\EthicsDisciplineViolationController::class, 'store'])
        ->name('ethics.discipline-violations.store');

    Route::get('/ethics/reports/export', [\App\Http\Controllers\EthicsReportExportController::class, 'export'])
        ->name('ethics.reports.export');


    // Dashboard
    Route::get('/conversations/dashboard', [\App\Http\Controllers\ConversationDashboardController::class, 'index'])
        ->name('conversations.dashboard');

    // Appointments
    Route::get('/conversations/appointments', [\App\Http\Controllers\ConversationAppointmentController::class, 'index'])
        ->name('conversation-appointments.index');
    Route::post('/conversations/appointments', [\App\Http\Controllers\ConversationAppointmentController::class, 'store'])
        ->name('conversation-appointments.store');
    Route::get('/conversations/appointments/{appointment}', [\App\Http\Controllers\ConversationAppointmentController::class, 'show'])
        ->name('conversation-appointments.show');
    Route::patch('/conversations/appointments/{appointment}/confirm', [\App\Http\Controllers\ConversationAppointmentController::class, 'confirm'])
        ->name('conversation-appointments.confirm');
    Route::delete('/conversations/appointments/{appointment}', [\App\Http\Controllers\ConversationAppointmentController::class, 'destroy'])
        ->name('conversation-appointments.destroy');

    // Records
    Route::get('/conversations/records', [\App\Http\Controllers\ConversationRecordController::class, 'index'])
        ->name('conversation-records.index');
    Route::get('/conversations/records/create', [\App\Http\Controllers\ConversationRecordController::class, 'create'])
        ->name('conversation-records.create');
    Route::post('/conversations/records', [\App\Http\Controllers\ConversationRecordController::class, 'store'])
        ->name('conversation-records.store');
    Route::get('/conversations/records/{record}', [\App\Http\Controllers\ConversationRecordController::class, 'show'])
        ->name('conversation-records.show');
    Route::get('/conversations/records/{record}/edit', [\App\Http\Controllers\ConversationRecordController::class, 'edit'])
        ->name('conversation-records.edit');
    Route::patch('/conversations/records/{record}', [\App\Http\Controllers\ConversationRecordController::class, 'update'])
        ->name('conversation-records.update');
    Route::delete('/conversations/records/{record}', [\App\Http\Controllers\ConversationRecordController::class, 'destroy'])
        ->name('conversation-records.destroy');
});

require __DIR__.'/settings.php';
