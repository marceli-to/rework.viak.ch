<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The licence catalogue ([[05-licences]]), the shape the client's answers of
 * 2026-10-08 left: three levels, not two.
 *
 *   software            the group, Rhino, V-Ray… (what courses already hang off)
 *   licence_products    V-Ray, V-Ray Render Node, Bongo 2… with a manufacturer
 *   licence_variants    one dropdown entry: Solo named, Update, 5 Stück…
 *
 * A variant is one row of the client's list, so it carries what the row
 * carries: the article number, the **net** price, the licence type, named or
 * floating, the platforms and the remark. One product mixes perpetual and
 * subscription variants (formZ 10), and Windows-only ones beside the rest
 * (Enscape Collection), so none of that can live on the product.
 *
 * - **`listed`** is on the variant: false is an EDU or lab licence VIAK enters
 *   by hand when one is ordered by mail or phone (#36). Never on the site.
 * - **`price`** is not nullable: every row has one, a demo is 0 (#35). There is
 *   no "Preis auf Anfrage" in the catalogue; a one-off price is the blank line
 *   on an admin-made order.
 * - **`three_years_on_request`** prints "3-Jahreslizenz auf Anfrage erhältlich"
 *   on the product (#34). Not a variant, not priced.
 * - **`hosts`** is the host software a plugin is ordered for (Maxwell V5,
 *   RealFlow Plugin): one price, the choice frozen on the order line later.
 * - **`min_quantity`** is the Teams licences' 3 (#39); no maximum anywhere.
 *
 * `manufacturers` has the taxonomies' shape, so *Einstellungen* edits it.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('manufacturers', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->json('title');
			$table->unsignedSmallInteger('order')->default(0);
			$table->boolean('publish')->default(true);
			$table->timestamps();
			$table->softDeletes();
		});

		Schema::create('licence_products', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->foreignId('software_id')->constrained('software')->restrictOnDelete();
			$table->foreignId('manufacturer_id')->constrained()->restrictOnDelete();
			$table->json('title');
			$table->json('slug');
			$table->json('description')->nullable();
			$table->json('hosts')->nullable();
			$table->boolean('three_years_on_request')->default(false);
			$table->boolean('publish')->default(true);
			$table->unsignedSmallInteger('order')->default(0);
			$table->timestamps();
			$table->softDeletes();

			$table->index(['software_id', 'order']);
		});

		Schema::create('licence_variants', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->foreignId('licence_product_id')->constrained()->cascadeOnDelete();
			$table->json('title');
			$table->string('sku', 32)->index();
			$table->decimal('price', 10, 2);
			$table->string('licence_type', 16)->nullable();
			$table->string('access', 16)->nullable();
			$table->json('platforms')->nullable();
			$table->json('note')->nullable();
			$table->unsignedSmallInteger('min_quantity')->nullable();
			$table->boolean('listed')->default(true);
			$table->unsignedSmallInteger('order')->default(0);
			$table->timestamps();
			$table->softDeletes();

			$table->index(['licence_product_id', 'order']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('licence_variants');
		Schema::dropIfExists('licence_products');
		Schema::dropIfExists('manufacturers');
	}
};
