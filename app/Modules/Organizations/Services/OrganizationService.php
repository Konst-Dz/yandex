<?php

namespace App\Modules\Organizations\Services;

use App\Modules\Organizations\Contracts\OrganizationServiceInterface;
use App\Modules\Organizations\Events\OrganizationLinkSaved;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\Review;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrganizationService implements OrganizationServiceInterface
{
    public const REVIEWS_PER_PAGE = 50;

    public function saveLink(int $userId, string $url): Organization
    {
        $organization = Organization::query()->where('user_id', $userId)->first();

        $isNew = $organization === null;

        $organization ??= new Organization(['user_id' => $userId]);

        $cardChanged = !$isNew && $organization->url !== $url;

        if ($cardChanged) {
            $organization->reviews()->delete();
        }

        $organization->url = $url;
        $organization->status = Organization::STATUS_PENDING;
        $organization->save();

        OrganizationLinkSaved::dispatch($organization->id, $url);

        Log::info('organizations.link_saved', [
            'organization_id' => $organization->id,
            'host' => parse_url($url, PHP_URL_HOST),
            'is_new' => $isNew,
            'card_changed' => $cardChanged,
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
}
