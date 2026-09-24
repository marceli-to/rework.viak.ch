import { reactive } from 'vue';

/**
 * The bar across the top while the dashboard waits: legacy's NProgress
 * (`nprogress` 0.2.0, as legacy's dashboard ran it), rewritten as a counter
 * so that nothing has to start and stop it by hand ([[07-dashboard]]).
 *
 * The API client counts every request, and the router counts a navigation
 * while it waits for a screen's code. `<Progress>` in the shell draws it.
 *
 * NProgress's own numbers: it starts at 8%, creeps by up to 2% every 800ms and
 * never passes 99.4%; *done* jumps ahead, runs to 100% in 200ms, holds for
 * 200ms and fades out over 200ms.
 *
 * Two changes to NProgress:
 * - **It waits 150ms before it shows.** Legacy flashed the bar on every
 *   request. Most requests here finish before then, and a screen already
 *   loaded opens straight away.
 * - **Finishing waits one turn of the event loop.** A screen opening ends its
 *   navigation and starts loading its data a moment later. Waiting one turn
 *   keeps that as one run of the bar, not two.
 */
export const bar = reactive({ value: null, fading: false });

const DELAY = 150;
const SPEED = 200;
const TRICKLE = 800;

let active = 0;
let showing = null;
let trickling = null;
let settling = null;
let fading = [];

const clamp = (n, min, max) => Math.min(Math.max(n, min), max);

function show() {
	showing = null;
	fading.forEach(clearTimeout);
	fading = [];
	bar.fading = false;
	bar.value = 0.08;
	trickling = setInterval(() => (bar.value = clamp(bar.value + Math.random() * 0.02, 0, 0.994)), TRICKLE);
}

function finish() {
	clearInterval(trickling);
	trickling = null;
	bar.value = 1;
	fading = [
		setTimeout(() => (bar.fading = true), SPEED),
		setTimeout(() => {
			bar.value = null;
			bar.fading = false;
		}, SPEED * 2),
	];
}

export function start() {
	active++;
	clearTimeout(settling);
	if (!trickling && !showing) showing = setTimeout(show, DELAY);
}

export function done() {
	active = Math.max(0, active - 1);
	if (active) return;

	clearTimeout(settling);
	settling = setTimeout(() => {
		if (active) return;
		if (showing) {
			clearTimeout(showing);
			showing = null;
		} else if (trickling) {
			finish();
		}
	});
}
