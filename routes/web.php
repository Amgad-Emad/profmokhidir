<?php

use App\Constants\Sections;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\CommentaryController;
use App\Http\Controllers\CvController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OtherItemController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\SectionPlaceholderController;
use App\Http\Controllers\StyleguideController;
use App\Http\Controllers\TeachingMaterialController;
use App\Http\Controllers\TributeController;
use App\Http\Middleware\EnsureLocalEnvironment;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Sections with their own controller. Each keeps the route name from Sections::definitions();
// detail routes nest under the same name (e.g. commentary.show).
$sections = Sections::definitions();

Route::get($sections[Sections::ABOUT]['path'], AboutController::class)->name($sections[Sections::ABOUT]['route']);
Route::get($sections[Sections::CV]['path'], CvController::class)->name($sections[Sections::CV]['route']);
Route::get($sections[Sections::PUBLICATIONS]['path'], PublicationController::class)->name($sections[Sections::PUBLICATIONS]['route']);
Route::get($sections[Sections::AWARDS]['path'], AwardController::class)->name($sections[Sections::AWARDS]['route']);

Route::get($sections[Sections::TEACHING]['path'], [TeachingMaterialController::class, 'index'])->name($sections[Sections::TEACHING]['route']);
Route::get($sections[Sections::TEACHING]['path'].'/{material}/download', [TeachingMaterialController::class, 'download'])
    ->whereNumber('material')
    ->name($sections[Sections::TEACHING]['route'].'.download');

Route::get($sections[Sections::COMMENTARY]['path'], [CommentaryController::class, 'index'])->name($sections[Sections::COMMENTARY]['route']);
Route::get($sections[Sections::COMMENTARY]['path'].'/{slug}', [CommentaryController::class, 'show'])->name($sections[Sections::COMMENTARY]['route'].'.show');

Route::get($sections[Sections::OTHER]['path'], [OtherItemController::class, 'index'])->name($sections[Sections::OTHER]['route']);
Route::get($sections[Sections::OTHER]['path'].'/{slug}', [OtherItemController::class, 'show'])->name($sections[Sections::OTHER]['route'].'.show');

Route::get($sections[Sections::TRIBUTES]['path'], [TributeController::class, 'index'])->name($sections[Sections::TRIBUTES]['route']);
Route::get($sections[Sections::TRIBUTES]['path'].'/new', [TributeController::class, 'create'])->name($sections[Sections::TRIBUTES]['route'].'.create');
Route::post($sections[Sections::TRIBUTES]['path'], [TributeController::class, 'store'])->name($sections[Sections::TRIBUTES]['route'].'.store');
Route::get($sections[Sections::TRIBUTES]['path'].'/thank-you', [TributeController::class, 'thanks'])->name($sections[Sections::TRIBUTES]['route'].'.thanks');

$built = [Sections::ABOUT, Sections::CV, Sections::PUBLICATIONS, Sections::AWARDS, Sections::TEACHING, Sections::COMMENTARY, Sections::OTHER, Sections::TRIBUTES];

// One placeholder page per remaining public section, named after the section, until its
// own controller exists.
foreach (array_diff_key($sections, array_flip($built)) as $key => $section) {
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
