<?php

use App\Http\Controllers\StyleguideController;
use App\Http\Middleware\EnsureLocalEnvironment;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Design-system review page. Served only in the local environment (404 elsewhere).
Route::get('/styleguide', StyleguideController::class)
    ->middleware(EnsureLocalEnvironment::class)
    ->name('styleguide');

Route::middleware(['auth', 'verified', 'can:access-admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('/', 'admin.dashboard')->name('dashboard');
    });
