<?php

use App\Http\Controllers\Admin\GoogleCalendarController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/google/callback', [GoogleCalendarController::class, 'callback']);
Route::get('/google/calendar/callback', [GoogleCalendarController::class, 'callback']);

Route::view('/{any?}', 'app')->where('any', '^(?!api(?:/|$)|up(?:/|$)|google/(?:calendar/)?callback$).*');

Route::get('run-migrations', function () {
    Artisan::call('migrate');
    return redirect()->back()->with('success', 'Migration completed successfully.');
});