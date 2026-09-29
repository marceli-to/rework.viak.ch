<?php

declare(strict_types=1);

namespace App\Console\Scenarios;

use Closure;

/**
 * One branch of the mail flow table, played step by step through the real
 * Actions ([[10-mail]], layer 3; [[PlayScenario]]). A step does one thing a
 * person would do and returns a line about it, or nothing; the command prints
 * the mails the step sent underneath.
 */
abstract class Scenario
{
	public function __construct(protected readonly Stage $stage) {}

	/** One line, for `scenario:play` without a name. */
	abstract public static function description(): string;

	/**
	 * In order. The first step usually builds the date.
	 *
	 * @return array<string, Closure(): ?string> title => step
	 */
	abstract public function steps(): array;
}
