<?php

namespace App\Modules\Organizations\Services;

use App\Modules\Organizations\Contracts\OrganizationServiceInterface;
use App\Modules\Organizations\Events\OrganizationLinkSaved;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\Review;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrganizationService implements OrganizationServiceInterface
{
    public const REVIEWS_PER_PAGE = 50;

    public function list(int $userId): Collection
    {
        return Organization::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function saveLink(int $userId, string $url): Organization
    {
        $organization = new Organization([
            'user_id' => $userId,
            'url' => $url,
            'status' => Organization::STATUS_PENDING,
        ]);
        $organization->save();

        OrganizationLinkSaved::dispatch($organization->id, $url);

        Log::info('organizations.link_saved', [
            'organization_id' => $organization->id,
            'host' => parse_url($url, PHP_URL_HOST),
        ]);

        return $organization;
    }

    public function updateLink(int $userId, int $organizationId, string $url): ?Organization
    {
        $organization = $this->find($userId, $organizationId);

        if ($organization === null) {
            return null;
        }

        if ($organization->url !== $url) {
            $organization->reviews()->delete();
            $organization->rating = null;
            $organization->ratings_count = 0;
            $organization->reviews_count = 0;
        }

        $organization->url = $url;
        $organization->status = Organization::STATUS_PENDING;
        $organization->save();

        OrganizationLinkSaved::dispatch($organization->id, $url);

        Log::info('organizations.link_updated', [
            'organization_id' => $organization->id,
            'url_changed' => $organization->wasChanged('url'),
        ]);

        return $organization;
    }

    public function find(int $userId, int $organizationId): ?Organization
    {
        return Organization::query()
            ->where('user_id', $userId)
            ->find($organizationId);
    }

    public function delete(int $userId, int $organizationId): bool
    {
        $organization = $this->find($userId, $organizationId);

        if ($organization === null) {
            return false;
        }

        $organization->delete();

        Log::info('organizations.deleted', ['organization_id' => $organizationId]);

        return true;
    }

    public function reviews(int $userId, int $organizationId, int $page): LengthAwarePaginator
    {
        $organization = $this->find($userId, $organizationId);

        if ($organization === null) {
            return new Paginator([], 0, self::REVIEWS_PER_PAGE, $page);
        }

        return $organization->reviews()
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->paginate(self::REVIEWS_PER_PAGE, ['*'], 'page', $page);
    }

    public function markParsing(int $organizationId): void
    {
        Organization::query()
            ->whereKey($organizationId)
            ->update([
                'status' => Organization::STATUS_PARSING,
                'failure_reason' => null,
            ]);
    }

    public function importParsed(int $organizationId, float $rating, int $ratingsCount, int $reviewCount, array $reviews): void
    {
        $organization = Organization::query()->find($organizationId);

        if ($organization === null) {
            Log::channel('parsing')->warning('organizations.import_skipped', [
                'organization_id' => $organizationId,
                'reason' => 'organization deleted',
            ]);

            return;
        }

        $before = [
            'rating' => $organization->rating,
            'ratings_count' => $organization->ratings_count,
            'reviews_count' => $organization->reviews_count,
        ];

        $started = microtime(true);
        $upserted = 0;

        DB::transaction(function () use ($organization, $before, $rating, $ratingsCount, $reviewCount, $reviews, &$upserted) {
            $organization->snapshots()->create([
                'payload' => [
                    'before' => $before,
                    'after' => [
                        'rating' => $rating,
                        'ratings_count' => $ratingsCount,
                        'reviews_count' => $reviewCount,
                    ],
                    'imported_at' => now()->toIso8601String(),
                ],
            ]);

            foreach (array_chunk($reviews, 100) as $chunk) {
                foreach ($chunk as $review) {
                    Review::query()->updateOrCreate(
                        [
                            'organization_id' => $organization->id,
                            'external_id' => (string) $review['external_id'],
                        ],
                        [
                            'author' => mb_substr((string) $review['author'], 0, 255),
                            'text' => mb_substr((string) $review['text'], 0, 16000),
                            'rating' => $review['rating'],
                            'reviewed_at' => $review['reviewed_at'] !== null
                                ? Carbon::parse($review['reviewed_at'])
                                : null,
                        ],
                    );
                    $upserted++;
                }
            }

            $organization->rating = $rating;
            $organization->ratings_count = $ratingsCount;
            $organization->reviews_count = $reviewCount;
            $organization->status = Organization::STATUS_READY;
            $organization->save();
        });

        Log::channel('parsing')->info('organizations.imported', [
            'organization_id' => $organization->id,
            'upserted' => $upserted,
            'reviews_count' => $reviewCount,
            'duration_ms' => (int) ((microtime(true) - $started) * 1000),
        ]);
    }

    public function markFailed(int $organizationId, string $reason): void
    {
        Organization::query()
            ->whereKey($organizationId)
            ->update([
                'status' => Organization::STATUS_ERROR,
                'failure_reason' => mb_substr($reason, 0, 255),
            ]);

        Log::channel('parsing')->warning('organizations.mark_failed', [
            'organization_id' => $organizationId,
            'reason' => $reason,
        ]);
    }

    public static function displayNameFromUrl(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $slug = null;

        if (preg_match('#/org/([a-z0-9_%-]+)/#i', $path, $m) === 1) {
            $slug = $m[1];
        }

        if ($slug === null) {
            return (string) parse_url($url, PHP_URL_HOST);
        }

        $name = str_replace('_', ' ', urldecode($slug));

        return Str::headline($name);
    }
}
