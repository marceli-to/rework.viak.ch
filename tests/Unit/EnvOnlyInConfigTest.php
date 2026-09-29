<?php

declare(strict_types=1);

/**
 * **`env()` is read in `config/` and nowhere else** ([[00-foundation]]).
 *
 * Once the config is cached, `env()` returns null outside `config/`. Legacy
 * read its sender and its office address that way in all 24 mailables and
 * three tasks, so caching its config would send mail from nobody to nobody
 * (`Todo.md`). This keeps the rework cacheable: `config('mail.from')`,
 * `config('mail.admin')`.
 */
it('reads env() only in config/', function () {
	$root = dirname(__DIR__, 2);
	$offenders = [];

	foreach (['app', 'bootstrap', 'database', 'routes', 'resources/views', 'lang'] as $dir) {
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$dir}", FilesystemIterator::SKIP_DOTS));

		foreach ($files as $file) {
			if ($file->getExtension() !== 'php' || str_contains($file->getPathname(), '/bootstrap/cache/')) {
				continue;
			}

			foreach (file($file->getPathname()) as $number => $line) {
				// A call, not a word in a comment or a method that ends in `env`.
				if (preg_match('/(?<![\w>:$])env\s*\(/', $line) && ! preg_match('/^\s*(\*|\/\/|#)/', $line)) {
					$offenders[] = str_replace("{$root}/", '', $file->getPathname()).':'.($number + 1);
				}
			}
		}
	}

	expect($offenders)->toBe([]);
});

it('knows where the office mail goes, from config', function () {
	config(['mail.admin' => 'office@example.test']);

	expect(config('mail.admin'))->toBe('office@example.test')
		->and(config('mail.from.address'))->not->toBeNull();
});
