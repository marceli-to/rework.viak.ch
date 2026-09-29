{{-- `/dev/mails`, local only ([[MailPreviewController]]). A tool, not the site: no layout, no Vite. --}}
<!doctype html>
<html lang="de">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Mails · Vorschau</title>
	<style>
		body { margin: 0; font: 14px/1.5 system-ui, sans-serif; color: #222; background: #fff; }
		main { display: grid; grid-template-columns: minmax(18rem, 26rem) 1fr; height: 100vh; }
		nav { overflow-y: auto; border-right: 1px solid #ddd; padding: 1rem; }
		h1 { font-size: 1rem; margin: 0 0 .25rem; }
		p { margin: 0 0 1rem; color: #666; }
		ol { list-style: none; margin: 0; padding: 0; }
		a { display: block; padding: .5rem .6rem; border-radius: 4px; color: inherit; text-decoration: none; }
		a:hover, a.current { background: #f0f0f0; }
		small { display: block; color: #777; }
		.file { color: #946200; }
		iframe { width: 100%; height: 100%; border: 0; }
	</style>
</head>
<body>
<main>
	<nav>
		<h1>Mails ({{ $mails->count() }})</h1>
		<p>Aus Testdaten gerendert, nichts wird versendet. Vergleich: die Mails von visualisierungs-akademie.ch.</p>
		<ol>
			@foreach ($mails as $mail)
				<li>
					<a href="{{ route('dev.mails.show', $mail['key']) }}" target="preview" @class(['current' => $loop->first]) onclick="document.querySelectorAll('a.current').forEach(a => a.classList.remove('current')); this.classList.add('current')">
						{{ $mail['subject'] }}
						<small>{{ $mail['when'] }}</small>
						@foreach ($mail['files'] as $file)
							<small class="file">+ {{ $file }}</small>
						@endforeach
					</a>
				</li>
			@endforeach
		</ol>
	</nav>
	<iframe name="preview" src="{{ route('dev.mails.show', $mails->first()['key']) }}" title="Vorschau"></iframe>
</main>
</body>
</html>
