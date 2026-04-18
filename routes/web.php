<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\ViolationController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\CasController;
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
    return Inertia::render('Dashboard');
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
