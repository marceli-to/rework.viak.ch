<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dev\MailPreviewController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Site\AboutController;
use App\Http\Controllers\Site\CheckoutController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\CourseController;
use App\Http\Controllers\Site\CustomerAddressController;
use App\Http\Controllers\Site\CustomerPortalController;
use App\Http\Controllers\Site\ExpertController;
use App\Http\Controllers\Site\ExpertPortalController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\InviteController;
use App\Http\Controllers\Site\NewsletterController;
use App\Http\Controllers\Site\ProjectController;
use App\Http\Controllers\Site\SitemapController;
use App\Http\Controllers\Site\SoftwareController;
use App\Http\Controllers\Site\TrainingController;
use App\Http\Middleware\SetLocaleFromUrl;
use App\Support\SiteUrl;
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

/* Every public page, on the canonical host ([[SitemapController]]); named in `robots.txt`. */
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

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

			Route::get('/', HomeController::class)->name("{$locale}.home");

			// The homepage footer's signup; a stand-in until #13 ([[NewsletterController]]).
			Route::post($segments['newsletter'], [NewsletterController::class, 'store'])
				->name("{$locale}.newsletter");

			/*
			 * One Vorhaben ([[04-content]]): its copy and the courses picked
			 * for it. The homepage's tiles are the way in; there is no list
			 * page of its own.
			 */
			Route::get($segments['project'].'/{slug}', [ProjectController::class, 'show'])
				->name("{$locale}.projects.show");

			/*
			 * The software list, the shop, and one software's page, drawn as
			 * the course pages are ([[05-licences]]).
			 */
			Route::get($segments['software'], [SoftwareController::class, 'index'])
				->name("{$locale}.software.index");

			Route::get($segments['software'].'/{slug}', [SoftwareController::class, 'show'])
				->name("{$locale}.software.show");

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
			 * *Über uns* — the Über uns text, the experts and the team, merged
			 * from legacy's Experten page and two of Kontakt's blocks
			 * ([[04-content]], decided 2026-10-06). Legacy's `/de/experten` is
			 * indexed, so it 301s here.
			 */
			Route::get($segments['about'], AboutController::class)
				->name("{$locale}.about");

			Route::redirect($segments['experts'], SiteUrl::about($locale), 301)
				->name("{$locale}.experts.index");

			/*
			 * One expert ([[09-public-site]]), on legacy's URL unchanged — see
			 * [[SiteUrl::expert]] for why this one keeps its uuid when the
			 * course URL did not.
			 *
			 * The expert portal lives under the same `experte` segment, at
			 * `/de/experte/profil/…`, and never collides with this: the second
			 * segment here must be a uuid, and every portal path's is a word.
			 */
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

			// The form on it: mailed, never stored ([[ContactController]]).
			Route::post($segments['contact'], [ContactController::class, 'store'])
				->name("{$locale}.contact.send");

			/*
			 * Firmenschulung (`04-content.md`, #25): legacy's copy, an enquiry
			 * form and the testimonials picked for it. Not in the nav; Kontakt,
			 * the Kurse filter and (later) the homepage link to it. Legacy's indexed `/de/individualschulungen` 301s here.
			 */
			Route::get($segments['training'], [TrainingController::class, 'show'])
				->name("{$locale}.training");
			Route::post($segments['training'], [TrainingController::class, 'store'])
				->name("{$locale}.training.send");
			Route::redirect($segments['training_legacy'], SiteUrl::training($locale), 301);

			/*
			 * Paying an invoice by card — the course confirmation mail links
			 * here, on legacy's URL. **A placeholder** until the Stripe page is
			 * rebuilt ([[SiteUrl::invoicePayment]], `Todo.md`). It says nothing
			 * about the invoice, so it needs no sign-in.
			 */
			Route::view($segments['payment'].'/'.$segments['invoice'].'/{uuid}', 'site.payment.placeholder')
				->whereUuid('uuid')
				->name("{$locale}.payment.invoice");

			/*
			 * The checkout, a student's ([[09-public-site]]).
			 *
			 * **Legacy's own URLs**, English step names inside a German path —
			 * `/de/checkout/basket`, not `/de/warenkorb`. `config/site.php`
			 * carries a `basket` segment nothing has ever used; adopting it
			 * would be a decision rather than a port, and it waits for one.
			 *
			 * The guards are an account, verified. Legacy also asked for
			 * `role:student`; every account is a customer now
			 * (`12-customers.md`), so there is no role to ask for. Nothing here is a
			 * `Route::view` for long — the remaining steps POST — but the
			 * basket itself needs no server state, because the selection is in
			 * the browser and the price comes from `/api/basket/price`.
			 */
			Route::middleware(['auth', 'verified'])
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
			 * Legacy's `/de/student/profil` and everything under it, **301 to
			 * the customer portal** (`12-customers.md`): legacy's mails and
			 * people's bookmarks point at the old tree. The rest of the path
			 * is kept, so a booked seat's link lands on the same seat.
			 */
			Route::get($segments['student'].'/'.$segments['profile'].'/{rest?}',
				fn (?string $rest = null) => redirect(SiteUrl::customerPortal($locale).($rest ? '/'.$rest : ''), 301))
				->where('rest', '.*')
				->name("{$locale}.student.legacy");

			/*
			 * The customer portal ([[08-accounts]], [[09-public-site]]) —
			 * `/de/konto` and the screens under it (Marcel, 2026-09-30,
			 * `12-customers.md`). Legacy's was `/de/student/profil`.
			 *
			 * The segments are read from `config/site.php`, so adding `'en'`
			 * to `site.locales` adds the English tree.
			 *
			 * A tree of its own beside the expert portal's
			 * `/de/experte/profil`: what a customer sees and what an expert
			 * sees are different screens over different data, and an account
			 * can have both. See [[SiteUrl::customerPortal]].
			 *
			 * The guard is `auth` and `verified`, the checkout's: legacy's
			 * `role:student` went when every account became a customer.
			 */
			Route::middleware(['auth', 'verified'])
				->prefix($segments['account'])
				->group(function () use ($locale, $segments): void {
					Route::get('/', [CustomerPortalController::class, 'index'])
						->name("{$locale}.customer.profile");

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
					Route::get($segments['edit'], [CustomerPortalController::class, 'edit'])
						->name("{$locale}.customer.profile.edit");

					Route::post($segments['edit'], [CustomerPortalController::class, 'update'])
						->name("{$locale}.customer.profile.update");

					Route::get($segments['documents'], [CustomerPortalController::class, 'documents'])
						->name("{$locale}.customer.documents");

					/*
					 * One booked seat, resolved by the **event's** uuid rather
					 * than the booking's — legacy's choice, and the uuid a
					 * student already has from the course page. The booking is
					 * found from it and the policy decides whether it is theirs.
					 */
					Route::get($segments['course'].'/'.$segments['event'].'/{uuid}',
						[CustomerPortalController::class, 'event'])
						->whereUuid('uuid')
						->name("{$locale}.customer.event");

					/*
					 * Saved invoice addresses. Full pages rather than a dialog,
					 * which is what legacy routes them as — the checkout's
					 * *Adresse erfassen* lightbox is a different screen with a
					 * different job ([[09-public-site]]).
					 */
					Route::prefix($segments['address'])->group(function () use ($locale, $segments): void {
						Route::get($segments['create'], [CustomerAddressController::class, 'create'])
							->name("{$locale}.customer.address.create");

						Route::post('/', [CustomerAddressController::class, 'store'])
							->name("{$locale}.customer.address.store");

						Route::get($segments['edit'].'/{address:uuid}', [CustomerAddressController::class, 'edit'])
							->name("{$locale}.customer.address.edit");

						Route::put('{address:uuid}', [CustomerAddressController::class, 'update'])
							->name("{$locale}.customer.address.update");

						Route::delete('{address:uuid}', [CustomerAddressController::class, 'destroy'])
							->name("{$locale}.customer.address.destroy");
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
/*
 * *Passwort festlegen* — the link in *Dein VIAK-Zugang*, for an account an
 * admin created ([[AccountInvite]]). Beside `/login`, not under a locale:
 * signed, and good once.
 */
Route::middleware('signed')->group(function (): void {
	Route::get('/zugang/{uuid}', [InviteController::class, 'show'])->whereUuid('uuid')->name('invite.show');
	Route::post('/zugang/{uuid}', [InviteController::class, 'store'])->whereUuid('uuid')->name('invite.store');
});

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

/*
 * Dev tools, never in production ([[10-mail]]): every mail rendered from
 * fixtures ([[MailPreviewController]]). Telescope, the other one, registers
 * its own routes, locally only ([[TelescopeServiceProvider]]).
 */
if (app()->environment('local', 'testing')) {
	Route::get('/dev/mails', [MailPreviewController::class, 'index'])->name('dev.mails.index');
	Route::get('/dev/mails/{key}', [MailPreviewController::class, 'show'])->name('dev.mails.show');
}
