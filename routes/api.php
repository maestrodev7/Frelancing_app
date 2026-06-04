<?php

use App\Http\Controllers\Api\StaffUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('staff.token')->group(function () {
    Route::post('/staff', [StaffUserController::class, 'store'])->name('api.staff.store');
});
