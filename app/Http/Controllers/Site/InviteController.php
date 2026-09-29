<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AccountInvite;
use App\Support\Home;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * *Passwort festlegen*: where *Dein VIAK-Zugang* leads ([[AccountInvite]]).
 * The route is `signed`, so a changed or expired link never reaches here.
 */
class InviteController extends Controller
{
	public function show(Request $request, string $uuid): View
	{
		$this->invited($request, $uuid);

		return view('site.auth.invite');
	}

	/**
	 * Sets the password, counts the address as verified (the link reached
	 * it), and signs the person in.
	 */
	public function store(Request $request, string $uuid): RedirectResponse
	{
		$user = $this->invited($request, $uuid);

		$data = $request->validate([
			'password' => ['required', 'string', 'min:8', 'confirmed'],
		]);

		$user->forceFill([
			'password' => $data['password'],
			'email_verified_at' => $user->email_verified_at ?? now(),
		])->save();

		Auth::login($user);
		$request->session()->regenerate();

		return redirect(Home::for($user));
	}

	/** The person the signature names, while the link is still unused. */
	private function invited(Request $request, string $uuid): User
	{
		$user = User::query()->where('uuid', $uuid)->firstOrFail();

		abort_if($user->isDeactivated(), 403, 'Dieses Konto ist deaktiviert.');
		abort_unless(hash_equals(AccountInvite::fingerprint($user), (string) $request->query('check')), 403, 'Dieser Link wurde bereits verwendet. Setze Dein Passwort über «Passwort vergessen?» zurück.');

		return $user;
	}
}
