<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Enums\Gender;
use App\Enums\OperatingSystem;
use App\Enums\Role;
use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Signing up ([[08-accounts]]).
 *
 * The replacement for legacy's `StudentRegisterController`, and the field list
 * is legacy's form exactly: salutation, name, company, phone, address, email
 * twice, password twice, which machines you work on, the terms, the newsletter.
 *
 * Three things legacy does that are worth keeping, and one that is not:
 *
 * - **The email is typed twice and paste is blocked on the second.** A typo
 *   here is an account nobody can recover, because every reset goes to the
 *   address that was mistyped.
 * - **Accepting the terms is required**, and it is not stored — legacy has no
 *   column for it and neither does this. It gates the form, nothing more.
 * - **At least one operating system.** It is what tells VIAK whether a rented
 *   laptop is any use to this student.
 * - Legacy signs the new user in **without verifying the address first**, then
 *   mails a verification link that nothing enforces. Here `MustVerifyEmail` is
 *   on the model and Fortify's `verified` middleware is what enforces it.
 */
class RegisterUser implements CreatesNewUsers
{
	/** @param  array<string, mixed>  $input */
	public function create(array $input): User
	{
		Validator::make($input, [
			'gender' => ['required', 'string', 'in:'.implode(',', array_column(Gender::cases(), 'value'))],
			'first_name' => ['required', 'string', 'max:255'],
			'last_name' => ['required', 'string', 'max:255'],
			'company' => ['nullable', 'string', 'max:255'],
			'phone' => ['required', 'string', 'max:30'],
			'street' => ['required', 'string', 'max:255'],
			'street_no' => ['nullable', 'string', 'max:5'],
			'zip' => ['required', 'string', 'max:10'],
			'city' => ['required', 'string', 'max:255'],
			'country_code' => ['required', 'string', 'exists:countries,code'],
			'email' => ['required', 'string', 'email', 'max:255', 'confirmed', 'unique:users,email'],
			'password' => ['required', 'string', 'confirmed', 'min:8'],
			'operating_systems' => ['required', 'array', 'min:1'],
			'operating_systems.*' => ['string', 'in:'.implode(',', array_column(OperatingSystem::cases(), 'value'))],
			'accept_tos' => ['accepted'],
			'subscribe_newsletter' => ['nullable', 'boolean'],
		], [
			'email.confirmed' => 'Die beiden E-Mail-Adressen stimmen nicht überein.',
			'password.confirmed' => 'Die beiden Passwörter stimmen nicht überein.',
			'accept_tos.accepted' => 'Bitte akzeptieren Sie die AGB.',
			'operating_systems.required' => 'Bitte wählen Sie mindestens ein Betriebssystem.',
		])->validate();

		return DB::transaction(function () use ($input): User {
			$user = User::create([
				'gender' => Gender::from($input['gender']),
				'first_name' => $input['first_name'],
				'last_name' => $input['last_name'],
				'company' => $input['company'] ?? null,
				'phone' => $input['phone'],
				'street' => $input['street'],
				'street_no' => $input['street_no'] ?? null,
				'zip' => $input['zip'],
				'city' => $input['city'],
				'country_code' => $input['country_code'],
				'email' => $input['email'],
				'password' => Hash::make($input['password']),
				'operating_systems' => array_map(
					fn (string $value) => OperatingSystem::from($value),
					$input['operating_systems'],
				),
				'subscribe_newsletter' => (bool) ($input['subscribe_newsletter'] ?? false),
			]);

			// Everyone who signs up here is a student. Expert and admin are
			// granted in the dashboard, never claimed at registration.
			DB::table('role_user')->insert([
				'user_id' => $user->id,
				'role' => Role::Student->value,
			]);

			return $user;
		});
	}

	/** @return array<string, string> the country list the form offers, code => name */
	public static function countries(): array
	{
		return Country::query()->orderBy('order')->orderBy('name')->pluck('name', 'code')->all();
	}
}
