{{-- `/dev/zahlung/{session}`, Stripe's page played ([[FakeCheckoutController]]). A tool, not the site: no layout, no Vite. --}}
<!doctype html>
<html lang="de">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Testzahlung · Rechnung {{ $session['number'] }}</title>
	<style>
		body { margin: 0; font: 16px/1.5 system-ui, sans-serif; color: #222; background: #f6f6f6; }
		main { max-width: 26rem; margin: 10vh auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
		h1 { font-size: 1.1rem; margin: 0 0 .25rem; }
		p { margin: 0 0 1.5rem; color: #666; }
		strong { display: block; font-size: 2rem; margin-bottom: 1.5rem; }
		button { font: inherit; padding: .7rem 1.2rem; border-radius: 6px; border: 1px solid #ccc; background: #fff; cursor: pointer; }
		button[value=pay] { background: #635bff; border-color: #635bff; color: #fff; }
	</style>
</head>
<body>
<main>
	<h1>Testzahlung, Rechnung {{ $session['number'] }}</h1>
	<p>Statt Stripe: es wird nichts belastet.</p>
	<strong>CHF {{ number_format($session['amount'] / 100, 2, '.', "'") }}</strong>
	<form method="POST" action="{{ route('dev.payment.update', $id) }}">
		@csrf
		<button name="action" value="pay">Bezahlen</button>
		<button name="action" value="cancel">Abbrechen</button>
	</form>
</main>
</body>
</html>
