<?php

namespace App\Modules\Organizations\Contracts;

use App\Modules\Organizations\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface OrganizationServiceInterface
{
    public function list(int $userId): Collection;

    public function saveLink(int $userId, string $url): Organization;

    public function updateLink(int $userId, int $organizationId, string $url): ?Organization;

    public function find(int $userId, int $organizationId): ?Organization;

    public function delete(int $userId, int $organizationId): bool;

    public function reviews(int $userId, int $organizationId, int $page): LengthAwarePaginator;

    public function markParsing(int $organizationId): void;

    public function importParsed(int $organizationId, float $rating, int $ratingsCount, int $reviewCount, array $reviews): void;

    public function markFailed(int $organizationId, string $reason): void;
}
