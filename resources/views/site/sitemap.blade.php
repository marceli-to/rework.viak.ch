{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
{{-- [[SitemapController]]. The declaration is echoed so it is the first byte and no PHP tag. --}}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as $url)
	<url><loc>{{ $url }}</loc></url>
@endforeach
</urlset>
