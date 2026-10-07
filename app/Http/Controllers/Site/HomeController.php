<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use Illuminate\View\View;

/**
 * The homepage, as far as it is built ([[04-content]]). It is meant to be
 * assembled last from the partials the other pages need. Built so far:
 * legacy's intro and footer, the Vorhaben tiles (the review's marker 1), the
 * call band (2, static copy in the view), the next course dates (3) and the
 * Firmenschulung teaser (4).
 */
class HomeController extends Controller
{
	private const EVENTS = 6;

	public function __invoke(): View
	{
		$page = Page::for('home');

		return view('site.landing.index', [
			/*
			 * The intro's slider: legacy's home hero images, in their order,
			 * less the one kept for `og:image` ([[Page]], `port:media`).
			 */
			'slides' => $page->media->filter->isImage()->reject(fn (Media $media) => $media->is_og)->values(),
			'og' => $page->openGraph(),
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
			/*
			 * The Firmenschulung teaser's image (marker 4): the one the
			 * dashboard gave that page, its *Vorschau* if it has one.
			 */
			'trainingImage' => Page::for('firmenschulung')->teaser(),
		]);
	}
}
