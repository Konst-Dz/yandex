<?php

namespace App\Modules\Organizations\Contracts;

use App\Modules\Organizations\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrganizationServiceInterface
{
    public function saveLink(int $userId, string $url): Organization;

    public function data(int $userId): ?Organization;

    public function reviews(int $userId, int $page): LengthAwarePaginator;

    public function markParsing(int $organizationId): void;

    public function importParsed(int $organizationId, float $rating, int $ratingsCount, int $reviewCount, array $reviews): void;

    public function markFailed(int $organizationId, string $reason): void;
}
