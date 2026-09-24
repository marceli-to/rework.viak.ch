<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Third Party Services
	|--------------------------------------------------------------------------
	|
	| This file is for storing the credentials for third party services such
	| as Resend, Postmark, AWS, and more. This file provides the de facto
	| location for this type of information, allowing packages to have
	| a conventional file to locate the various service credentials.
	|
	*/

	/*
	 * The map on the Kontakt page ([[09-public-site]]). Legacy's variable name,
	 * unchanged, so the production .env carries across as it is. Empty locally:
	 * the key is the client's and restricted to the live site, and without one
	 * the page draws a grey box of the map's size instead.
	 */
	'google_maps' => [
		'key' => env('GOOGLEMAPS_APIKEY'),
	],

	'postmark' => [
		'key' => env('POSTMARK_API_KEY'),
	],

	'resend' => [
		'key' => env('RESEND_API_KEY'),
	],

	/*
	 * Run My Accounts — VIAK's books ([[AccountingSystem]]).
	 *
	 * Deliberately empty everywhere but production. The service provider hands
	 * out the fake client unless all of this is present AND the environment is
	 * production, so a prototype cannot write into the client's live
	 * accounting. Do not put these keys in a local .env to "test the real
	 * thing"; the fake records what would have been posted.
	 */
	'run_my_accounts' => [
		'base_url' => env('RMA_API_BASE', ''),
		'key' => env('RMA_API_KEY', ''),
		'create_path' => env('RMA_API_CREATE', ''),
		'status_path' => env('RMA_API_STATUS', ''),
		'prefix' => env('RMA_INVOICE_PREFIX', 'VIAK_'),
	],

	'ses' => [
		'key' => env('AWS_ACCESS_KEY_ID'),
		'secret' => env('AWS_SECRET_ACCESS_KEY'),
		'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
	],

	'slack' => [
		'notifications' => [
			'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
			'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
		],
	],

];
