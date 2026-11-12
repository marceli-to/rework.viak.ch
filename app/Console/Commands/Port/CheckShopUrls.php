<?php

declare(strict_types=1);

namespace App\Console\Commands\Port;

use App\Support\ShopRedirects;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * The old shop's redirects, checked ([[ShopRedirects]], `.rework/Deploy.md`):
 * every 3d-software.ch path the client listed (2026-11-12), resolved as the
 * redirect would resolve it now, and the page it lands on asked for.
 *
 * **Fails** when a target does not answer 200 directly: the redirect is one
 * hop, so its target must be the page itself. **Lists** every path that
 * missed its first choice, the product an old article number no longer
 * finds (Rhino 9 until VIAK adds it): those land on their Angebot, which
 * works, but is VIAK's to decide.
 *
 * `php artisan port:check-shop-urls --base=https://staging.viak.ch`
 */
class CheckShopUrls extends Command
{
	protected $signature = 'port:check-shop-urls
		{--base= : The rework to ask; the app URL when left out}
		{--all : List every path, not only the misses}';

	protected $description = 'Resolve every old 3d-software.ch URL and check the page it redirects to';

	public function handle(): int
	{
		$base = rtrim((string) ($this->option('base') ?: config('app.url')), '/');
		$checked = [];
		$rows = [];
		$failed = 0;
		$fallbacks = 0;

		foreach (array_keys(ShopRedirects::map()) as $path) {
			$resolved = ShopRedirects::resolve($path);
			$target = $resolved['target'];
			$status = $checked[$target] ??= $this->status($base.$target);
			$ok = $status === 200;

			$failed += $ok ? 0 : 1;
			$fallbacks += $resolved['fallback'] ? 1 : 0;

			if ($this->option('all') || ! $ok || $resolved['fallback']) {
				$rows[] = [$path, $target, $status ?: 'error', $resolved['fallback'] ? 'fallback (wanted '.strtok($this->first($path), '|').')' : '', $ok ? '' : 'FAIL'];
			}
		}

		if ($rows !== []) {
			$this->table(['old path', 'goes to', 'status', '', ''], $rows);
		}

		$this->line(count(ShopRedirects::map()).' paths, '.count($checked).' pages, '.$fallbacks.' on a fallback.');

		if ($failed > 0) {
			$this->error("{$failed} old URL(s) would not land on a page.");

			return self::FAILURE;
		}

		$this->info('Every old URL lands on a page in one hop.');

		return self::SUCCESS;
	}

	private function first(string $path): string
	{
		return ShopRedirects::map()[$path];
	}

	private function status(string $url): int
	{
		try {
			return Http::withoutRedirecting()->withoutVerifying()->timeout(20)->get($url)->status();
		} catch (Throwable) {
			return 0;
		}
	}
}
