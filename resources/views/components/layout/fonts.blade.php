{{--
	Effra from Adobe Fonts, kit `bmx5jih` (`resources/css/partials/fonts.css`).

	A `<link>` in the head, not an `@import` at the top of `app.css`
	(2026-10-30, Lighthouse on staging): imported, the browser met the kit only
	once our stylesheet had arrived, then its fonts after that, three round
	trips in a row before any text. Linked, the kit loads beside our CSS, and
	the preconnects open both Typekit hosts (CSS on `use`, fonts on `p`) early.

	`font-display` is the kit's to set, in the Adobe Fonts project settings;
	it is `auto` today.
--}}
<link rel="preconnect" href="https://use.typekit.net" crossorigin>
<link rel="preconnect" href="https://p.typekit.net" crossorigin>
<link rel="stylesheet" href="https://use.typekit.net/bmx5jih.css">
