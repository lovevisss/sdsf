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

Route::get('projects/create', [App\Http\Controllers\ProjectController::class, 'create'])->name('projects.create');

Route::post('/projects', [App\Http\Controllers\ProjectController::class, 'store'])->name('projects.store');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
