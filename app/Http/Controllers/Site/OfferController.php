<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Angebote;
use Illuminate\View\View;

/**
 * *Alle Angebote*, `/de/angebote` ([[14-angebote]]): everything VIAK offers,
 * one section per category, a small tile per Angebot (the client,
 * 2026-10-29, after the mockup's `Alle-Angebote.html`). The sections are
 * [[Angebote]]'s, which `/llms.txt` lists too.
 */
class OfferController extends Controller
{
	public function index(): View
	{
		return view('site.offers.index', ['sections' => Angebote::sections()]);
	}
}
