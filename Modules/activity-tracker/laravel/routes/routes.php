<?php
/**
 * Activity Tracker — route definitions.
 *
 * This file is loaded from routes/web.php (web routes) and routes/api.php
 * (api routes). Keep both in one place so the module stays self-contained.
 */

use App\Http\Controllers\ActivityTrackerController;
use App\Http\Controllers\Api\ActivityTrackerApiController;
use Illuminate\Support\Facades\Route;

/* ──────────────────────────────────────────────────────────────────────
 * WEB ROUTES — admin dashboard
 * Mount via:  require __DIR__ . '/../modules/activity-tracker/laravel/routes/routes.php';
 *             from inside routes/web.php (with auth+XSS middleware applied
 *             at the call site).
 * ──────────────────────────────────────────────────────────────────── */
Route::middleware(['auth', 'XSS'])->prefix('activity-tracker')->group(function () {
    Route::get ('/',                [ActivityTrackerController::class, 'index'])->name('activity-tracker.index');
    Route::get ('/user-activity',   [ActivityTrackerController::class, 'userActivity'])->name('activity-tracker.user-activity');
    Route::get ('/timeline',        [ActivityTrackerController::class, 'timeline'])->name('activity-tracker.timeline');
    Route::get ('/app-usage',       [ActivityTrackerController::class, 'appUsage'])->name('activity-tracker.app-usage');
    Route::get ('/daily-report',    [ActivityTrackerController::class, 'dailyReport'])->name('activity-tracker.daily-report');
    Route::get ('/daily-report.csv',[ActivityTrackerController::class, 'dailyReportCsv'])->name('activity-tracker.daily-report.csv');

    // Stop-request review (admin approve/reject)
    Route::post('/stop-request/{id}/review', [ActivityTrackerController::class, 'reviewStopRequest'])->name('activity-tracker.stop-request.review')->whereNumber('id');

    // Polling endpoint — returns pending count + items for real-time bell
    Route::get('/stop-requests/poll', [ActivityTrackerController::class, 'pollStopRequests'])->name('activity-tracker.stop-requests.poll');

    // Token UI removed — desktop agent auth no longer managed from this screen.
    // Route::get   ('/token',           [ActivityTrackerController::class, 'tokenIndex'])->name('activity-tracker.token');
    // Route::post  ('/token',           [ActivityTrackerController::class, 'tokenCreate'])->name('activity-tracker.token.create');
    // Route::delete('/token/{id}',      [ActivityTrackerController::class, 'tokenRevoke'])->name('activity-tracker.token.revoke')->whereNumber('id');
});
