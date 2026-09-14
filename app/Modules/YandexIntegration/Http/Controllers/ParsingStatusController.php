<?php

namespace App\Modules\YandexIntegration\Http\Controllers;

use App\Modules\Organizations\Contracts\OrganizationServiceInterface;
use App\Modules\Organizations\Models\Organization;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParsingStatusController extends ApiController
{
    public function __construct(private readonly OrganizationServiceInterface $organizations)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $organization = $this->organizations->data((int) $request->user()->id);

        if ($organization === null) {
            return ApiResponse::success([
                'status' => 'idle',
                'reason' => null,
            ]);
        }

        return ApiResponse::success([
            'status' => $organization->status,
            'reason' => $organization->status === Organization::STATUS_ERROR
                ? $organization->failure_reason
                : null,
        ]);
    }
}
