<?php

declare(strict_types=1);

use App\Http\Controllers\Site\CourseController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'site.home')->name('home');

Route::get('/kurse', [CourseController::class, 'index'])->name('courses.index');
Route::get('/kurse/{slug}', [CourseController::class, 'show'])->name('courses.show');

// SPA shell — the dashboard router takes over client-side.
Route::view('/dashboard/{any?}', 'components.layout.app')
	->where('any', '.*')
	->name('dashboard');
