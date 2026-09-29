{{-- The dashboard's `ui/NoResults.vue`, legacy's `.no-results`: an empty list
     saying so, italic, 16px above and 32 from sm, 14 / 16 / 18px. --}}
<p {{ $attributes->class(['mt-16 text-md italic sm:mt-32 sm:text-lg lg:text-xl']) }}>{{ $slot }}</p>
