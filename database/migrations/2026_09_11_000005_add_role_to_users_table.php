<?php

declare(strict_types=1);

use App\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
	public function up(): void
	{
		Schema::table('users', function (Blueprint $table) {
			$table->uuid()->nullable()->unique()->after('id');
			$table->string('role', 20)->default(Role::Student->value)->index()->after('email');
		});
	}

	public function down(): void
	{
		Schema::table('users', function (Blueprint $table) {
			$table->dropColumn(['uuid', 'role']);
		});
	}
};
