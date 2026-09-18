<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/** @extends Factory<UserDocument> */
class UserDocumentFactory extends Factory
{
	protected $model = UserDocument::class;

	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'user_id' => User::factory(),
			'type' => DocumentType::Invoice,
			'filename' => 'viak-rechnung-'.fake()->unique()->numerify('######').'.pdf',
			'date' => now()->toDateString(),
		];
	}

	/** Writes a file to match, for the routes that stream one. */
	public function onDisk(): static
	{
		return $this->afterCreating(function (UserDocument $document) {
			Storage::disk('documents')->put($document->path(), '%PDF-1.4 test');
		});
	}
}
