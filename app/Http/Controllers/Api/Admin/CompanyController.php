<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCompanyRequest;
use App\Http\Resources\Admin\CompanyFormResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * *Firmen* ([[16-firmenkurse]]): the companies Firmenkurse are held for, kept
 * on the *Firmenkurse* screen and added from a Firmenkurs's picker.
 */
class CompanyController extends Controller
{
	public function index(): AnonymousResourceCollection
	{
		return CompanyFormResource::collection(Company::query()->withCount(['courses', 'invoices'])->orderBy('company')->get());
	}

	public function show(Company $company): CompanyFormResource
	{
		return new CompanyFormResource($company);
	}

	public function store(SaveCompanyRequest $request): JsonResponse
	{
		return (new CompanyFormResource(Company::create($request->companyAttributes())))->response()->setStatusCode(201);
	}

	/** An invoice keeps the address it was issued to, so a change reaches only the next one. */
	public function update(SaveCompanyRequest $request, Company $company): CompanyFormResource
	{
		$company->update($request->companyAttributes());

		return new CompanyFormResource($company);
	}

	/** Only one with no Firmenkurs and no invoice: its customer number is in the books. */
	public function destroy(Company $company): JsonResponse
	{
		abort_if($company->courses()->exists() || $company->invoices()->exists(), 422, 'Die Firma hat Firmenkurse oder Rechnungen und kann nicht gelöscht werden.');

		$company->delete();

		return response()->json(status: 204);
	}
}
