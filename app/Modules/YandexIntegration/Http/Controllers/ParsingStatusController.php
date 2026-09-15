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

    public function __invoke(Request $request, int $organization): JsonResponse
    {
        $found = $this->organizations->find((int) $request->user()->id, $organization);

        if ($found === null) {
            return ApiResponse::error('Not found.', 404);
        }

        return ApiResponse::success([
            'status' => $found->status,
            'reason' => $found->status === Organization::STATUS_ERROR
                ? $found->failure_reason
                : null,
        ]);
    }
}
