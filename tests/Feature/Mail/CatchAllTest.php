<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

/**
 * **Nothing reaches a real inbox outside production** ([[10-mail]]). The
 * database holds VIAK's real customers, so this guard going missing would
 * mail them from a prototype. Sent for real through the `array` mailer, not
 * `Mail::fake()`, which would not show where a mail actually went.
 */
function sentTo(): array
{
	$message = app('mailer')->getSymfonyTransport()->messages()->last()->getOriginalMessage();

	return array_map(fn ($address) => $address->getAddress(), $message->getTo());
}

it('sends every mail to the catch-all instead of its recipient', function () {
	Mail::raw('Hallo', fn (Message $mail) => $mail->to('kunde@example.com')->subject('Test'));

	expect(sentTo())->toBe([config('mail.catch_all') ?: 'catch-all@viak.test']);
});

it('falls back to an address that reaches nobody when none is set', function () {
	config(['mail.catch_all' => null]);
	app()->forgetInstance('mail.manager');
	app()->forgetInstance('mailer');
	Mail::clearResolvedInstances();
	(new AppServiceProvider(app()))->boot();

	Mail::raw('Hallo', fn (Message $mail) => $mail->to('kunde@example.com')->subject('Test'));

	expect(sentTo())->toBe(['catch-all@viak.test']);
});
