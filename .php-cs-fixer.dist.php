<?php

declare(strict_types=1);

/**
 * Code style for the rework. Tab-indented per .editorconfig (indent_style = tab) —
 * the reason we use PHP-CS-Fixer here instead of Laravel Pint, which hardcodes
 * 4-space indentation and exposes no tab option.
 *
 * Run:  vendor/bin/php-cs-fixer fix
 * Check: vendor/bin/php-cs-fixer fix --dry-run --diff
 */
$finder = PhpCsFixer\Finder::create()
	->in([
		__DIR__.'/app',
		__DIR__.'/bootstrap',
		__DIR__.'/config',
		__DIR__.'/database',
		__DIR__.'/routes',
		__DIR__.'/tests',
	])
	->exclude('cache') // bootstrap/cache — framework-generated, never hand-formatted
	->name('*.php')
	->notName('*.blade.php')
	->ignoreDotFiles(true)
	->ignoreVCS(true);

return (new PhpCsFixer\Config())
	->setIndent("\t")
	->setLineEnding("\n")
	->setRules([
		'@PSR12' => true,
		'array_syntax' => ['syntax' => 'short'],
		'array_indentation' => true,
		'binary_operator_spaces' => true,
		'blank_line_after_opening_tag' => true,
		'fully_qualified_strict_types' => true,
		'method_chaining_indentation' => true,
		'no_extra_blank_lines' => true,
		'no_unused_imports' => true,
		'not_operator_with_successor_space' => true,
		'ordered_imports' => ['sort_algorithm' => 'alpha'],
		'single_quote' => true,
		'trailing_comma_in_multiline' => true,
	])
	->setFinder($finder);
