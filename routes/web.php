<?php

declare(strict_types=1);

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\Site\CourseController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'site.home')->name('home');

/*
 * On-demand image rendering ([[08-accounts]]). Parameters are clamped to the
 * sizes and formats `<x-media.image>` would have produced, so the resizer cannot
 * be used to fill the cache disk.
 */
Route::get('/img/{path}', [ImageController::class, 'show'])
	->where('path', '.*')
	->name('image');

Route::get('/kurse', [CourseController::class, 'index'])->name('courses.index');
Route::get('/kurse/{slug}', [CourseController::class, 'show'])->name('courses.show');

/*
 * Generated PDFs, behind the session guard and a policy ([[08-accounts]]).
 * Legacy served these straight off the public disk.
 */
Route::get('/dokumente/{document}', [DocumentController::class, 'show'])
	->middleware('auth')
	->name('documents.show');

// SPA shell — the dashboard router takes over client-side.
Route::view('/dashboard/{any?}', 'components.layout.app')
	->where('any', '.*')
	->name('dashboard');
