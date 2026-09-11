<?php

declare(strict_types=1);

use App\Support\Slug;

it('builds a slug per locale', function () {
	expect(Slug::forTitles(['de' => 'Blender Einführungskurs', 'en' => 'Blender Intro Course']))
		->toBe(['de' => 'blender-einfuhrungskurs', 'en' => 'blender-intro-course']);
});

it('falls back to the first available title for a missing locale', function () {
	expect(Slug::forTitles(['de' => 'Nur Deutsch']))
		->toBe(['de' => 'nur-deutsch', 'en' => 'nur-deutsch']);
});

it('accepts a plain string as the german title', function () {
	expect(Slug::forTitles('Rhino Grundkurs'))->toHaveKey('de', 'rhino-grundkurs');
});
