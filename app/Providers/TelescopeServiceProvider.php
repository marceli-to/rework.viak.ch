<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

/**
 * Telescope, **local only** ([[10-mail]], layer 4): every request and command
 * with the events it fired, the listeners that ran, the jobs it queued and the
 * mails that went out. It answers *"I confirmed the date, why is there no
 * invoice mail?"* without guessing. At `/telescope` (https://rework.viak.ch.test/telescope).
 *
 * A dev dependency, kept out of package discovery (`composer.json`) and
 * registered by [[AppServiceProvider]] only when `app()->isLocal()`, so neither
 * production nor the test suite ever loads it. Its table comes from the
 * package's own migration, loaded here rather than published, for the same
 * reason: production gets no `telescope_entries`.
 *
 * Records everything, since it only ever runs on a laptop. `telescope:clear`
 * empties it.
 */
class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
	public function register(): void
	{
		$this->loadMigrationsFrom(base_path('vendor/laravel/telescope/database/migrations'));
	}

	/** Nobody, anywhere but local, where Telescope lets everyone in regardless. */
	protected function gate(): void
	{
		Gate::define('viewTelescope', fn () => false);
	}
}
