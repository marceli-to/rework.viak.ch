<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\SoftwareFormResource;
use App\Models\Software;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * **An Angebot's logo** ([[Software::logoUrl]], [[14-angebote]] *Logos*):
 * *Logo* in the software form's *Einstellungen*, saved at once as the images
 * are. Stored as it comes, PNG with its transparency or SVG, under a name
 * that changes with the file so no browser keeps the old one.
 */
class SoftwareLogoController extends Controller
{
	public function store(Request $request, Software $software): SoftwareFormResource
	{
		$request->validate([
			'file' => ['required', 'file', 'mimes:png,svg,webp,jpg,jpeg', 'max:2048'],
		], [], ['file' => 'Logo']);

		$file = $request->file('file');
		$name = Str::slug((string) $software->getTranslation('slug', 'de')).'-'.Str::lower(Str::random(8)).'.'.$file->getClientOriginalExtension();
		$file->storeAs(Software::LOGOS, $name, 'public');

		$software->replaceLogo($name);

		return new SoftwareFormResource($software->refresh()->load(['categories', 'hosts']));
	}

	public function destroy(Software $software): SoftwareFormResource
	{
		$software->replaceLogo(null);

		return new SoftwareFormResource($software->refresh()->load(['categories', 'hosts']));
	}
}
