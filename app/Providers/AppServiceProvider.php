<?php

namespace App\Providers;

use App\Support\Accounting\AccountingSystem;
use App\Support\Accounting\FakeAccountingSystem;
use App\Support\Accounting\RunMyAccounts;
use App\Support\Mcp\McpAccess;
use App\Support\Newsletter\FakeNewsletterList;
use App\Support\Newsletter\Mailchimp;
use App\Support\Newsletter\NewsletterList;
use App\Support\Payments\FakePaymentGateway;
use App\Support\Payments\PaymentGateway;
use App\Support\Payments\StripeGateway;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 */
	public function register(): void
	{
		$this->registerAccountingSystem();
		$this->registerPaymentGateway();
		$this->registerNewsletterList();
		$this->registerTelescopeLocally();
	}

	/**
	 * Telescope on a laptop and nowhere else ([[TelescopeServiceProvider]]).
	 * `class_exists` because it is a dev dependency: a `--no-dev` install has
	 * no such class, and must not need one.
	 */
	private function registerTelescopeLocally(): void
	{
		if ($this->app->isLocal() && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
			$this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
			$this->app->register(TelescopeServiceProvider::class);
		}
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
				paymentPath: (string) $config['payment_path'],
				prefix: (string) $config['prefix'],
			);
		});
	}

	/**
	 * Binds the newsletter list ([[NewsletterList]]): Mailchimp in production,
	 * the fake everywhere else, whatever the .env holds. Staging's database
	 * holds real customers, and the list is VIAK's live audience.
	 */
	private function registerNewsletterList(): void
	{
		$this->app->singleton(NewsletterList::class, function ($app): NewsletterList {
			if (! $app->isProduction()) {
				return new FakeNewsletterList;
			}

			return new Mailchimp(
				apiKey: (string) config('services.mailchimp.key'),
				listId: (string) config('services.mailchimp.list_id'),
				tag: (string) config('services.mailchimp.tag'),
			);
		});
	}

	/**
	 * Binds card payment ([[PaymentGateway]]), on the accounting system's
	 * principle: **no real money moves from a prototype.**
	 *
	 * - no key, outside production: the local fake ([[FakePaymentGateway]]);
	 * - a key: Stripe, but a **live** key only in production. A test key
	 *   moves no money, so a laptop may use one to try Stripe's own page;
	 * - production without a key throws, rather than handing out the fake,
	 *   whose *Bezahlen* button would settle invoices for nothing.
	 */
	private function registerPaymentGateway(): void
	{
		$this->app->singleton(PaymentGateway::class, function ($app): PaymentGateway {
			$secret = (string) config('services.stripe.secret');

			if ($secret === '') {
				if ($app->isProduction()) {
					throw new RuntimeException('Stripe is not configured. Refusing to take card payments with the fake.');
				}

				return new FakePaymentGateway;
			}

			if (! $app->isProduction() && ! str_starts_with($secret, 'sk_test_')) {
				throw new RuntimeException('Only a Stripe test key (sk_test_…) may be used outside production.');
			}

			return new StripeGateway($secret, (string) config('services.stripe.webhook_secret'));
		});
	}

	/**
	 * Bootstrap any application services.
	 */
	public function boot(): void
	{
		$this->catchMailOutsideProduction();

		// A token opens `/mcp` and nothing else ([[McpAccess]]).
		Sanctum::authenticateAccessTokensUsing(
			fn (PersonalAccessToken $token, bool $isValid): bool => McpAccess::admits($token, $isValid, request()),
		);
	}

	/**
	 * **Outside production, every mail goes to one address** ([[10-mail]]).
	 *
	 * The ported database holds VIAK's real students and experts, so a
	 * prototype that mails from it would mail VIAK's customers. Same rule as
	 * the accounting system above: mocked until cutover. Legacy had it twice
	 * (`Mail::alwaysTo(env('MAIL_TO'))` and `Tasks/Job` swapping recipients);
	 * here it is once, for mailables and notifications alike, in every process,
	 * the queue worker's included.
	 *
	 * With no `MAIL_CATCH_ALL` set, mail goes to an address under `.test`,
	 * which by definition reaches nobody: forgetting the setting cannot mail a
	 * customer. Locally the mailer points at MailHog as well.
	 */
	private function catchMailOutsideProduction(): void
	{
		if ($this->app->isProduction()) {
			return;
		}

		Mail::alwaysTo(config('mail.catch_all') ?: 'catch-all@viak.test');
	}
}
