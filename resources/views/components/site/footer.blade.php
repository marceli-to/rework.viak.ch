{{-- Address and social links, as the legacy footer carries them. --}}
<footer class="mt-24 border-t border-line bg-paper">
	<div class="mx-auto grid w-full max-w-(--container-site) gap-10 px-4 py-14 sm:grid-cols-2 sm:px-8">
		<div>
			<h2 class="text-lg font-medium text-ink">Kontakt</h2>
			<p class="mt-4 leading-relaxed">
				Visualisierungs-Akademie Schweiz GmbH<br>
				Limmatstrasse 291<br>
				CH-8005 Zürich
			</p>
			<p class="mt-4">
				<a href="tel:+41435014040" class="hover:text-teal-dark">+41 43 501 40 40</a><br>
				<a href="mailto:hallo@visualisierungs-akademie.ch" class="hover:text-teal-dark">hallo@visualisierungs-akademie.ch</a>
			</p>
		</div>

		<div class="sm:text-right">
			<nav class="flex gap-6 sm:justify-end" aria-label="Social">
				<a href="https://www.instagram.com/viak.ch/" target="_blank" rel="noopener" class="hover:text-teal-dark">Instagram</a>
				<a href="https://www.facebook.com/ViAkSchweiz" target="_blank" rel="noopener" class="hover:text-teal-dark">Facebook</a>
			</nav>
			<p class="mt-8 text-sm text-faint">
				© {{ date('Y') }} Visualisierungs-Akademie Schweiz GmbH
			</p>
		</div>
	</div>
</footer>
