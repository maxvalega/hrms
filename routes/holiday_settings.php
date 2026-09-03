<?php

use App\Http\Controllers\HolidaySettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'XSS'])->group(function () {
    Route::get('settings/holiday', [HolidaySettingsController::class, 'show'])->name('settings.holiday.show');
    Route::post('settings/holiday', [HolidaySettingsController::class, 'store'])->name('settings.holiday.store');
});
