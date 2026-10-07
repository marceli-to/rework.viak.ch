/**
 * The homepage intro's slider ([[media.slider]]), legacy's Swiper as it was set
 * up (`frontend/home.js`): `loop`, `autoplay: { delay: 5000 }`, the two arrows,
 * Swiper's own 300ms slide. Rebuilt in Alpine rather than shipping Swiper for
 * one slider on one page.
 *
 * **The loop is seamless**, as Swiper's is: the track carries a copy of the
 * first slide at its end. Sliding past the last lands on the copy, and once
 * the transition ends the track jumps back to the real first slide without
 * one. Going back from the first does the same in reverse.
 *
 * Swiper's `disableOnInteraction` defaults to `true`, so legacy's autoplay
 * stopped for good once someone used an arrow or swiped; so does this. It
 * never starts for someone who asked for reduced motion.
 */
export default (count) => ({
	count,
	// The position on the track, the copy included: `count` is the copy.
	index: 0,
	animate: true,
	timer: null,
	touchX: null,

	init() {
		if (this.count > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			this.timer = setInterval(() => this.advance(), 5000);
		}
	},

	destroy() {
		clearInterval(this.timer);
	},

	/** Which real slide is showing, for the screen reader's count. */
	get current() {
		return this.index % this.count;
	},

	advance() {
		// The copy is showing until its transition ends; wait for the jump.
		if (this.index >= this.count) return;
		this.animate = true;
		this.index++;
	},

	retreat() {
		if (this.index > 0) {
			this.animate = true;
			this.index--;
			return;
		}

		// From the first slide: stand on the copy without moving, then slide
		// back from it to the last real one.
		this.animate = false;
		this.index = this.count;
		requestAnimationFrame(() => requestAnimationFrame(() => {
			this.animate = true;
			this.index--;
		}));
	},

	next() {
		this.stop();
		this.advance();
	},

	prev() {
		this.stop();
		this.retreat();
	},

	stop() {
		clearInterval(this.timer);
		this.timer = null;
	},

	/** After a slide onto the copy, back to the real first without a transition. */
	settle() {
		if (this.index === this.count) {
			this.animate = false;
			this.index = 0;
		}
	},

	touchStart(event) {
		this.touchX = event.touches[0].clientX;
	},

	touchEnd(event) {
		if (this.touchX === null) return;
		const distance = event.changedTouches[0].clientX - this.touchX;
		this.touchX = null;

		if (Math.abs(distance) > 40) {
			distance < 0 ? this.next() : this.prev();
		}
	},
});
