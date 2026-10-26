<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Actions\CompanyCourses\IssueCompanyCourseInvoice;
use App\Actions\CompanyCourses\WithdrawCompanyCourseInvoice;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCompanyCourseRequest;
use App\Http\Resources\Admin\CompanyCourseFormResource;
use App\Models\CompanyCourse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * *Firmenkurse* ([[16-firmenkurse]]): a course held for one company, and its
 * one invoice. A handful a year, so the list is whole.
 */
class CompanyCourseController extends Controller
{
	private const WITH = ['course', 'dates', 'lines', 'invoice.document', 'invoice.companyCourse'];

	public function index(): AnonymousResourceCollection
	{
		return CompanyCourseFormResource::collection(CompanyCourse::query()->with(self::WITH)->latest('id')->get());
	}

	public function show(CompanyCourse $companyCourse): CompanyCourseFormResource
	{
		return new CompanyCourseFormResource($companyCourse->load(self::WITH));
	}

	public function store(SaveCompanyCourseRequest $request): JsonResponse
	{
		$course = DB::transaction(function () use ($request): CompanyCourse {
			$course = CompanyCourse::create($request->courseAttributes());
			$this->children($course, $request);

			return $course;
		});

		return (new CompanyCourseFormResource($course->load(self::WITH)))->response()->setStatusCode(201);
	}

	/** Frozen while its invoice stands: the lines are what was billed. */
	public function update(SaveCompanyCourseRequest $request, CompanyCourse $companyCourse): CompanyCourseFormResource
	{
		abort_if($companyCourse->isLocked(), 409, 'Der Firmenkurs ist verrechnet. Um ihn zu ändern, zuerst die Rechnung stornieren.');

		DB::transaction(function () use ($request, $companyCourse): void {
			$companyCourse->update($request->courseAttributes());
			$this->children($companyCourse, $request);
		});

		return new CompanyCourseFormResource($companyCourse->load(self::WITH));
	}

	/** Not while an invoice stands; a withdrawn one keeps pointing at it, so it is soft-deleted. */
	public function destroy(CompanyCourse $companyCourse): JsonResponse
	{
		abort_if($companyCourse->isLocked(), 422, 'Der Firmenkurs ist verrechnet und kann nicht gelöscht werden.');

		$companyCourse->delete();

		return response()->json(status: 204);
	}

	/** *Rechnung stellen* ([[IssueCompanyCourseInvoice]]). */
	public function invoice(CompanyCourse $companyCourse, IssueCompanyCourseInvoice $issue): CompanyCourseFormResource
	{
		$companyCourse->load(self::WITH);

		abort_if($companyCourse->isLocked(), 409, 'Der Firmenkurs ist bereits verrechnet.');

		if ($companyCourse->lines->isEmpty() || $companyCourse->lines->sum('net') <= 0) {
			throw ValidationException::withMessages(['lines' => 'Für eine Rechnung braucht es mindestens eine Position mit einem Betrag.']);
		}

		$issue->execute($companyCourse);

		return new CompanyCourseFormResource($companyCourse->refresh()->load(self::WITH));
	}

	/** *Rechnung stornieren* ([[WithdrawCompanyCourseInvoice]]). */
	public function withdraw(CompanyCourse $companyCourse, WithdrawCompanyCourseInvoice $withdraw): CompanyCourseFormResource
	{
		abort_unless($companyCourse->invoice?->isPending() ?? false, 409, 'Nur eine offene Rechnung kann storniert werden.');

		return new CompanyCourseFormResource($withdraw->execute($companyCourse)->load(self::WITH));
	}

	/** The days and lines, replaced whole: neither has an identity worth keeping. */
	private function children(CompanyCourse $course, SaveCompanyCourseRequest $request): void
	{
		$course->dates()->delete();
		$course->dates()->createMany($request->dates());

		$course->lines()->delete();
		$course->lines()->createMany($request->lines());
	}
}
