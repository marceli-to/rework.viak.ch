<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
	protected static ?string $password;

	/** @return array<string, mixed> */
	public function definition(): array
	{
		return [
			'name' => fake()->name(),
			'email' => fake()->unique()->safeEmail(),
			'email_verified_at' => now(),
			'password' => static::$password ??= Hash::make('password'),
			'remember_token' => Str::random(10),
		];
	}

	public function unverified(): static
	{
		return $this->state(fn (array $attributes) => [
			'email_verified_at' => null,
		]);
	}

	public function admin(): static
	{
		return $this->withRole(Role::Admin);
	}

	public function expert(): static
	{
		return $this->withRole(Role::Expert);
	}

	public function student(): static
	{
		return $this->withRole(Role::Student);
	}

	/**
	 * Roles live in a pivot, so they are attached once the user exists.
	 * Chainable — `User::factory()->admin()->expert()` is a real combination,
	 * and one that exists in production.
	 */
	private function withRole(Role $role): static
	{
		return $this->afterCreating(function (User $user) use ($role): void {
			DB::table('role_user')->insertOrIgnore([
				'user_id' => $user->id,
				'role' => $role->value,
			]);
		});
	}
}
