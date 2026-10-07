<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\View\View;

/**
 * The homepage, as far as it is built ([[04-content]]). It is meant to be
 * assembled last from the partials the other pages need; the Vorhaben tiles
 * are the first of them (the review's homepage marker 1).
 */
class HomeController extends Controller
{
	public function __invoke(): View
	{
		return view('site.home', [
			'projects' => Project::query()->published()->ordered()->get(),
		]);
	}
}
