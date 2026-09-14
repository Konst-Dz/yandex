<?php

namespace App\Modules\Organizations\Services;

use App\Modules\Organizations\Contracts\OrganizationServiceInterface;
use App\Modules\Organizations\Events\OrganizationLinkSaved;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Log;

class OrganizationService implements OrganizationServiceInterface
{
    public const REVIEWS_PER_PAGE = 50;

    public function saveLink(int $userId, string $url): Organization
    {
        $organization = Organization::query()->where('user_id', $userId)->first();

        $isNew = $organization === null;

        $organization ??= new Organization(['user_id' => $userId]);
        $organization->url = $url;
        $organization->status = Organization::STATUS_PENDING;
        $organization->save();

        OrganizationLinkSaved::dispatch($organization->id, $url);

        Log::info('organizations.link_saved', [
            'organization_id' => $organization->id,
            'host' => parse_url($url, PHP_URL_HOST),
            'is_new' => $isNew,
        ]);

        return $organization;
    }

    public function data(int $userId): ?Organization
    {
        return Organization::query()->where('user_id', $userId)->first();
    }

    public function reviews(int $userId, int $page): LengthAwarePaginator
    {
        $organization = $this->data($userId);

        if ($organization === null) {
            return new Paginator([], 0, self::REVIEWS_PER_PAGE, $page);
        }

        return $organization->reviews()
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->paginate(self::REVIEWS_PER_PAGE, ['*'], 'page', $page);
    }
}
