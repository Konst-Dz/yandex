<?php

namespace App\Modules\Organizations\Http\Controllers;

use App\Modules\Organizations\Contracts\OrganizationServiceInterface;
use App\Modules\Organizations\Http\Requests\SaveOrganizationLinkRequest;
use App\Modules\Organizations\Http\Resources\OrganizationResource;
use App\Modules\Organizations\Http\Resources\ReviewResource;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends ApiController
{
    public function __construct(private readonly OrganizationServiceInterface $organizations)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            OrganizationResource::collection($this->organizations->list((int) $request->user()->id)),
        );
    }

    public function store(SaveOrganizationLinkRequest $request): JsonResponse
    {
        $organization = $this->organizations->saveLink(
            (int) $request->user()->id,
            (string) $request->input('url'),
        );

        return ApiResponse::success(OrganizationResource::make($organization), 201);
    }

    public function show(Request $request, int $organization): JsonResponse
    {
        $found = $this->organizations->find((int) $request->user()->id, $organization);

        return $found === null
            ? ApiResponse::error('Not found.', 404)
            : ApiResponse::success(OrganizationResource::make($found));
    }

    public function update(SaveOrganizationLinkRequest $request, int $organization): JsonResponse
    {
        $updated = $this->organizations->updateLink(
            (int) $request->user()->id,
            $organization,
            (string) $request->input('url'),
        );

        return $updated === null
            ? ApiResponse::error('Not found.', 404)
            : ApiResponse::success(OrganizationResource::make($updated));
    }

    public function destroy(Request $request, int $organization): JsonResponse
    {
        $deleted = $this->organizations->delete((int) $request->user()->id, $organization);

        return $deleted
            ? response()->json()->setStatusCode(204)
            : ApiResponse::error('Not found.', 404);
    }

    public function reviews(Request $request, int $organization): JsonResponse
    {
        $paginator = $this->organizations->reviews(
            (int) $request->user()->id,
            $organization,
            max(1, (int) $request->query('page', '1')),
        );

        return ReviewResource::collection($paginator)->response();
    }
}
