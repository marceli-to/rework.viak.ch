<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTruncation;
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

/*
 * The site search ([[15-search]]) commits, because InnoDB's FULLTEXT index
 * does not see rows a transaction has not committed: inside
 * `RefreshDatabase`'s transaction every search would find nothing. So these
 * tables are truncated after each test instead, which is slower, and why the
 * suite is its own and small.
 */
pest()->extend(TestCase::class)
	->use(DatabaseTruncation::class)
	->in('Search');
