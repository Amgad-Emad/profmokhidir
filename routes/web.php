<?php

use App\Constants\Sections;
use App\Http\Controllers\SectionPlaceholderController;
use App\Http\Controllers\StyleguideController;
use App\Http\Middleware\EnsureLocalEnvironment;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// One placeholder page per public section, named after the section, until its
// own controller exists. Detail routes nest under the same name (e.g. publications.show).
foreach (Sections::definitions() as $key => $section) {
    Route::get($section['path'], SectionPlaceholderController::class)
        ->defaults('section', $key)
        ->name($section['route']);
}

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
