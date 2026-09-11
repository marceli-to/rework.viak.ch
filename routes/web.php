<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::view('/', 'site.home')->name('home');

// SPA shell — the dashboard router takes over client-side.
Route::view('/dashboard/{any?}', 'components.layout.app')
	->where('any', '.*')
	->name('dashboard');
