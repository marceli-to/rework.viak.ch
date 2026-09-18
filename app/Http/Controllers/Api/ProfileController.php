<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Accounts\UpdateProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\UpdateProfileRequest;
use App\Http\Resources\UserDocumentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * A user's own account ([[08-accounts]]).
 *
 * One controller for all three roles. Legacy had three — `StudentController`,
 * `ExpertController`, `AdminController` — differing only in which two or three
 * fields they validated, and each carrying its own copy of the same unconfirmed
 * email change.
 */
class ProfileController extends Controller
{
	public function show(Request $request): JsonResponse
	{
		$user = $request->user();

		return response()->json(['data' => [
			'uuid' => $user->uuid,
			'first_name' => $user->first_name,
			'last_name' => $user->last_name,
			'email' => $user->email,
			'email_verified' => $user->hasVerifiedEmail(),
			'company' => $user->company,
			'street' => $user->street,
			'street_no' => $user->street_no,
			'zip' => $user->zip,
			'city' => $user->city,
			'phone' => $user->phone,
			'country_code' => $user->country_code,
			'roles' => $user->roles()->map(fn ($role) => $role->value)->values(),
		]]);
	}

	public function update(UpdateProfileRequest $request, UpdateProfile $update): JsonResponse
	{
		$user = $update->execute(
			user: $request->user(),
			attributes: $request->profileAttributes(),
			email: $request->filled('email') ? $request->string('email')->value() : null,
			password: $request->filled('password') ? $request->string('password')->value() : null,
			currentPassword: $request->string('current_password')->value() ?: null,
		);

		return response()->json([
			'data' => ['uuid' => $user->uuid],
			// The client has to say so: an unverified address stops mail
			// arriving, and a customer who does not know that will wonder where
			// their invoice went.
			'email_verification_required' => ! $user->hasVerifiedEmail(),
		]);
	}

	/** *Meine Dokumente* — invoices and participation confirmations. */
	public function documents(Request $request): AnonymousResourceCollection
	{
		return UserDocumentResource::collection(
			$request->user()->documents()->latest('date')->get()
		);
	}
}
