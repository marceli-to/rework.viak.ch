<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Message> */
class MessageFactory extends Factory
{
	protected $model = Message::class;

	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'event_id' => Event::factory(),
			'user_id' => User::factory(),
			'subject' => fake()->sentence(4),
			'body' => fake()->paragraph(),
		];
	}
}
