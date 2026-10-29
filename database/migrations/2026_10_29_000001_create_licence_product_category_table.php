<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A licence product's categories ([[14-angebote]]): the client wants them on
 * both levels, the Angebot and its products, and a plugin sold under
 * Rhinoceros (Bongo) need not share Rhino's. Built as `software_category` is.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('licence_product_category', function (Blueprint $table) {
			$table->foreignId('licence_product_id')->constrained()->cascadeOnDelete();
			$table->foreignId('category_id')->constrained()->cascadeOnDelete();
			$table->primary(['licence_product_id', 'category_id']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('licence_product_category');
	}
};
