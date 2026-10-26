<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A *Firmenkurs* ([[16-firmenkurse]]): a course held for one company, and the
 * one invoice it raises.
 *
 * **The company is an address typed on it**, the fields of [[UserAddress]]:
 * no account, and no company record either. Each Firmenkurs is a one-off,
 * filed in Run My Accounts under its own customer number ([[RunMyAccounts]]).
 *
 * **Not an `Event`**, deliberately: it has no experts, no participants by
 * name, no seats and no page, so nothing that lists Veranstaltungen (the
 * site, search, reminders, the expert portal, the MCP tools) has to be taught
 * to leave it out.
 *
 * Its lines are what *Rechnung stellen* issues ([[IssueCompanyCourseInvoice]]),
 * and once an invoice stands the record is frozen ([[isLocked]]) until that
 * invoice is cancelled.
 */
class CompanyCourse extends Model
{
	use HasFactory;
	use HasUuid;
	use SoftDeletes;

	protected $fillable = [
		'company', 'first_name', 'last_name', 'street', 'street_no', 'zip', 'city', 'country_code',
		'course_id', 'title', 'location', 'participants', 'email', 'remarks', 'invoice_id',
	];

	public function course(): BelongsTo
	{
		return $this->belongsTo(Course::class);
	}

	public function dates(): HasMany
	{
		return $this->hasMany(CompanyCourseDate::class)->orderBy('date')->orderBy('time_start');
	}

	public function lines(): HasMany
	{
		return $this->hasMany(CompanyCourseLine::class)->orderBy('position');
	}

	public function invoice(): BelongsTo
	{
		return $this->belongsTo(Invoice::class);
	}

	/** The typed title, or the catalogue course's. */
	public function name(): string
	{
		return $this->title ?: (string) $this->course?->getTranslation('title', 'de');
	}

	/** @return array<string, string|null> The snapshot the invoice freezes ([[UserAddress::toSnapshot]]). */
	public function toSnapshot(): array
	{
		return $this->only([
			'first_name', 'last_name', 'company',
			'street', 'street_no', 'zip', 'city', 'country_code',
		]);
	}

	/** An invoice that is not cancelled freezes the lines it was issued from. */
	public function isLocked(): bool
	{
		return $this->invoice !== null && ! $this->invoice->isCancelled();
	}
}
