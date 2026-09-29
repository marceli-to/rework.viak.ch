<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Exports\CourseParticipantsExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * *Exporte* ([[07-dashboard]], step 7) — legacy's `ExportController`, which sat
 * at `/export/kurs-liste` outside the dashboard's API.
 */
class ExportController extends Controller
{
	/** `viak-kurse-29.09.2026.xlsx`, legacy's name without its cache-busting suffix. */
	public function courses(CourseParticipantsExport $export): Response
	{
		return response($export->xlsx(), 200, [
			'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'Content-Disposition' => 'attachment; filename="viak-kurse-'.now()->format('d.m.Y').'.xlsx"',
			'Cache-Control' => 'private, no-store',
		]);
	}
}
