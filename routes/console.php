<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
	$this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Nightly, and only in production: outside it the accounting system is a fake
 * with nothing to report ([[AccountingSystem]]).
 */
Schedule::command('invoices:sync')->dailyAt('01:00')->environments('production');
