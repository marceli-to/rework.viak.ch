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
			preferencesModal: { layout: 'box', position: 'right', equalWeightButtons: false, flipButtons: false },
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
						description: `Unsere Website verwendet Tracking-Cookies, um zu verstehen, wie Sie mit ihr interagieren. Das Tracking wird nur aktiviert, wenn Sie ausdrücklich zustimmen. Mehr dazu in der <a href="${privacy}">Datenschutzerklärung</a>.`,
						acceptAllBtn: 'Akzeptieren',
						acceptNecessaryBtn: 'Ablehnen',
						showPreferencesBtn: 'Einstellungen',
					},
					preferencesModal: {
						title: 'Cookie Einstellungen',
						acceptAllBtn: 'Alle akzeptieren',
						acceptNecessaryBtn: 'Alle ablehnen',
						savePreferencesBtn: 'Einstellungen speichern',
						closeIconLabel: 'Schliessen',
						sections: [
							{
								title: 'Verwendung von Cookies',
								description: `Wir verwenden Cookies, um die grundlegenden Funktionen der Website sicherzustellen und Ihr Online-Erlebnis zu verbessern. Sie können für jede Kategorie wählen, ob Sie sich jederzeit an- oder abmelden möchten. Weitere Einzelheiten zu Cookies und anderen sensiblen Daten finden Sie in der vollständigen <a href="${privacy}" class="cc__link">Datenschutzerklärung</a>.`,
							},
							{
								title: 'Notwendige Cookies',
								description: 'Cookies sind unverzichtbar, damit unsere Webseite einwandfrei funktioniert. Sie ermöglichen grundlegende Funktionen wie Seitennavigation und Zugriff auf sichere Bereiche der Webseite. Diese Cookies sammeln keine Informationen über Sie, die für Marketingzwecke verwendet werden könnten oder merken, wo Sie im Internet gewesen sind. Ohne diese Cookies kann unsere Webseite nicht effizient funktionieren. Ihre Zustimmung zu diesen Cookies ist nicht erforderlich, da sie essentiell für die Bereitstellung der von Ihnen angeforderten Dienste sind.',
								linkedCategory: 'necessary',
							},
							{
								title: 'Performance und Analyse Cookies',
								description: 'Performance- und Analyse-Cookies helfen uns, die Nutzung unserer Website zu verstehen und zu messen. Sie sammeln Informationen darüber, wie Besucher unsere Seite nutzen, welche Seiten am häufigsten besucht werden oder ob sie Fehlermeldungen von Webseiten erhalten. Diese Cookies sammeln keine Informationen, die einen Besucher direkt identifizieren. Alle Informationen, die durch diese Cookies gesammelt werden, sind aggregiert und daher anonym. Sie werden nur verwendet, um die Funktionsweise unserer Website zu verbessern. Durch die Zustimmung zu diesen Cookies tragen Sie zu einer optimierten Nutzungserfahrung bei.',
								linkedCategory: 'analytics',
							},
							{
								title: 'Marketing Cookies',
								description: 'Marketing-Cookies helfen uns zu messen, welche Werbung (zum Beispiel bei Google oder Meta) zu einer Buchung führt, und Ihnen passende Angebote zu zeigen. Sie werden nur gesetzt, wenn Sie zustimmen.',
								linkedCategory: 'marketing',
							},
							{
								title: 'Weitere Informationen',
								description: 'Für Fragen zu unserer Cookie-Richtlinie und Ihren Wahlmöglichkeiten stehen wir Ihnen gerne zur Verfügung. <a class="cc__link" href="/de/kontakt">Kontakt</a>.',
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
