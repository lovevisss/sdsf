<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('/test',function(){
    return Inertia::render('Test');
});

Route::get('testing/create/{model}', function($model){
    $modelClass = 'App\\Models\\' . ucfirst($model);
    return $modelClass::factory()->create();
});

ROute::get('/tasks/{task}', [\App\Http\Controllers\TaskController::class, 'show']);

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

// Conversation system routes
Route::middleware('auth')->group(function () {
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
