<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One table for everything uploaded ([[08-accounts]]).
 *
 * Replaces legacy's `images` (492 rows) *and* `files` + `fileables` (44 files,
 * 33 attachments) — three tables for one idea, and the `Invoice`/`RentalInvoice`
 * duplication pattern again. `images` and `files` carry the same eleven columns;
 * `images` adds orientation and four crop coordinates, and then, for no stated
 * reason, the two relate differently: `files` through a pivot, `images` through
 * a direct morph.
 *
 * The shape is ported from `github.com/marceli-to/forrerzimmermann.ch`, which is
 * already Laravel 13 and these conventions — Marcel's call on 2026-09-18, and it
 * answers both the media-pipeline question and the client's "image handling
 * (frontend output)" requirement. `league/glide` renders on demand; nothing is
 * pre-generated.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('media', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();

			// Nullable because an upload exists before it is attached to
			// anything — the crop UI works on it first.
			$table->nullableMorphs('mediable');

			$table->string('file');
			$table->string('original_name')->nullable();
			$table->string('mime_type')->nullable();
			$table->unsignedBigInteger('size')->nullable();

			$table->string('alt')->nullable();
			$table->string('caption')->nullable();

			// Legacy stored neither. It kept `orientation` as 'l'/'p' instead,
			// and that column **disagrees with the actual file on 49 of the 333
			// live images** — so it is not ported, it is derived. Dimensions are
			// read from the files at port time, which is why the chunk needs the
			// production storage snapshot to run at all.
			$table->unsignedInteger('width')->nullable();
			$table->unsignedInteger('height')->nullable();

			// `{x, y, w, h}` in **source pixels**, exactly what Glide's `crop`
			// parameter takes and exactly what legacy already stored — its
			// `double(16,12)` columns read like normalised fractions but hold
			// pixels, ranging to 4608×3124. So the port is a straight copy.
			//
			// Null means *no crop*. Legacy said that with `0,0,0,0` on 85 of its
			// 492 rows and special-cased the string to avoid producing a 1×1
			// image; porting those zeros across would break all 85.
			$table->json('crop')->nullable();

			// Art direction: a second row for the same subject, cropped for
			// phones. `<x-media.image>` picks between them with a media query.
			$table->string('variant', 20)->default('desktop');

			$table->boolean('is_teaser')->default(false);
			$table->boolean('is_og')->default(false);
			$table->integer('sort_order')->default(0);

			$table->timestamps();

			$table->index(['mediable_type', 'mediable_id', 'sort_order']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('media');
	}
};
