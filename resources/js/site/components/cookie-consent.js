import * as CookieConsent from 'vanilla-cookieconsent';
import 'vanilla-cookieconsent/dist/cookieconsent.css';
import '../../../css/cookie-consent.css';

/**
 * The cookie banner ([[11-tracking]], step 1): legacy's orestbida
 * cookieconsent v3 (`public/assets/cookieconsent/cookieconsent-init.js`), its
 * cookie name, layout and wording, with chunk 11's findings fixed:
 *
 * - **A marketing category.** Legacy unlocked `ads` but never defined it, so
 *   nobody could ever allow advertising cookies (finding 2). `marketing` grants
 *   `ad_storage`, `ad_user_data` and `ad_personalization`, Consent Mode v2
 *   (finding 4); the defaults are set in `tracking/head.blade.php`.
 * - **The consent update in the callbacks**, not in four `text/plain` scripts.
 *   `onConsent` runs on the first choice and on every later page load, so the
 *   saved choice reaches GTM before its tags fire on each page.
 * - **The links**: the privacy policy is on the Kontakt page (legacy linked a
 *   2023 PDF and `#`), and *Kontakt* is VIAK's, not `chamgroup.ch` (finding 5).
 *
 * The Meta pixel ignoring consent (finding 3) is the container's to fix, not
 * this file's: a GTM tag is told which consent it waits for.
 */
const privacy = '/de/kontakt';

function grant(category) {
	return CookieConsent.acceptedCategory(category) ? 'granted' : 'denied';
}

/** The choice, to Google's Consent Mode and as an event GTM can trigger on. */
function update() {
	const marketing = grant('marketing');

	window.gtag?.('consent', 'update', {
		analytics_storage: grant('analytics'),
		ad_storage: marketing,
		ad_user_data: marketing,
		ad_personalization: marketing,
	});

	window.dataLayer?.push({ event: 'consent_update' });
}

export default function cookieConsent() {
	return CookieConsent.run({
		cookie: { name: 'viak_cookie_consent' },

		guiOptions: {
			consentModal: { layout: 'bar inline', position: 'bottom center', equalWeightButtons: false, flipButtons: true },
			preferencesModal: { layout: 'box', equalWeightButtons: true, flipButtons: false },
		},

		onConsent: update,
		onChange: update,

		categories: {
			necessary: { readOnly: true, enabled: true },
			analytics: { autoClear: { cookies: [{ name: /^(_ga|_gid)/ }] } },
			marketing: { autoClear: { cookies: [{ name: /^(_gcl|_fbp|_fbc)/ }] } },
		},

		language: {
			default: 'de',
			translations: {
				de: {
					consentModal: {
						title: 'Verwendung von Cookies',
						description: `Unsere Website verwendet Tracking-Cookies, um zu verstehen, wie du mit ihr interagierst. Das Tracking wird nur aktiviert, wenn du ausdrücklich zustimmst. Mehr dazu in der <a href="${privacy}">Datenschutzerklärung</a>.`,
						acceptAllBtn: 'Akzeptieren',
						acceptNecessaryBtn: 'Ablehnen',
						showPreferencesBtn: 'Einstellungen',
					},
					preferencesModal: {
						title: 'Cookie-Einstellungen',
						acceptAllBtn: 'Alle akzeptieren',
						acceptNecessaryBtn: 'Nur notwendige',
						savePreferencesBtn: 'Auswahl speichern',
						closeIconLabel: 'Schliessen',
						sections: [
							{
								description: `Du bestimmst, wofür wir Cookies setzen dürfen. Notwendige sind immer aktiv, alle anderen nur mit deiner Zustimmung. Ändern kannst du deine Wahl jederzeit in der <a href="${privacy}" class="cc__link">Datenschutzerklärung</a>.`,
							},
							{
								title: 'Notwendig',
								description: 'Damit die Website funktioniert: Anmeldung, Warenkorb und Buchung, der Schutz der Formulare und das Speichern dieser Auswahl. Diese Cookies verfolgen dich nicht.',
								linkedCategory: 'necessary',
							},
							{
								title: 'Analyse',
								description: 'Mit Google Analytics sehen wir, welche Seiten besucht werden und wo etwas nicht funktioniert. Google kann diese Daten auch in den USA bearbeiten.',
								linkedCategory: 'analytics',
							},
							{
								title: 'Marketing',
								description: 'Mit Google Ads und Meta messen wir, welche Werbung zu einer Buchung führt, und zeigen dir passende Angebote. Google und Meta können diese Daten auch in den USA bearbeiten.',
								linkedCategory: 'marketing',
							},
						],
					},
				},
			},
		},
	});
}

/** The *Cookie-Einstellungen* link on the Kontakt page. */
export function showCookiePreferences() {
	CookieConsent.showPreferences();
}
