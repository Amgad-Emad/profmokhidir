<?php

use App\Constants\Sections;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SectionPlaceholderController;
use App\Http\Controllers\StyleguideController;
use App\Http\Middleware\EnsureLocalEnvironment;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Sections with their own controller. Each keeps the route name from Sections::definitions().
$sections = Sections::definitions();

Route::get($sections[Sections::ABOUT]['path'], AboutController::class)->name($sections[Sections::ABOUT]['route']);

// One placeholder page per remaining public section, named after the section, until its
// own controller exists. Detail routes nest under the same name (e.g. publications.show).
foreach (array_diff_key($sections, array_flip([Sections::ABOUT])) as $key => $section) {
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
