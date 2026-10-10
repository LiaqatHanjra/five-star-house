<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::view('/{any?}', 'app')->where('any', '^(?!api(?:/|$)|up(?:/|$)).*');

Route::get('run-migrations', function () {
    Artisan::call('migrate');
    return redirect()->back()->with('success', 'Migration completed successfully.');
});