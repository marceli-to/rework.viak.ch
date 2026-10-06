<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\View\View;

/**
 * *Über uns* ([[04-content]]): the Team mockup's three parts, in its order — the
 * Über uns text, the experts, the team. The experts are legacy's Experten page
 * as it was, so who is listed and in what order has not changed.
 */
class AboutController extends Controller
{
	public function __invoke(): View
	{
		return view('site.about.index', [
			'experts' => User::query()
				->publiclyListedExperts()
				->with(['media', 'eventsAsExpert' => ExpertController::teaching(...)])
				->get(),
			'team' => TeamMember::query()->published()->ordered()->with('media')->get(),
		]);
	}
}
