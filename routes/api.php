<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EventController;
use Illuminate\Support\Facades\Route;

/*
 * Public reads are open; writes sit behind the session guard and are gated
 * further by policy. The SPA and the public site consume the same endpoints —
 * never a parallel write path. See [[02-courses-events]].
 */

Route::get('courses', [CourseController::class, 'index']);
Route::get('courses/{course}', [CourseController::class, 'show']);
Route::get('events', [EventController::class, 'index']);
Route::get('events/{event}', [EventController::class, 'show']);

Route::middleware('auth:sanctum')->group(function (): void {
	Route::post('courses', [CourseController::class, 'store']);
	Route::put('courses/{course}', [CourseController::class, 'update']);
	Route::delete('courses/{course}', [CourseController::class, 'destroy']);

	Route::post('events', [EventController::class, 'store']);
	Route::put('events/{event}', [EventController::class, 'update']);
	Route::patch('events/{event}/state', [EventController::class, 'setState']);
	Route::delete('events/{event}', [EventController::class, 'destroy']);
});
