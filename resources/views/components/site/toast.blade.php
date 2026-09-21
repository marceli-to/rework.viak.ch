@props(['variant' => 'error'])

{{--
	`components/_notification.scss:170` — `.notification.is-toast`, the message
	that drops into the top right and goes away when you click it. Legacy uses it
	for a failed login, for "added to basket", and through the booking flow.

	The anchoring is the fiddly part: from `sm` it is not `right: 16px` but
	`calc((100% - 1100px) / 2 + 16px)`, so it lines up with the **container's**
	right edge rather than the window's. Below `sm` it is simply inset 16.

	Sizes are legacy's: 16px, 18px from `bp-md`; 360px wide from `sm` and 480
	from `md`; 8px of padding, 16 left and right from `sm`; the message bold.
--}}
<div
	x-data="{ open: true }"
	x-show="open"
	@click="open = false"
	role="alert"
	@class([
		'fixed top-16 left-16 z-[1001] w-[calc(100%-32px)] cursor-pointer text-lg leading-[1.3] text-white sm:top-16 sm:left-auto sm:w-auto sm:max-w-360 sm:right-[calc((100%-1100px)/2+16px)] lg:max-w-480 lg:text-xl',
		'bg-danger' => $variant === 'error',
		'bg-success' => $variant === 'success',
	])
>
	<div @class([
		'flex items-center p-8 sm:px-16',
		'border border-danger' => $variant === 'error',
		'border border-success' => $variant === 'success',
	])>
		<div class="font-bold">{{ $slot }}</div>
	</div>
</div>
