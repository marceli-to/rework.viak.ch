<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\View\View;

/**
 * One Vorhaben ([[04-content]]): the title and text the review asked for
 * (Räume marker 3) and the courses the dashboard picked, published only, in
 * the dashboard's order.
 */
class ProjectController extends Controller
{
	public function show(string $slug): View
	{
		$project = Project::query()
			->published()
			->where('slug->'.app()->getLocale(), $slug)
			->firstOrFail();

		return view('site.projects.show', [
			'project' => $project,
			// What `x-card.course` reads, eager-loaded as the Kurse page does.
			'courses' => $project->courses()
				->published()
				->with([
					'categories',
					'media',
					'events' => fn ($query) => $query->published()->active()->upcoming()->with('experts'),
				])
				->get(),
		]);
	}
}
