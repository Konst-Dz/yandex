<?php

namespace App\Modules\YandexIntegration\Services;

use App\Modules\YandexIntegration\Contracts\MapsParserInterface;
use App\Modules\YandexIntegration\Dto\OrganizationData;
use App\Modules\YandexIntegration\Dto\RatingSummary;
use App\Modules\YandexIntegration\Dto\ReviewDto;
use App\Modules\YandexIntegration\Exceptions\MarkupChangedException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class YandexMapsParser implements MapsParserInterface
{
    private const REVIEWS_PER_PAGE = 50;

    private const MAX_PAGES = 200;

    private const PAGE_DELAY_MS = 1500;

    public function __construct(private readonly YandexHttpClient $http)
    {
    }

    public function parse(string $url): OrganizationData
    {
        Log::channel('parsing')->info('parser.start', ['url' => $url]);

        $started = microtime(true);
        $summary = null;
        $reviews = [];
        $page = 1;

        do {
            $body = $this->http->get($this->reviewsPageUrl($url, $page));

            if ($body === null) {
                break;
            }

            $summary ??= $this->extractSummary($body);

            $found = $this->extractReviews($body);
            $reviews = array_merge($reviews, $found);

            Log::channel('parsing')->debug('parser.page', [
                'page' => $page,
                'reviews' => count($found),
                'collected' => count($reviews),
            ]);

            if (count($found) < self::REVIEWS_PER_PAGE) {
                break;
            }

            $page++;

            if ($page > self::MAX_PAGES) {
                throw new MarkupChangedException('pagination exceeded ' . self::MAX_PAGES . ' pages');
            }

            usleep(self::PAGE_DELAY_MS * 1000);
        } while (true);

        if ($summary === null) {
            throw new MarkupChangedException('aggregate rating microdata not found');
        }

        if ($summary->reviewCount > 0 && count($reviews) === 0) {
            throw new MarkupChangedException('review containers missing while reviewCount > 0');
        }

        if ($summary->reviewCount > 0 && count($reviews) < $summary->reviewCount) {
            Log::channel('parsing')->warning('parser.seo_pagination_cap', [
                'url' => $url,
                'collected' => count($reviews),
                'declared' => $summary->reviewCount,
            ]);
        }

        Log::channel('parsing')->info('parser.done', [
            'url' => $url,
            'rating' => $summary->rating,
            'ratings_count' => $summary->ratingsCount,
            'review_count' => $summary->reviewCount,
            'collected' => count($reviews),
            'duration_ms' => (int) ((microtime(true) - $started) * 1000),
        ]);

        return new OrganizationData($summary, $reviews);
    }

    private function reviewsPageUrl(string $url, int $page): string
    {
        $base = preg_replace('#/reviews/?$#', '', rtrim($url, '/'));
        $base = preg_replace('#\?.*#', '', (string) $base);

        return $page === 1
            ? $base . '/reviews/'
            : $base . '/reviews/?page=' . $page;
    }

    private function extractSummary(string $body): RatingSummary
    {
        $reviewCount = preg_match('#<meta itemProp="reviewCount" content="(\d+)"#', $body, $rc) === 1;
        $ratingsCount = preg_match('#<meta itemProp="ratingCount" content="(\d+)"#', $body, $rtc) === 1;
        $ratingValue = preg_match('#<meta itemProp="ratingValue" content="([0-9.]+)"#', $body, $rv) === 1;

        if (!$reviewCount || !$ratingsCount || !$ratingValue) {
            throw new MarkupChangedException('aggregate rating microdata not found');
        }

        return new RatingSummary((float) $rv[1], (int) $rtc[1], (int) $rc[1]);
    }

    private function extractReviews(string $body): array
    {
        $parts = preg_split('#<div[^>]*itemType="https?://schema\.org/Review"[^>]*>#', $body);
        array_shift($parts);

        $reviews = [];

        foreach ($parts as $part) {
            $date = $this->matchOne('#itemProp="datePublished" content="([^"]+)"#', $part);

            if ($date === null) {
                throw new MarkupChangedException('review datePublished missing');
            }

            $uid = $this->matchOne('#maps/user/([a-z0-9]+)#i', $part);
            $rating = $this->matchOne('#itemProp="ratingValue" content="([0-9.]+)"#', $part);
            $name = $this->matchOne('#itemProp="name"[^>]*>([^<]+)<#u', $part);
            $text = $this->extractBody($part) ?? '';

            $reviews[] = new ReviewDto(
                externalId: md5(($uid ?? '') . '|' . $date . '|' . ($name ?? '') . '|' . substr(md5($text), 0, 12)),
                author: $name ?? 'Unknown',
                text: $text,
                rating: $rating !== null ? (int) round((float) $rating) : null,
                reviewedAt: Carbon::parse($date)->utc(),
            );
        }

        return $reviews;
    }

    private function extractBody(string $part): ?string
    {
        $marker = 'itemProp="reviewBody"';
        $markerPos = strpos($part, $marker);

        if ($markerPos === false) {
            return null;
        }

        $open = strpos($part, '>', $markerPos);

        if ($open === false) {
            return null;
        }

        $open++;
        $depth = 1;
        $cursor = $open;
        $length = strlen($part);

        while ($cursor < $length && $depth > 0) {
            $nextOpen = stripos($part, '<div', $cursor);
            $nextClose = stripos($part, '</div', $cursor);

            if ($nextClose === false) {
                return null;
            }

            if ($nextOpen !== false && $nextOpen < $nextClose) {
                $depth++;
                $cursor = $nextOpen + 4;

                continue;
            }

            $depth--;
            $cursor = $nextClose + 5;
        }

        $inner = substr($part, $open, $cursor - $open - 5);
        $text = strip_tags($inner);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('#\s+#u', ' ', $text);

        return trim((string) $text);
    }

    private function matchOne(string $pattern, string $subject): ?string
    {
        return preg_match($pattern, $subject, $m) === 1 ? $m[1] : null;
    }
}
