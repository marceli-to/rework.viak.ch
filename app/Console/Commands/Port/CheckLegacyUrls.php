<?php

declare(strict_types=1);

namespace App\Console\Commands\Port;

use App\Models\User;
use App\Support\SiteUrl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * The redirect map, checked rather than written down ([[09-public-site]],
 * `Todo.md` *The redirect map*, `.rework/Deploy.md`).
 *
 * Builds every URL legacy answers from the legacy database: the fixed pages,
 * each course and each listed expert the way legacy links them, the PDFs under
 * `/media/downloads`, some images under `/storage/uploads`, and a card-payment
 * link as old invoice mails carry it. Then asks the rework for each, follows
 * up to five redirects, and says where it lands.
 *
 * **A URL legacy answers must land on a 200 here**, by one 301 or directly
 * (a chain fails since the courses moved under `/de/angebote`, 2026-10-29). A page
 * behind a login landing on the login page counts. Two groups are reported
 * and not failed: courses legacy does not publish (its `show` serves any
 * course, published or not; the rework 404s them, and nothing links to them)
 * and old mail links that expired by design.
 *
 * Run it against the server being switched over, before the DNS moves:
 * `php artisan port:check-urls --base=https://staging.example`.
 */
class CheckLegacyUrls extends Command
{
	protected $signature = 'port:check-urls
		{--base= : The rework to ask; the app URL when left out}
		{--all : List every URL, not only the ones that did not land on a 200}';

	protected $description = 'Ask the rework for every URL legacy answers, and report where each lands';

	private const FIXED = [
		'/', '/de', '/de/kurse', '/de/kontakt', '/de/experten', '/de/individualschulungen',
		'/de/registration', '/register', '/login', '/password/reset',
		'/de/checkout/basket', '/de/student/profil', '/de/student/profil/dokumente',
		'/de/student/profil/adresse/erstellen', '/de/experte/profil',
		'/robots.txt', '/favicon.ico', '/apple-touch-icon.png', '/site.webmanifest',
		'/media/downloads/Visualisierungs-Akademie_AGB_Jul24.pdf',
		'/media/downloads/Visualisierungs-Akademie_Datenschutzerklaerung.pdf',
		'/media/downloads/Visualisierungs-Akademie_Datenschutzerklaerung_Feb23.pdf',
	];

	public function handle(): int
	{
		$base = rtrim((string) ($this->option('base') ?: config('app.url')), '/');
		$failed = 0;
		$rows = [];

		foreach ($this->urls() as [$group, $path, $mustLand]) {
			[$status, $landed, $hops] = $this->follow($base, $path);
			// One redirect at most: a chain costs ranking where a single 301
			// does not (`14-angebote.md`, *The nav, the URLs*). The login's
			// own redirect comes on top for a portal page, and is not indexed.
			$ok = $status === 200 && $hops <= ($landed === '/login' ? 2 : 1);

			if (! $ok && $mustLand) {
				$failed++;
			}

			if ($this->option('all') || ! $ok) {
				$rows[] = [$group, $path, $status ?: 'error', $hops ?: '', $landed === $path ? '' : $landed, $ok ? 'ok' : ($mustLand ? 'FAIL' : 'expected')];
			}
		}

		if ($rows !== []) {
			$this->table(['group', 'legacy URL', 'status', 'hops', 'lands on', ''], $rows);
		}

		if ($failed > 0) {
			$this->error("{$failed} legacy URL(s) do not land on a page in one hop.");

			return self::FAILURE;
		}

		$this->info('Every legacy URL that must survive lands on a page.');

		return self::SUCCESS;
	}

	/** @return array<int, array{0: string, 1: string, 2: bool}> */
	private function urls(): array
	{
		$legacy = DB::connection('legacy');
		$urls = array_map(fn (string $path) => ['page', $path, true], self::FIXED);

		foreach ($legacy->table('courses')->whereNull('deleted_at')->orderBy('id')->get(['uuid', 'slug', 'publish']) as $course) {
			$slug = json_decode((string) $course->slug, true)['de'] ?? (string) $course->slug;
			$urls[] = [$course->publish ? 'course' : 'course, unpublished', "/de/kurs/{$slug}/{$course->uuid}", (bool) $course->publish];
		}

		$experts = $legacy->table('users')
			->join('role_user', 'role_user.user_id', '=', 'users.id')
			->where('role_user.role_id', 2)
			->where('users.publish', 1)->where('users.visible', 1)->whereNull('users.deleted_at')
			->orderBy('users.id')->get(['users.uuid', 'users.firstname', 'users.name']);

		foreach ($experts as $expert) {
			$slug = SiteUrl::expertSlug(new User(['first_name' => $expert->firstname, 'last_name' => $expert->name]));
			$urls[] = ['expert', "/de/experte/{$slug}/{$expert->uuid}", true];
		}

		// Only the owners `port:media` carries; legacy's News images are not
		// ported (chunk 04 has no news).
		foreach ($legacy->table('images')->whereNull('deleted_at')
			->whereIn('imageable_type', ['App\\Models\\Course', 'App\\Models\\User', 'App\\Models\\Hero'])
			->orderByDesc('id')->limit(5)->pluck('name') as $image) {
			$urls[] = ['image', '/storage/uploads/'.$image, true];
		}

		if ($invoice = $legacy->table('invoices')->orderByDesc('id')->value('uuid')) {
			$urls[] = ['mail link', "/de/zahlung/rechnung/{$invoice}", true];
		}

		return $urls;
	}

	/** @return array{0: int, 1: string, 2: int} the final status and path, and how many redirects it took */
	private function follow(string $base, string $path): array
	{
		$current = $path;

		for ($hop = 0; $hop < 5; $hop++) {
			try {
				$response = Http::withoutRedirecting()->withoutVerifying()->timeout(20)->get($base.$current);
			} catch (Throwable) {
				return [0, $current, $hop];
			}

			if (! $response->redirect()) {
				return [$response->status(), $current, $hop];
			}

			$location = (string) $response->header('Location');
			$current = str_starts_with($location, $base) ? substr($location, strlen($base)) : $location;

			if ($current === '') {
				$current = '/';
			}
		}

		return [310, $current, 5];
	}
}
