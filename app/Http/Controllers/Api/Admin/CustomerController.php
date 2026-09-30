<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Accounts\CreateAccount;
use App\Actions\Accounts\RequireEmailConfirmation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCustomerRequest;
use App\Http\Resources\Admin\CustomerFormResource;
use App\Http\Resources\Admin\CustomerRowResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * *Kunden* ([[07-dashboard]], step 6) — legacy's `Dashboard/StudentController`, whose
 * *Studenten* it was.
 *
 * Every account is a customer (`12-customers.md`); `{customer}` binds any of them.
 * **Nobody is deleted** (#16): an account is deactivated, and the list keeps
 * the deactivated apart.
 */
class CustomerController extends Controller
{
	private const PER_PAGE = 50;

	/**
	 * **Searched and paginated on the server**: some 580 accounts, about 200 more a
	 * year, where legacy loaded them all. Every word of the search has to match
	 * one of name, e-mail, city, phone or company.
	 *
	 * The deactivated come whole, with `?deaktiviert=1`: a handful, listed
	 * apart as legacy's list would have had them.
	 */
	public function index(Request $request): AnonymousResourceCollection
	{
		$query = User::query()
			->when($request->boolean('deaktiviert'), fn (Builder $query) => $query->whereNotNull('deactivated_at'), fn (Builder $query) => $query->whereNull('deactivated_at'))
			->tap(fn (Builder $query) => $this->search($query, (string) $request->query('suche', '')))
			->orderBy('last_name')
			->orderBy('first_name');

		return CustomerRowResource::collection($request->boolean('deaktiviert') ? $query->get() : $query->paginate(self::PER_PAGE));
	}

	public function show(User $customer): CustomerFormResource
	{
		return new CustomerFormResource($this->loaded($customer));
	}

	/** Invited to set their own password ([[CreateAccount]]). */
	public function store(SaveCustomerRequest $request, CreateAccount $create): JsonResponse
	{
		$customer = DB::transaction(function () use ($request, $create): User {
			$customer = $create->execute($request->userAttributes(), $request->roles());
			$this->saveAddresses($customer, $request->addresses());

			return $customer;
		});

		return (new CustomerFormResource($this->loaded($customer)))->response()->setStatusCode(201);
	}

	/**
	 * An address the admin changes must be confirmed by the person
	 * ([[RequireEmailConfirmation]]).
	 */
	public function update(SaveCustomerRequest $request, User $customer, RequireEmailConfirmation $confirm): CustomerFormResource
	{
		DB::transaction(function () use ($request, $customer): void {
			$customer->update($request->userAttributes());
			$customer->syncRoles($request->roles());
			$this->saveAddresses($customer, $request->addresses());
		});

		if ($customer->wasChanged('email')) {
			$confirm->execute($customer);
		}

		return new CustomerFormResource($this->loaded($customer));
	}

	/**
	 * Deactivate or reactivate — what the rework does instead of deleting
	 * (#16). A deactivated account cannot sign in, and a session already open
	 * ends on its next request ([[SignOutDeactivated]]). Not your own.
	 */
	public function state(Request $request, User $customer): CustomerFormResource
	{
		$active = $request->validate(['active' => ['required', 'boolean']])['active'];

		abort_if(! $active && $customer->is($request->user()), 422, 'Du kannst dein eigenes Konto nicht deaktivieren.');

		$customer->forceFill(['deactivated_at' => $active ? null : now()])->save();

		return new CustomerFormResource($this->loaded($customer));
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
	private function saveAddresses(User $customer, array $rows): void
	{
		$kept = [];

		foreach ($rows as $row) {
			$uuid = $row['uuid'];
			unset($row['uuid']);

			$address = $uuid ? $customer->addresses()->where('uuid', $uuid)->first() : null;
			$address ? $address->update($row) : $address = $customer->addresses()->create($row);
			$kept[] = $address->getKey();
		}

		$customer->addresses()->whereKeyNot($kept)->get()->each->delete();
	}

	private function loaded(User $customer): User
	{
		return $customer->load(['addresses' => fn ($query) => $query->orderBy('id')]);
	}
}
