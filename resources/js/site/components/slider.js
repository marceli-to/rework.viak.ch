import Swiper from 'swiper';
import { A11y, Autoplay, Keyboard, Navigation } from 'swiper/modules';
import 'swiper/css';

/**
 * The homepage intro's slider ([[media.slider]]): **Swiper, as legacy has
 * it** (Marcel, 2026-10-07, after a first pass in plain Alpine), set up as
 * legacy's `frontend/home.js` does: `loop`, `autoplay: { delay: 5000 }`, the
 * two arrows, Swiper's own 300ms slide. Legacy's `pagination` option is
 * left out: its markup never had the element, so it drew nothing.
 *
 * Only Swiper's core stylesheet is loaded; the arrows are the component's
 * own buttons, handed to the Navigation module, so none of Swiper's arrow
 * CSS or icon font is needed. Keyboard and A11y are new and cost nothing:
 * the arrow keys work while the slider is in view, and the buttons and
 * slides get Swiper's German labels below.
 *
 * Autoplay stops for good once someone uses an arrow or swipes: legacy's
 * Swiper 8 did that by default, and from Swiper 9 the default is `false`,
 * so it is stated here. It never starts for
 * someone who asked for reduced motion.
 */
export default () => ({
	swiper: null,

	init() {
		const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		this.swiper = new Swiper(this.$refs.track, {
			modules: [A11y, Autoplay, Keyboard, Navigation],
			loop: true,
			autoplay: still ? false : { delay: 5000, disableOnInteraction: true },
			keyboard: { enabled: true, onlyInViewport: true },
			navigation: { prevEl: this.$refs.prev, nextEl: this.$refs.next },
			a11y: {
				prevSlideMessage: 'Vorheriges Bild',
				nextSlideMessage: 'Nächstes Bild',
				slideLabelMessage: '{{index}} von {{slidesLength}}',
				containerRoleDescriptionMessage: 'Karussell',
			},
		});
	},

	destroy() {
		this.swiper?.destroy();
	},
});
