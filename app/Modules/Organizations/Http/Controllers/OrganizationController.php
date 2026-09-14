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
        $organization = $this->organizations->data((int) $request->user()->id);

        return ApiResponse::success(
            $organization !== null ? OrganizationResource::make($organization) : null,
        );
    }

    public function saveLink(SaveOrganizationLinkRequest $request): JsonResponse
    {
        $organization = $this->organizations->saveLink(
            (int) $request->user()->id,
            (string) $request->input('url'),
        );

        return ApiResponse::success(OrganizationResource::make($organization));
    }

    public function reviews(Request $request): JsonResponse
    {
        $paginator = $this->organizations->reviews(
            (int) $request->user()->id,
            max(1, (int) $request->query('page', '1')),
        );

        return ReviewResource::collection($paginator)->response();
    }
}
