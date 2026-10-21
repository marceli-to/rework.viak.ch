<?php

declare(strict_types=1);

namespace App\Console\Commands\Port;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

/**
 * Checks what the ports leave behind in the one place no foreign key can
 * ([[08-accounts]], `.rework/Deploy.md`): every `*_type` / `*_id` pair.
 *
 * Twice a link failed quietly. `event_expert` was empty for weeks, and 13
 * `media` rows pointed at `Event` while `Event` had no `media()` to read them.
 * A pivot has foreign keys and cannot point at nothing; a polymorphic column
 * can, and nothing complains. So, for every type written into one:
 *
 * - **the class exists**, or the row belongs to nothing;
 * - **the owner exists**. `port:courses` clears and rebuilds `events`,
 *   `courses` and `software` with new ids, so a re-run against a database
 *   that has anything attached to them since leaves it pointing at nothing;
 * - **a relation reads it back**, named. *Only from its own side* is right
 *   for some (a booking's confirmation is found through the user's
 *   documents, a recommendation through its owner's list) and is the thing
 *   to look at for a new type.
 *
 * Fails on the first two and only lists the third. Reads only.
 */
class VerifyPort extends Command
{
	protected $signature = 'port:verify';

	protected $description = 'Check every polymorphic link for missing classes, missing owners and missing readers';

	public function handle(): int
	{
		$readers = $this->readers();
		$failed = false;
		$rows = [];

		foreach (Schema::getTableListing(DB::getDatabaseName(), schemaQualified: false) as $table) {
			$columns = Schema::getColumnListing($table);

			foreach ($columns as $column) {
				if (! str_ends_with($column, '_type') || ! in_array($id = substr($column, 0, -5).'_id', $columns, true)) {
					continue;
				}

				foreach (DB::table($table)->whereNotNull($column)->selectRaw("{$column} as t, count(*) as n")->groupBy($column)->get() as $group) {
					$type = (string) $group->t;
					$problem = null;

					if (! class_exists($type) || ! is_subclass_of($type, Model::class)) {
						$problem = 'no such model';
					} else {
						$owners = (new $type)->getTable();
						$orphans = DB::table($table)->where($column, $type)
							->whereNotExists(fn ($query) => $query->from($owners)->whereColumn("{$owners}.id", "{$table}.{$id}"))
							->count();
						$problem = $orphans > 0 ? "{$orphans} with no owner" : null;
					}

					$failed = $failed || $problem !== null;
					$read = $readers["{$table}.{$column}={$type}"] ?? [];

					$rows[] = [
						"{$table}.{$column}",
						class_basename($type),
						$group->n,
						$problem ?? 'ok',
						$read === [] ? 'only from its own side' : implode(', ', $read),
					];
				}
			}
		}

		$this->table(['column', 'type', 'rows', 'owners', 'read by'], $rows);

		if ($failed) {
			$this->error('Rows point at nothing. Each one is a decision: re-port in order, or delete them.');

			return self::FAILURE;
		}

		$this->info('Every polymorphic row has its owner.');

		return self::SUCCESS;
	}

	/**
	 * Every relation on every model that reads a polymorphic column, keyed
	 * `table.column=Class`.
	 *
	 * @return array<string, array<int, string>>
	 */
	private function readers(): array
	{
		$readers = [];

		foreach (glob(app_path('Models/*.php')) ?: [] as $file) {
			$class = 'App\\Models\\'.basename($file, '.php');
			$reflection = new ReflectionClass($class);

			if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
				continue;
			}

			foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
				$returns = $method->getReturnType();

				if ($method->class !== $class || $method->isStatic() || $method->getNumberOfRequiredParameters() > 0
					|| ! $returns instanceof ReflectionNamedType || ! is_subclass_of($returns->getName(), Relation::class)) {
					continue;
				}

				try {
					$relation = (new $class)->{$method->name}();
				} catch (Throwable) {
					continue;
				}

				$key = match (true) {
					$relation instanceof MorphOneOrMany => $relation->getRelated()->getTable().'.'.$relation->getMorphType().'='.$relation->getMorphClass(),
					$relation instanceof MorphToMany => $relation->getTable().'.'.$relation->getMorphType().'='.$relation->getMorphClass(),
					default => null,
				};

				if ($key !== null) {
					$readers[$key][] = class_basename($class).'::'.$method->name;
				}
			}
		}

		return $readers;
	}
}
