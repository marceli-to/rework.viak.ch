<?php

declare(strict_types=1);

namespace App\Support\Newsletter;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Mailchimp, the real thing ([[NewsletterList]]). Marketing API 3.0 over
 * plain HTTP, where legacy went through `spatie/laravel-newsletter`.
 *
 * What legacy sent, unchanged: the member with `FNAME` and `LNAME`, then the
 * tags `MAILCHIMP_TAGS` (*VA Newsletter*) and *Deutsch*.
 */
class Mailchimp implements NewsletterList
{
	public function __construct(
		private readonly string $apiKey,
		private readonly string $listId,
		private readonly string $tag,
	) {
		if ($this->apiKey === '' || $this->listId === '' || ! str_contains($this->apiKey, '-')) {
			throw new RuntimeException('Mailchimp is not configured.');
		}
	}

	public function subscribe(string $email, string $firstName, string $lastName): void
	{
		$this->api()->put($this->member($email), [
			'email_address' => $email,
			'status' => 'subscribed',
			'status_if_new' => 'subscribed',
			'merge_fields' => ['FNAME' => $firstName, 'LNAME' => $lastName],
		])->throw();

		$tags = array_values(array_filter([$this->tag, 'Deutsch']));

		$this->api()->post($this->member($email).'/tags', [
			'tags' => array_map(fn (string $name) => ['name' => $name, 'status' => 'active'], $tags),
		])->throw();
	}

	public function unsubscribe(string $email): void
	{
		$response = $this->api()->patch($this->member($email), ['status' => 'unsubscribed']);

		// Never on the list: nothing to take off it.
		if ($response->notFound()) {
			return;
		}

		$response->throw();
	}

	private function api(): PendingRequest
	{
		// The key ends in its data centre: `…-us10` lives at us10.api.mailchimp.com.
		$dataCentre = substr($this->apiKey, strrpos($this->apiKey, '-') + 1);

		return Http::baseUrl("https://{$dataCentre}.api.mailchimp.com/3.0")
			->withBasicAuth('viak', $this->apiKey)
			->acceptJson()
			->timeout(10);
	}

	private function member(string $email): string
	{
		return "lists/{$this->listId}/members/".md5(mb_strtolower($email));
	}
}
