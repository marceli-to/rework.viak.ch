<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Accounts\UpdateProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * *Mein Profil* on the dashboard ([[07-dashboard]], step 6): the signed-in
 * admin, through the portal's own [[UpdateProfile]], so a changed address
 * is confirmed again and a credential change asks for the password.
 */
class ProfileController extends Controller
{
	public function show(Request $request): JsonResponse
	{
		return $this->answer($request->user());
	}

	public function update(SaveProfileRequest $request, UpdateProfile $update): JsonResponse
	{
		$user = $update->execute(
			user: $request->user(),
			attributes: $request->profileAttributes(),
			email: $request->string('email')->value(),
			password: $request->filled('password') ? $request->string('password')->value() : null,
			currentPassword: $request->filled('current_password') ? $request->string('current_password')->value() : null,
		);

		return $this->answer($user);
	}

	/** The form's shape; the password fields always come back empty. */
	private function answer(User $user): JsonResponse
	{
		return response()->json(['data' => [
			'gender' => $user->gender?->value ?? '',
			'first_name' => $user->first_name,
			'last_name' => $user->last_name,
			'company' => $user->company ?? '',
			'phone' => $user->phone ?? '',
			'street' => $user->street ?? '',
			'street_no' => $user->street_no ?? '',
			'zip' => $user->zip ?? '',
			'city' => $user->city ?? '',
			'country' => $user->country_code ?? '',
			'email' => $user->email,
			'password' => '',
			'password_confirmation' => '',
			'current_password' => '',

			// Read, never sent ([[useResourceForm]]).
			'email_verified' => $user->hasVerifiedEmail(),
		]]);
	}
}
