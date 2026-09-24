<?php

declare(strict_types=1);

namespace App\Forms;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use JsonSerializable;

/**
 * One field of a dashboard form — the field kit ([[04-content]], [[07-dashboard]]).
 *
 * A schema is a list of these, declared once in PHP beside the request it
 * validates. The same declaration gives **the request its rules and German
 * attribute names** and **the dashboard its form** (`GET
 * /api/admin/forms/{form}`), so a label, a `required` and a list of options
 * are written down in one place instead of three.
 *
 * The inventory is the closed one `04-content.md` counted, trimmed to what the
 * two source forms — course and testimonial — actually use; `07-dashboard.md`
 * checks it against every form still to come:
 *
 *   text · number · date · time · textarea · richtext · checkbox ·
 *   checkboxes · select · repeater · section · row · custom · hidden
 *
 * `section` is a collapsible, `row` lays fields side by side, `custom` hands a
 * spot to a component the kit does not know — the course's images. A course
 * date's days are a repeater drawn `inline`, one line per day.
 */
final class Field implements JsonSerializable
{
	private ?string $label = null;

	private bool $required = false;

	/** @var array<int, mixed>|Closure(?Model): array<int, mixed> */
	private array|Closure $rules = [];

	/** @var array<string, string> */
	private array $messages = [];

	/** @var array<string, mixed> */
	private array $props = [];

	/** @var array<int, Field> */
	private array $children = [];

	private function __construct(
		public readonly string $type,
		public readonly ?string $name = null,
	) {}

	public static function text(string $name): self
	{
		return (new self('text', $name))->rules(['string', 'max:255']);
	}

	public static function number(string $name): self
	{
		return (new self('number', $name))->rules(['numeric']);
	}

	public static function textarea(string $name): self
	{
		return (new self('textarea', $name))->rules(['string']);
	}

	public static function richtext(string $name): self
	{
		return (new self('richtext', $name))->rules(['string']);
	}

	public static function checkbox(string $name): self
	{
		return (new self('checkbox', $name))->rules(['boolean']);
	}

	/**
	 * Several of a list, picked by uuid.
	 *
	 * Given a model, the options are all of its rows by German `title` — the
	 * five course taxonomies. Given a closure, they are whatever it returns as
	 * `uuid => label`, and only those pass — a course date's experts, who are
	 * users holding the Expert role and not just any user.
	 *
	 * @param  class-string<Model>|Closure(): array<string, string>  $source
	 */
	public static function checkboxes(string $name, string|Closure $source): self
	{
		$read = $source instanceof Closure
			? $source
			: fn (): array => $source::query()->get()
				->mapWithKeys(fn ($term) => [$term->uuid => $term->getTranslation('title', 'de')])
				->sortBy(fn ($label) => $label, SORT_NATURAL | SORT_FLAG_CASE)
				->all();

		return (new self('checkboxes', $name))
			->rules(['array'])
			->with(['each' => fn () => ['string', Rule::in(array_keys($read()))]], false)
			->with(['options' => fn () => collect($read())->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()->all()]);
	}

	/**
	 * A calendar day — *Deadline Anmeldung*. Typed as legacy has it,
	 * `TT.MM.JJJJ`, and sent as `Y-m-d`; a day half typed goes as typed, so it
	 * fails here and not silently.
	 */
	public static function date(string $name): self
	{
		return (new self('date', $name))->rules(['date_format:Y-m-d']);
	}

	/** A time of day — a course day's *von* and *bis*. Typed `hh.mm`, sent `H:i`. */
	public static function time(string $name): self
	{
		return (new self('time', $name))->rules(['date_format:H:i']);
	}

	/**
	 * One of a list. `value => label`, or grouped — `group => [value =>
	 * label]` draws each group under its heading, as *Bezieht sich auf* lists
	 * courses and software. A closure is read when the form is served or
	 * validated, not at boot. Only a listed value passes; an empty one is the
	 * `placeholder`, if the field has one.
	 *
	 * @param  array<string|int, mixed>|Closure(): array<string|int, mixed>  $options
	 */
	public static function select(string $name, array|Closure $options): self
	{
		$read = fn (): array => $options instanceof Closure ? $options() : $options;

		return (new self('select', $name))
			->rules(fn () => [Rule::in(self::values($read()))])
			->with(['options' => fn () => self::choices($read())]);
	}

	/**
	 * Every value a select offers, its groups flattened.
	 *
	 * @param  array<string|int, mixed>  $options
	 * @return array<int, string|int>
	 */
	private static function values(array $options): array
	{
		return collect($options)->flatMap(fn ($label, $value) => is_array($label) ? array_keys($label) : [$value])->values()->all();
	}

	/**
	 * The options as the dashboard draws them: `{ value, label }`, or a group
	 * `{ label, options }`.
	 *
	 * @param  array<string|int, mixed>  $options
	 * @return array<int, array<string, mixed>>
	 */
	private static function choices(array $options): array
	{
		return collect($options)->map(fn ($label, $value) => is_array($label)
			? ['label' => $value, 'options' => self::choices($label)]
			: ['value' => $value, 'label' => $label])->values()->all();
	}

	/**
	 * A list of the same small form — a course's videos. Validated per row as
	 * `name.*.child`; `blank` is what *add* appends.
	 *
	 * @param  array<int, Field>  $fields
	 * @param  array<string, mixed>  $blank
	 */
	public static function repeater(string $name, array $fields, array $blank): self
	{
		$field = (new self('repeater', $name))->rules(['array'])->with(['blank' => $blank]);
		$field->children = $fields;

		return $field;
	}

	/** A collapsible around its fields — legacy's form sections. @param  array<int, Field>  $fields */
	public static function section(string $label, array $fields): self
	{
		$field = (new self('section'))->label($label);
		$field->children = $fields;

		return $field;
	}

	/** Fields side by side, closed by a rule — *Onlinekurs* / *Publizieren*. @param  array<int, Field>  $fields */
	public static function row(array $fields): self
	{
		$field = new self('row');
		$field->children = $fields;

		return $field;
	}

	/**
	 * Carried and validated but never drawn — a repeater row's `uuid`, which is
	 * how a saved video is told from a new one. Leave it out and validation
	 * drops it, and every video comes back as new.
	 */
	public static function hidden(string $name): self
	{
		return (new self('hidden', $name))->rules(['string']);
	}

	/** A spot for a component the kit does not draw; it neither sends nor validates. */
	public static function custom(string $component): self
	{
		return (new self('custom'))->with(['component' => $component]);
	}

	public function label(string $label): self
	{
		$this->label = $label;

		return $this;
	}

	public function required(bool $required = true): self
	{
		$this->required = $required;

		return $this;
	}

	/**
	 * Rules beyond the type's own. A closure is given the record being edited,
	 * or null on create — a unique rule ignoring itself.
	 *
	 * @param  array<int, mixed>|Closure(?Model): array<int, mixed>  $rules
	 */
	public function rules(array|Closure $rules): self
	{
		if ($rules instanceof Closure || $this->rules instanceof Closure) {
			$own = $this->rules;
			$this->rules = fn (?Model $record) => [
				...($own instanceof Closure ? $own($record) : $own),
				...($rules instanceof Closure ? $rules($record) : $rules),
			];
		} else {
			$this->rules = [...$this->rules, ...$rules];
		}

		return $this;
	}

	/** @param  array<int, mixed>  $rules  applied to each item of an array field */
	public function eachRule(array $rules): self
	{
		return $this->with(['each' => $rules], false);
	}

	/** A German message for one rule — *Bitte mindestens eine Kategorie wählen.* */
	public function message(string $rule, string $message): self
	{
		$this->messages[$rule] = $message;

		return $this;
	}

	/**
	 * What the component needs besides the basics: `rows`, `mono`, `columns`,
	 * `add`. A closure is resolved when the schema is served, so options are
	 * read then and not at boot.
	 *
	 * @param  array<string, mixed>  $props
	 */
	public function with(array $props, bool $public = true): self
	{
		foreach ($props as $key => $value) {
			$this->props[$public ? $key : "_{$key}"] = $value;
		}

		return $this;
	}

	/** @return array<int, Field> */
	public function children(): array
	{
		return $this->children;
	}

	/**
	 * This field's rules, keyed by path, children included.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function validation(?Model $record, string $prefix = ''): array
	{
		if (in_array($this->type, ['section', 'row'], true)) {
			return collect($this->children)->flatMap(fn (Field $child) => $child->validation($record, $prefix))->all();
		}

		if ($this->name === null) {
			return [];
		}

		$path = $prefix.$this->name;
		$own = $this->rules instanceof Closure ? ($this->rules)($record) : $this->rules;
		$rules = [$path => [$this->required ? 'required' : 'nullable', ...$own]];

		if ($this->type === 'checkboxes' && $this->required) {
			$rules[$path][] = 'min:1';
		}

		if (isset($this->props['_each'])) {
			$each = $this->props['_each'];
			$rules["{$path}.*"] = $each instanceof Closure ? $each() : $each;
		}

		foreach ($this->children as $child) {
			$rules = [...$rules, ...$child->validation($record, "{$path}.*.")];
		}

		return $rules;
	}

	/** @return array<string, string> path.rule => message */
	public function messages(string $prefix = ''): array
	{
		$path = $prefix.$this->name;
		$own = collect($this->messages)->mapWithKeys(fn ($message, $rule) => ["{$path}.{$rule}" => $message])->all();

		$childPrefix = $this->type === 'repeater' ? "{$path}.*." : $prefix;

		return array_merge($own, ...array_map(fn (Field $child) => $child->messages($childPrefix), $this->children));
	}

	/** @return array<string, string> path => label, for `:attribute` */
	public function attributes(string $prefix = ''): array
	{
		$childPrefix = $this->type === 'repeater' ? "{$prefix}{$this->name}.*." : $prefix;
		$own = $this->name !== null && $this->label !== null ? [$prefix.$this->name => $this->label] : [];

		// One box of a group is named after the group: *Experten*, not `experts.0`.
		if ($own !== [] && $this->type === 'checkboxes') {
			$own["{$prefix}{$this->name}.*"] = $this->label;
		}

		return array_merge($own, ...array_map(fn (Field $child) => $child->attributes($childPrefix), $this->children));
	}

	/** What the dashboard draws — the public props, with closures resolved. */
	public function jsonSerialize(): array
	{
		$props = collect($this->props)
			->reject(fn ($value, $key) => str_starts_with((string) $key, '_'))
			->map(fn ($value) => $value instanceof Closure ? $value() : $value)
			->all();

		return array_filter([
			'type' => $this->type,
			'name' => $this->name,
			'label' => $this->label,
			'required' => $this->required ?: null,
			...$props,
			'fields' => $this->children ?: null,
		], fn ($value) => $value !== null);
	}
}
