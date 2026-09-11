<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
	public function up(): void
	{
		Schema::create('locations', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->json('description');
			$table->json('address');
			$table->text('map')->nullable();
			$table->boolean('publish')->default(true);
			$table->timestamps();
			$table->softDeletes();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('locations');
	}
};
