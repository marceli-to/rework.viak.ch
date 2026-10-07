import Swiper from 'swiper';
import { A11y, Autoplay, Pagination } from 'swiper/modules';
import 'swiper/css';

/**
 * The testimonials' slider ([[testimonial.slider]]), Swiper as the intro's
 * slider is ([[slider]]). It moves a page at a time, as many cards as are in
 * view: one, two from sm (700px), three from lg (1132px), the grid's gaps
 * between them. `rewind` rather than `loop`: a loop needs more cards than
 * two pages hold, and a page picks as few as it likes.
 *
 * No arrows; the dots are Swiper's pagination, drawn as round buttons by the
 * row's classes, the current one teal. With every card in view Swiper
 * locks, and the lock class hides the dots. Autoplay pauses under the
 * pointer, stops for good once someone picks a dot or swipes, as the intro's
 * does, and never starts for someone who asked for reduced motion.
 */
export default () => ({
	swiper: null,

	init() {
		const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		this.swiper = new Swiper(this.$refs.track, {
			modules: [A11y, Autoplay, Pagination],
			rewind: true,
			slidesPerView: 1,
			slidesPerGroup: 1,
			spaceBetween: 16,
			breakpoints: {
				700: { slidesPerView: 2, slidesPerGroup: 2, spaceBetween: 16 },
				1132: { slidesPerView: 3, slidesPerGroup: 3, spaceBetween: 40 },
			},
			autoplay: still ? false : { delay: 6000, disableOnInteraction: true, pauseOnMouseEnter: true },
			pagination: {
				el: this.$refs.dots,
				clickable: true,
				bulletElement: 'button',
				// Plain names: Swiper selects by them. The look is the dots
				// row's classes ([[testimonial.slider]]).
				bulletClass: 'dot',
				bulletActiveClass: 'is-active',
				lockClass: 'hidden',
			},
			a11y: {
				paginationBulletMessage: 'Zu Seite {{index}}',
				slideLabelMessage: '{{index}} von {{slidesLength}}',
				containerRoleDescriptionMessage: 'Karussell',
			},
		});
	},

	destroy() {
		this.swiper?.destroy();
	},
});
