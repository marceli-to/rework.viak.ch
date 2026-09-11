<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * Tests run against MySQL rather than sqlite: the schema leans on JSON columns
 * and MySQL-specific behaviour, and a green suite on a different engine than
 * production proves less than it appears to.
 */
pest()->extend(TestCase::class)
	->use(RefreshDatabase::class)
	->in('Feature');

pest()->extend(TestCase::class)->in('Unit');
