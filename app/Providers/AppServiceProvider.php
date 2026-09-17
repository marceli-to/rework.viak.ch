<?php

namespace App\Providers;

use App\Support\Accounting\AccountingSystem;
use App\Support\Accounting\FakeAccountingSystem;
use App\Support\Accounting\RunMyAccounts;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 */
	public function register(): void
	{
		$this->registerAccountingSystem();
	}

	/**
	 * Binds the bookkeeping client ([[AccountingSystem]]).
	 *
	 * The fake is the default and the real client is the exception, not the
	 * other way round: **nothing may be posted to VIAK's live accounting from
	 * anywhere but production.** Both conditions are checked here — the
	 * environment and the credentials — so neither a stray key in a local .env
	 * nor a production deploy with an empty config can do the wrong thing
	 * quietly. Missing credentials in production make [[RunMyAccounts]] throw,
	 * which is the correct failure: an invoice the customer has and the books
	 * do not is worse than an error.
	 */
	private function registerAccountingSystem(): void
	{
		$this->app->singleton(AccountingSystem::class, function ($app): AccountingSystem {
			$config = config('services.run_my_accounts');

			if (! $app->isProduction()) {
				return new FakeAccountingSystem;
			}

			return new RunMyAccounts(
				baseUrl: (string) $config['base_url'],
				apiKey: (string) $config['key'],
				createPath: (string) $config['create_path'],
				statusPath: (string) $config['status_path'],
				prefix: (string) $config['prefix'],
			);
		});
	}

	/**
	 * Bootstrap any application services.
	 */
	public function boot(): void
	{
		//
	}
}
