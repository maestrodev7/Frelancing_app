<?php

use App\Http\Controllers\MissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProposalController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/missions', [MissionController::class, 'index'])->name('missions.index');

    Route::middleware('role:client')->group(function () {
        Route::get('/missions/create', [MissionController::class, 'create'])->name('missions.create');
        Route::post('/missions', [MissionController::class, 'store'])->name('missions.store');
    });

    Route::get('/missions/{mission}', [MissionController::class, 'show'])->name('missions.show');

    Route::middleware('role:freelancer')->group(function () {
        Route::post('/missions/{mission}/proposals', [MissionController::class, 'storeProposal'])
            ->name('missions.proposals.store');
    });

    Route::middleware('role:client')->group(function () {
        Route::patch('/proposals/{proposal}/accept', [ProposalController::class, 'accept'])
            ->name('proposals.accept');
        Route::patch('/proposals/{proposal}/reject', [ProposalController::class, 'reject'])
            ->name('proposals.reject');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/freelancer', [ProfileController::class, 'updateFreelancer'])
        ->name('profile.freelancer.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
