<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Angebote;
use Illuminate\Http\Response;

/**
 * `/llms.txt` (llmstxt.org, 2026-10-30): what VIAK is, for AI assistants.
 * Plain Markdown, a few rules an answer about VIAK gets wrong easily, then
 * every Angebot by category ([[Angebote]], as on *Alle Angebote*) and the
 * fixed pages.
 *
 * New, not parity: legacy has none. The way follows Marcel's other projects
 * (gotaticket): a route and a view rather than a file in `public/`, so the
 * links follow the catalogue. They are on the canonical host, as the
 * sitemap's are ([[SitemapController]]).
 */
class LlmsController extends Controller
{
	public function __invoke(): Response
	{
		return response()
			->view('site.llms', ['sections' => Angebote::sections()])
			->header('Content-Type', 'text/markdown; charset=UTF-8');
	}
}
