<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Site\CheckoutController;
use App\Http\Controllers\Site\CourseController;
use App\Http\Controllers\Site\ExpertController;
use App\Http\Controllers\Site\ExpertPortalController;
use App\Http\Controllers\Site\StudentAddressController;
use App\Http\Controllers\Site\StudentPortalController;
use App\Http\Middleware\SetLocaleFromUrl;
use Illuminate\Support\Facades\Route;

/*
 * The root redirects to the language root ([[00-foundation]]).
 *
 * Legacy serves **both** `/` and `/de` with 200 and no canonical tag, which is
 * duplicate content on the live site today — verified against production on
 * 2026-09-18, identical `<title>` on each.
 *
 * The scope note said to fix that with a canonical tag. A 301 is better and is
 * what this does: a canonical is a hint, a redirect is a directive, and `/` has
 * no content of its own to keep. It costs one round trip on the most-linked URL
 * and consolidates the two properly.
 */
Route::redirect('/', '/'.config('app.locale'), 301);

/*
 * On-demand image rendering ([[08-accounts]]). Parameters are clamped to the
 * sizes and formats `<x-media.image>` would have produced, so the resizer cannot
 * be used to fill the cache disk.
 */
Route::get('/img/{path}', [ImageController::class, 'show'])
	->where('path', '.*')
	->name('image');

/*
 * Everything the public sees, under its locale prefix.
 *
 * The segments come from `config/site.php` rather than being written here, so
 * `/en/course/{slug}` exists the moment a locale is added — and so nothing in a
 * view has to know that the list is `kurse` and the detail is `kurs`. That
 * plural/singular split is legacy's, it is what is indexed, and it is not ours
 * to tidy ([[SiteUrl]]).
 */
Route::prefix('{locale}')
	->whereIn('locale', config('site.locales'))
	->middleware(SetLocaleFromUrl::class)
	->group(function (): void {
		foreach (config('site.locales') as $locale) {
			$segments = config("site.segments.{$locale}");

			Route::view('/', 'site.home')->name("{$locale}.home");

			Route::get($segments['courses'], [CourseController::class, 'index'])
				->name("{$locale}.courses.index");

			Route::get($segments['course'].'/{slug}', [CourseController::class, 'show'])
				->name("{$locale}.courses.show");

			/*
			 * The legacy detail URL carried a slug *and* a uuid, and the uuid is
			 * what resolved — so `/de/kurs/anything/{uuid}` renders the course
			 * today. 301 to the slug form, which passes essentially full
			 * ranking and retires the wart. `01-schema.md` carries legacy uuids
			 * across rather than regenerating them, which is what makes this
			 * lookup possible at all.
			 */
			Route::get($segments['course'].'/{slug}/{uuid}', [CourseController::class, 'redirectLegacy'])
				->whereUuid('uuid')
				->name("{$locale}.courses.legacy");

			/*
			 * The Experten page and one expert ([[09-public-site]]), on
			 * legacy's URLs unchanged — see [[SiteUrl::expert]] for why this one
			 * keeps its uuid when the course URL did not.
			 *
			 * The expert portal lives under the same `experte` segment, at
			 * `/de/experte/profil/…`, and never collides with this: the second
			 * segment here must be a uuid, and every portal path's is a word.
			 */
			Route::get($segments['experts'], [ExpertController::class, 'index'])
				->name("{$locale}.experts.index");

			Route::get($segments['expert'].'/{slug}/{uuid}', [ExpertController::class, 'show'])
				->whereUuid('uuid')
				->name("{$locale}.experts.show");

			/*
			 * Kontakt — static copy and a map, so a view and no controller.
			 * Legacy's controller existed only to hand the page its team
			 * members, and there are none ([[09-public-site]]).
			 */
			Route::view($segments['contact'], 'site.contact.index')
				->name("{$locale}.contact");

			/*
			 * The checkout, a student's ([[09-public-site]]).
			 *
			 * **Legacy's own URLs**, English step names inside a German path —
			 * `/de/checkout/basket`, not `/de/warenkorb`. `config/site.php`
			 * carries a `basket` segment nothing has ever used; adopting it
			 * would be a decision rather than a port, and it waits for one.
			 *
			 * The guards are legacy's too: its whole checkout group is
			 * `auth:sanctum, verified` plus `role:student`. Nothing here is a
			 * `Route::view` for long — the remaining steps POST — but the
			 * basket itself needs no server state, because the selection is in
			 * the browser and the price comes from `/api/basket/price`.
			 */
			Route::middleware(['auth', 'verified', 'role:student'])
				->prefix($segments['checkout'])
				->group(function () use ($locale): void {
					/*
					 * The basket is a bare view: its contents are in the
					 * browser and its prices come from `/api/basket/price`, so
					 * there is nothing for a controller to hand it.
					 */
					Route::view('basket', 'site.checkout.basket')
						->name("{$locale}.checkout.basket");

					/*
					 * From here on every step is Blade with a POST and the
					 * answers in the session ([[CheckoutSession]]).
					 */
					Route::get('address', [CheckoutController::class, 'address'])
						->name("{$locale}.checkout.address");
					Route::post('address', [CheckoutController::class, 'storeAddress']);

					// The *Adresse erfassen* dialog, as a real form post.
					Route::post('address/new', [CheckoutController::class, 'storeNewAddress'])
						->name("{$locale}.checkout.address.new");

					Route::get('payment', [CheckoutController::class, 'payment'])
						->name("{$locale}.checkout.payment");

					Route::get('summary', [CheckoutController::class, 'summary'])
						->name("{$locale}.checkout.summary");

					// The only irreversible POST in the flow.
					Route::post('summary', [CheckoutController::class, 'complete']);

					Route::get('confirmation', [CheckoutController::class, 'confirmation'])
						->name("{$locale}.checkout.confirmation");
				});

			/*
			 * The student portal ([[08-accounts]], [[09-public-site]]).
			 *
			 * **Legacy's URL tree, with the segments read from
			 * `config/site.php`** — `/de/student/profil` and four screens under
			 * it. Legacy writes both languages out by hand in its own
			 * `routes/web.php`, twelve routes for six; here the loop does it,
			 * and adding `'en'` to `site.locales` adds the English tree.
			 *
			 * Why a role tree rather than one `/de/konto`: four accounts hold
			 * more than one role, and what a student sees and what an expert
			 * sees are different screens over different data, not two views of
			 * one. See [[SiteUrl::studentPortal]].
			 *
			 * The guard is legacy's — `auth`, `verified`, `role:student` —
			 * and it is the same one the checkout carries.
			 */
			Route::middleware(['auth', 'verified', 'role:student'])
				->prefix($segments['student'].'/'.$segments['profile'])
				->group(function () use ($locale, $segments): void {
					Route::get('/', [StudentPortalController::class, 'index'])
						->name("{$locale}.student.profile");

					/*
					 * **The edit form is a screen, not a panel** (Marcel,
					 * 2026-09-22). Legacy toggles it in place off component
					 * state, and that state does not survive leaving the page —
					 * which the *Rechnungsadressen* block inside it does, every
					 * time somebody adds an address. See
					 * [[SiteUrl::studentProfileEdit]].
					 *
					 * The POST lands on the same URL, so a validation failure
					 * comes back to the form by itself rather than needing to be
					 * sent there.
					 */
					Route::get($segments['edit'], [StudentPortalController::class, 'edit'])
						->name("{$locale}.student.profile.edit");

					Route::post($segments['edit'], [StudentPortalController::class, 'update'])
						->name("{$locale}.student.profile.update");

					Route::get($segments['documents'], [StudentPortalController::class, 'documents'])
						->name("{$locale}.student.documents");

					/*
					 * One booked seat, resolved by the **event's** uuid rather
					 * than the booking's — legacy's choice, and the uuid a
					 * student already has from the course page. The booking is
					 * found from it and the policy decides whether it is theirs.
					 */
					Route::get($segments['course'].'/'.$segments['event'].'/{uuid}',
						[StudentPortalController::class, 'event'])
						->whereUuid('uuid')
						->name("{$locale}.student.event");

					/*
					 * Saved invoice addresses. Full pages rather than a dialog,
					 * which is what legacy routes them as — the checkout's
					 * *Adresse erfassen* lightbox is a different screen with a
					 * different job ([[09-public-site]]).
					 */
					Route::prefix($segments['address'])->group(function () use ($locale, $segments): void {
						Route::get($segments['create'], [StudentAddressController::class, 'create'])
							->name("{$locale}.student.address.create");

						Route::post('/', [StudentAddressController::class, 'store'])
							->name("{$locale}.student.address.store");

						Route::get($segments['edit'].'/{address:uuid}', [StudentAddressController::class, 'edit'])
							->name("{$locale}.student.address.edit");

						Route::put('{address:uuid}', [StudentAddressController::class, 'update'])
							->name("{$locale}.student.address.update");

						Route::delete('{address:uuid}', [StudentAddressController::class, 'destroy'])
							->name("{$locale}.student.address.destroy");
					});
				});

			/*
			 * The expert portal ([[08-accounts]], [[09-public-site]]).
			 *
			 * Legacy's second role tree, `/de/experte/profil`, with the segments
			 * read from `config/site.php` as the student's are. Five screens:
			 * the landing page, the profile form, one course, the message
			 * composer and the upload.
			 *
			 * **The guard is `role:admin,expert`, which is legacy's** — an admin
			 * reaches these screens too, and [[EventPolicy::viewParticipants]]
			 * then admits them to every course rather than to the ones they
			 * teach. That is the shape legacy has and it is the right one: an
			 * admin answering a question about a course should not have to be
			 * added to it as an expert first.
			 *
			 * What legacy does *not* have is anything below the role. Every
			 * screen under here is authorised against the **event**, which is
			 * findings 4 and 5 of `08-accounts.md` — a participant list is names,
			 * towns, phone numbers and email addresses, and
			 * `/pdf/teilnehmer-liste/{event}` hands it to any of the 18 accounts
			 * holding the Expert role.
			 */
			Route::middleware(['auth', 'verified', 'role:admin,expert'])
				->prefix($segments['expert'].'/'.$segments['profile'])
				->group(function () use ($locale, $segments): void {
					Route::get('/', [ExpertPortalController::class, 'index'])
						->name("{$locale}.expert.profile");

					/*
					 * A screen rather than a panel, for the reason the student's
					 * form is one ([[SiteUrl::studentProfileEdit]]). The POST
					 * lands on the same URL so a validation failure comes back
					 * by itself.
					 */
					Route::get($segments['edit'], [ExpertPortalController::class, 'edit'])
						->name("{$locale}.expert.profile.edit");

					Route::post($segments['edit'], [ExpertPortalController::class, 'update'])
						->name("{$locale}.expert.profile.update");

					/*
					 * One course, by the **event's** uuid — the student portal's
					 * choice and legacy's, under a different root.
					 *
					 * The two screens under it keep legacy's English segments
					 * inside the German path, `…/message` and `…/file-upload`,
					 * for the same reason `/de/checkout/basket` does.
					 */
					Route::prefix($segments['course'].'/'.$segments['event'].'/{uuid}')
						->whereUuid('uuid')
						->group(function () use ($locale, $segments): void {
							Route::get('/', [ExpertPortalController::class, 'event'])
								->name("{$locale}.expert.event");

							/*
							 * The participant list as a PDF — legacy's
							 * `/pdf/teilnehmer-liste/{event}`, moved under the
							 * portal so it inherits the same object-level check
							 * as the screen that links to it
							 * ([[EventPolicy::viewParticipants]]).
							 */
							Route::get($segments['participants'], [ExpertPortalController::class, 'participants'])
								->name("{$locale}.expert.event.participants");

							Route::get($segments['message'], [ExpertPortalController::class, 'createMessage'])
								->name("{$locale}.expert.event.message.create");

							Route::post($segments['message'], [ExpertPortalController::class, 'storeMessage'])
								->name("{$locale}.expert.event.message.store");

							Route::get($segments['upload'], [ExpertPortalController::class, 'createUpload'])
								->name("{$locale}.expert.event.upload.create");

							Route::post($segments['upload'], [ExpertPortalController::class, 'storeUpload'])
								->name("{$locale}.expert.event.upload.store");

							/*
							 * Removing a course document. A `DELETE` from a form
							 * with `@method`, as the address screens do — the
							 * only verb-spoofed route on the portal, and it is
							 * one because a file removal is not a navigation.
							 */
							Route::delete($segments['documents'].'/{media:uuid}',
								[ExpertPortalController::class, 'destroyFile'])
								->name("{$locale}.expert.event.file.destroy");
						});
				});
		}
	});

/*
 * Generated PDFs, behind the session guard and a policy ([[08-accounts]]).
 * Legacy served these straight off the public disk.
 */
Route::get('/dokumente/{document}', [DocumentController::class, 'show'])
	->middleware('auth')
	->name('documents.show');

/*
 * Attachments — a course's materials, a file on a message ([[08-accounts]]).
 *
 * Images do **not** come through here: they are published content and
 * `/img/{path}` serves them at whatever size the page asked for.
 * [[MediaPolicy]] admits an Event's or a Message's files to the people who
 * belong to that course, and nothing else at all.
 */
Route::get('/medien/{media:uuid}', [MediaController::class, 'download'])
	->middleware('auth')
	->name('media.download');

// SPA shell — the dashboard router takes over client-side. Admins only; anyone
// else is sent to their own portal ([[DashboardController]]).
Route::get('/dashboard/{any?}', DashboardController::class)
	->middleware('auth')
	->where('any', '.*')
	->name('dashboard');

/*
 * Legacy's own redirect, kept ([[08-accounts]]).
 *
 * `routes/web.php:164` on the live site sends `/register` — the URL
 * `Auth::routes()` would have claimed — to the prefixed form at
 * `/de/registration`, which is the one that is linked and indexed. Fortify now
 * serves the form there directly (`config/fortify.php`, `paths`), so this only
 * has to catch the unprefixed one.
 */
Route::redirect('/register', '/de/registration', 301);
