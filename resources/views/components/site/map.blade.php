@props(['apiKey' => config('services.google_maps.key')])

{{--
	`web/components/content/map.blade.php` — the Google map on the Kontakt page,
	carried across as legacy has it: the Maps JavaScript API, legacy's grey
	styles and the black VA mark as the pin, at 16:10 (`ratio-container--16:10`).

	**It renders only with a key.** `GOOGLEMAPS_APIKEY` is the client's, and a
	Maps key is normally restricted to the live referrer — so locally and in
	tests this is the grey box at the map's size, which keeps the layout true
	without loading a script that would only draw Google's error.

	Loading a third-party script on page view is the same consent question as
	the Elfsight widgets (`Open-Questions.md` #18); legacy asks nothing, and
	neither does this.
--}}
<div class="relative aspect-[16/10] w-full bg-gray-200" id="js-map"></div>

@if ($apiKey)
	<script src="https://maps.googleapis.com/maps/api/js?key={{ $apiKey }}&callback=initMap&v=weekly" defer></script>
	<script>
		function initMap() {

			var styles = [
					{
					"elementType": "geometry",
					"stylers": [
						{
							"color": "#f5f5f5"
						}
					]
				},
				{
					"elementType": "labels.icon",
					"stylers": [
						{
							"visibility": "off"
						}
					]
				},
				{
					"elementType": "labels.text.fill",
					"stylers": [
						{
							"color": "#000000"
						}
					]
				},
				{
					"elementType": "labels.text.stroke",
					"stylers": [
						{
							"color": "#f5f5f5"
						}
					]
				},
				{
					"featureType": "administrative.land_parcel",
					"elementType": "labels.text.fill",
					"stylers": [
						{
							"color": "#bdbdbd"
						}
					]
				},
				{
					"featureType": "poi",
					"elementType": "geometry",
					"stylers": [
						{
							"color": "#eeeeee"
						}
					]
				},
				{
					"featureType": "poi",
					"elementType": "labels.text.fill",
					"stylers": [
						{
							"color": "#757575"
						}
					]
				},
				{
					"featureType": "poi.park",
					"elementType": "geometry",
					"stylers": [
						{
							"color": "#e5e5e5"
						}
					]
				},
				{
					"featureType": "poi.park",
					"elementType": "labels.text.fill",
					"stylers": [
						{
							"color": "#9e9e9e"
						}
					]
				},
				{
					"featureType": "road",
					"elementType": "geometry",
					"stylers": [
						{
							"color": "#ffffff"
						}
					]
				},
				{
					"featureType": "road.arterial",
					"elementType": "labels.text.fill",
					"stylers": [
						{
							"color": "#757575"
						}
					]
				},
				{
					"featureType": "road.highway",
					"elementType": "geometry",
					"stylers": [
						{
							"color": "#dadada"
						}
					]
				},
				{
					"featureType": "road.highway",
					"elementType": "labels.text.fill",
					"stylers": [
						{
							"color": "#000000"
						}
					]
				},
				{
					"featureType": "road.local",
					"elementType": "labels.text.fill",
					"stylers": [
						{
							"color": "#9e9e9e"
						}
					]
				},
				{
					"featureType": "transit.line",
					"elementType": "geometry",
					"stylers": [
						{
							"color": "#e5e5e5"
						}
					]
				},
				{
					"featureType": "transit.station",
					"elementType": "geometry",
					"stylers": [
						{
							"color": "#eeeeee"
						}
					]
				},
				{
					"featureType": "water",
					"elementType": "geometry",
					"stylers": [
						{
							"color": "#c9c9c9"
						}
					]
				},
				{
					"featureType": "water",
					"elementType": "labels.text.fill",
					"stylers": [
						{
							"color": "#9e9e9e"
						}
					]
				}
			];

			const latLng = { lat: 47.38904320283179, lng: 8.524579610686503 };

			const map = new google.maps.Map(document.getElementById("js-map"), {
				zoom: 14,
				center: latLng,
			});
			map.setOptions({styles: styles});

			const marker = new google.maps.Marker({
				position: latLng,
				map: map,
				icon: {
					anchor: new google.maps.Point(0,0),
					url: 'data:image/svg+xml;utf-8, \
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 54" fill="none"><rect x="0.886154" y="46.7674" width="60" height="10" rx="2" transform="rotate(-50 0.886154 46.7674)" fill="black"/><rect x="8.54659" y="0.804733" width="60" height="10" rx="2" transform="rotate(50 8.54659 0.804733)" fill="black"/></svg>',
					scaledSize: new google.maps.Size(30, 34)
				}
			});
		}

		window.initMap = initMap;
	</script>
@endif
