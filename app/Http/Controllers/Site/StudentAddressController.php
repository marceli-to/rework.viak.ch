<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\StoreAddressRequest;
use App\Models\Country;
use App\Models\UserAddress;
use App\Support\SiteUrl;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Saved invoice addresses, as pages ([[08-accounts]], [[09-public-site]]).
 *
 * **Full screens, not a dialog.** Legacy routes `adresse/erstellen` and
 * `adresse/bearbeiten/{uuid}` as views of the student SPA and renders the form
 * in the page, and that is what is rebuilt here. The checkout's *Adresse
 * erfassen* lightbox is a different screen with a different job — it exists so
 * a customer mid-purchase does not lose the basket, and it selects what it
 * creates.
 *
 * Writes go through [[StoreAddressRequest]], whose `authorize()` asks
 * [[UserAddressPolicy]] whether the address is the caller's. That is the check
 * legacy made nowhere: all 28 of its FormRequests return `true`.
 */
class StudentAddressController extends Controller
{
	public function create(): View
	{
		return view('site.student.address', [
			'address' => null,
			'countries' => $this->countries(),
		]);
	}

	/**
	 * Back **into the form**, all three of them.
	 *
	 * The *Rechnungsadressen* list lives inside the profile's edit screen, so
	 * that is where somebody who just added, changed or removed an address
	 * expects to land — on the list they changed, with the change in it. Sending
	 * them to the read view would show them a page with no addresses on it at
	 * all ([[SiteUrl::studentProfileEdit]]).
	 */
	public function store(StoreAddressRequest $request): RedirectResponse
	{
		$request->user()->addresses()->create($request->validated());

		return redirect(SiteUrl::studentProfileEdit())
			->with('status', 'Die Rechnungsadresse wurde gespeichert.');
	}

	public function edit(Request $request, UserAddress $address): View
	{
		$this->authorize('update', $address);

		return view('site.student.address', [
			'address' => $address,
			'countries' => $this->countries(),
		]);
	}

	public function update(StoreAddressRequest $request, UserAddress $address): RedirectResponse
	{
		$address->update($request->validated());

		return redirect(SiteUrl::studentProfileEdit())
			->with('status', 'Die Rechnungsadresse wurde gespeichert.');
	}

	/**
	 * *Adresse löschen*, legacy's `form-danger-zone` at the foot of the edit
	 * form.
	 *
	 * Soft-deleted, because a booking froze a **copy** of the address at
	 * checkout rather than pointing at this row — so deleting one changes no
	 * invoice that has gone out, and the row is still there to trace one back
	 * to ([[AddressController]]).
	 */
	public function destroy(UserAddress $address): RedirectResponse
	{
		$this->authorize('delete', $address);

		$address->delete();

		return redirect(SiteUrl::studentProfileEdit())
			->with('status', 'Die Rechnungsadresse wurde gelöscht.');
	}

	/** @return Collection<int, Country> */
	private function countries()
	{
		return Country::query()->orderBy('order')->orderBy('name')->get();
	}
}
