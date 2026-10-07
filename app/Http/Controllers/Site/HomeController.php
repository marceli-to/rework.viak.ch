<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Project;
use Illuminate\View\View;

/**
 * The homepage, as far as it is built ([[04-content]]). It is meant to be
 * assembled last from the partials the other pages need. Built so far: the
 * Vorhaben tiles (the review's marker 1), the call band (2, static copy in
 * the view) and the next course dates (3).
 */
class HomeController extends Controller
{
	private const EVENTS = 6;

	public function __invoke(): View
	{
		return view('site.home', [
			'projects' => Project::query()->published()->ordered()->get(),
			/*
			 * *Nächste Kurstermine* (marker 3, "Autom. Widget"): the next six
			 * Veranstaltungen anyone can book, by their first day. Published
			 * and not cancelled, of a published course, as the course page
			 * lists them.
			 */
			'events' => Event::query()
				->published()
				->active()
				->upcoming()
				->whereHas('course', fn ($query) => $query->published())
				->with(['course', 'dates', 'location', 'experts'])
				->limit(self::EVENTS)
				->get(),
		]);
	}
}
