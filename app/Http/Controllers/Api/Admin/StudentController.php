<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Accounts\CreateAccount;
use App\Actions\Accounts\RequireEmailConfirmation;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveStudentRequest;
use App\Http\Resources\Admin\StudentFormResource;
use App\Http\Resources\Admin\StudentRowResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * *Studenten* ([[07-dashboard]], step 6) — legacy's `Dashboard/StudentController`.
 *
 * A student is anyone holding the Student role; `{student}` binds nobody else.
 * **Nobody is deleted** (#16): an account is deactivated, and the list keeps
 * the deactivated apart.
 */
class StudentController extends Controller
{
	private const PER_PAGE = 50;

	/**
	 * **Searched and paginated on the server**: 570 students, about 200 more a
	 * year, where legacy loaded them all. Every word of the search has to match
	 * one of name, e-mail, city, phone or company.
	 *
	 * The deactivated come whole, with `?deaktiviert=1`: a handful, listed
	 * apart as legacy's list would have had them.
	 */
	public function index(Request $request): AnonymousResourceCollection
	{
		$query = User::query()
			->withRole(Role::Student)
			->when($request->boolean('deaktiviert'), fn (Builder $query) => $query->whereNotNull('deactivated_at'), fn (Builder $query) => $query->whereNull('deactivated_at'))
			->tap(fn (Builder $query) => $this->search($query, (string) $request->query('suche', '')))
			->orderBy('last_name')
			->orderBy('first_name');

		return StudentRowResource::collection($request->boolean('deaktiviert') ? $query->get() : $query->paginate(self::PER_PAGE));
	}

	public function show(User $student): StudentFormResource
	{
		return new StudentFormResource($this->loaded($student));
	}

	/** Invited to set their own password ([[CreateAccount]]). */
	public function store(SaveStudentRequest $request, CreateAccount $create): JsonResponse
	{
		$student = DB::transaction(function () use ($request, $create): User {
			$student = $create->execute($request->userAttributes(), $request->roles());
			$this->saveAddresses($student, $request->addresses());

			return $student;
		});

		return (new StudentFormResource($this->loaded($student)))->response()->setStatusCode(201);
	}

	/**
	 * An address the admin changes must be confirmed by the person
	 * ([[RequireEmailConfirmation]]).
	 */
	public function update(SaveStudentRequest $request, User $student, RequireEmailConfirmation $confirm): StudentFormResource
	{
		DB::transaction(function () use ($request, $student): void {
			$student->update($request->userAttributes());
			$student->syncRoles($request->roles());
			$this->saveAddresses($student, $request->addresses());
		});

		if ($student->wasChanged('email')) {
			$confirm->execute($student);
		}

		return new StudentFormResource($this->loaded($student));
	}

	/**
	 * Deactivate or reactivate — what the rework does instead of deleting
	 * (#16). A deactivated account cannot sign in, and a session already open
	 * ends on its next request ([[SignOutDeactivated]]). Not your own.
	 */
	public function state(Request $request, User $student): StudentFormResource
	{
		$active = $request->validate(['active' => ['required', 'boolean']])['active'];

		abort_if(! $active && $student->is($request->user()), 422, 'Du kannst dein eigenes Konto nicht deaktivieren.');

		$student->forceFill(['deactivated_at' => $active ? null : now()])->save();

		return new StudentFormResource($this->loaded($student));
	}

	/** Every word somewhere: *anna zürich* finds Anna Muster in Zürich. */
	private function search(Builder $query, string $search): void
	{
		foreach (preg_split('/\s+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY) as $word) {
			$like = '%'.addcslashes($word, '%_\\').'%';
			$query->where(fn (Builder $query) => $query
				->where('first_name', 'like', $like)
				->orWhere('last_name', 'like', $like)
				->orWhere('email', 'like', $like)
				->orWhere('city', 'like', $like)
				->orWhere('phone', 'like', $like)
				->orWhere('company', 'like', $like));
		}
	}

	/**
	 * The form's addresses, exactly: a row with a uuid of theirs is updated,
	 * one without is new, and one no longer sent is soft-deleted, as the
	 * portal deletes one.
	 *
	 * @param  array<int, array<string, mixed>>  $rows
	 */
	private function saveAddresses(User $student, array $rows): void
	{
		$kept = [];

		foreach ($rows as $row) {
			$uuid = $row['uuid'];
			unset($row['uuid']);

			$address = $uuid ? $student->addresses()->where('uuid', $uuid)->first() : null;
			$address ? $address->update($row) : $address = $student->addresses()->create($row);
			$kept[] = $address->getKey();
		}

		$student->addresses()->whereKeyNot($kept)->get()->each->delete();
	}

	private function loaded(User $student): User
	{
		return $student->load(['addresses' => fn ($query) => $query->orderBy('id')]);
	}
}
