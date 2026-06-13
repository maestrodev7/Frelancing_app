<?php

use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\MissionReviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProposalController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('role:admin,secretary')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/status', [UserManagementController::class, 'updateStatus'])
            ->name('users.status');
        Route::get('/disputes', [DisputeController::class, 'index'])->name('disputes.index');
        Route::get('/disputes/{dispute}', [DisputeController::class, 'show'])->name('disputes.show');
        Route::patch('/disputes/{dispute}/resolve', [DisputeController::class, 'resolve'])
            ->name('disputes.resolve');
        Route::get('/contacts', [ContactMessageController::class, 'index'])->name('contacts.index');
        Route::patch('/contacts/{contactMessage}/read', [ContactMessageController::class, 'markAsRead'])
            ->name('contacts.read');
    });

    Route::middleware('role:client,freelancer')->group(function () {
        Route::get('/missions', [MissionController::class, 'index'])->name('missions.index');
    });

    Route::middleware('role:client')->group(function () {
        Route::get('/missions/create', [MissionController::class, 'create'])->name('missions.create');
        Route::post('/missions', [MissionController::class, 'store'])->name('missions.store');
        Route::post('/missions/{mission}/close', [MissionController::class, 'close'])->name('missions.close');
        Route::post('/missions/{mission}/disputes', [DisputeController::class, 'store'])
            ->name('missions.disputes.store');
        Route::patch('/proposals/{proposal}/accept', [ProposalController::class, 'accept'])
            ->name('proposals.accept');
        Route::patch('/proposals/{proposal}/reject', [ProposalController::class, 'reject'])
            ->name('proposals.reject');
    });

    Route::middleware('role:client,freelancer')->group(function () {
        Route::get('/missions/{mission}', [MissionController::class, 'show'])->name('missions.show');
        Route::post('/missions/{mission}/reviews', [MissionReviewController::class, 'store'])
            ->name('missions.reviews.store');
    });

    Route::middleware('role:freelancer')->group(function () {
        Route::post('/missions/{mission}/proposals', [MissionController::class, 'storeProposal'])
            ->name('missions.proposals.store');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/freelancer', [ProfileController::class, 'updateFreelancer'])
        ->name('profile.freelancer.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
