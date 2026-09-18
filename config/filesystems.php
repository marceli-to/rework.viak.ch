<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Default Filesystem Disk
	|--------------------------------------------------------------------------
	|
	| Here you may specify the default filesystem disk that should be used
	| by the framework. The "local" disk, as well as a variety of cloud
	| based disks are available to your application for file storage.
	|
	*/

	'default' => env('FILESYSTEM_DISK', 'local'),

	/*
	|--------------------------------------------------------------------------
	| Filesystem Disks
	|--------------------------------------------------------------------------
	|
	| Below you may configure as many filesystem disks as necessary, and you
	| may even configure multiple disks for the same driver. Examples for
	| most supported storage drivers are configured here for reference.
	|
	| Supported drivers: "local", "ftp", "sftp", "s3"
	|
	*/

	'disks' => [

		'local' => [
			'driver' => 'local',
			'root' => storage_path('app/private'),
			'serve' => true,
			'throw' => false,
			'report' => false,
		],

		'public' => [
			'driver' => 'local',
			'root' => storage_path('app/public'),
			'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
			'visibility' => 'public',
			'throw' => false,
			'report' => false,
		],

		/*
		 * Generated PDFs — invoices and participation confirmations
		 * ([[08-accounts]]).
		 *
		 * Deliberately **not** under `storage/app/public`, which is symlinked
		 * into the web root. Legacy put them there and stored the public path on
		 * the row, so all 1,162 documents are fetchable without authenticating,
		 * protected only by the uuid in the path being unguessable. Here they
		 * are served by a route with a policy behind it, and the disk cannot be
		 * reached any other way.
		 */
		'documents' => [
			'driver' => 'local',
			'root' => storage_path('app/documents'),
			'serve' => false,
			'throw' => false,
			'report' => false,
		],

		/*
		 * Read-only snapshot of the legacy site's `storage/app/public`, used by
		 * the port commands the way the `legacy` database connection is. Never
		 * written to.
		 *
		 * It holds `files/` (generated invoice and participation PDFs, plus the
		 * loose participant lists) and `uploads/` (the images and file uploads).
		 * The image port cannot run without it: `media` needs width and height,
		 * and legacy's `images` table never stored them. See [[08-accounts]].
		 *
		 * Date the snapshot directory. Reconciling documents against a copy taken
		 * at a different moment from the database dump is what produced the
		 * unexplainable 495 in the first place.
		 */
		'legacy' => [
			'driver' => 'local',
			'root' => env('LEGACY_STORAGE_PATH', base_path('../viak-legacy-storage/current')),
			'throw' => false,
			'report' => false,
		],

		's3' => [
			'driver' => 's3',
			'key' => env('AWS_ACCESS_KEY_ID'),
			'secret' => env('AWS_SECRET_ACCESS_KEY'),
			'region' => env('AWS_DEFAULT_REGION'),
			'bucket' => env('AWS_BUCKET'),
			'url' => env('AWS_URL'),
			'endpoint' => env('AWS_ENDPOINT'),
			'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
			'throw' => false,
			'report' => false,
		],

	],

	/*
	|--------------------------------------------------------------------------
	| Symbolic Links
	|--------------------------------------------------------------------------
	|
	| Here you may configure the symbolic links that will be created when the
	| `storage:link` Artisan command is executed. The array keys should be
	| the locations of the links and the values should be their targets.
	|
	*/

	'links' => [
		public_path('storage') => storage_path('app/public'),
	],

];
