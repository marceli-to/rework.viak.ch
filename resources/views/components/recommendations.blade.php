@props(['courses', 'software'])

{{--
	*Das könnte dich auch interessieren* ([[Recommendations]]): the courses
	and software VIAK picks, in *Beliebte Angebote*'s grid and cards
	(`landing/_featured`), each tagged *Kurs* or *Software*. Used under a
	software's page and in the basket.
--}}
<div {{ $attributes->class(['grid grid-cols-12 gap-16 lg:gap-40']) }}>
	@foreach ($courses as $course)
		<x-card.course :course="$course" kind="Kurs" class="col-span-6 sm:col-span-4" />
	@endforeach

	@foreach ($software as $item)
		<x-card.software :software="$item" kind="Software" class="col-span-6 sm:col-span-4" />
	@endforeach
</div>
