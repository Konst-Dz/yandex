<?php

namespace App\Modules\YandexIntegration\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProbeYandexCommand extends Command
{
    protected $signature = 'yandex:probe {url : Organization card URL} {--save : Store raw HTML fixture}';

    protected $description = 'Probe a Yandex Maps organization page: structure report, rating data and review pagination';

    public function handle(): int
    {
        $url = (string) $this->argument('url');
        $started = microtime(true);

        $response = Http::timeout(30)
            ->connectTimeout(10)
            ->withHeaders($this->headers())
            ->withOptions(['allow_redirects' => ['max' => 5]])
            ->get($url);

        $body = $response->body();
        $effective = $response->effectiveUri()?->__toString() ?? $url;

        Log::channel('parsing')->info('probe.page_fetched', [
            'url' => $url,
            'status' => $response->status(),
            'bytes' => strlen($body),
            'effective_url' => $effective,
            'duration_ms' => (int) ((microtime(true) - $started) * 1000),
        ]);

        if ($response->failed()) {
            $this->error("page fetch failed: HTTP {$response->status()}");

            return self::FAILURE;
        }

        if ($this->looksBlocked($body)) {
            $this->error('blocked: response looks like a captcha/robot page');

            return self::FAILURE;
        }

        $this->info("page: HTTP {$response->status()}, " . strlen($body) . ' bytes, effective: ' . $effective);

        $this->reportAggregateRating($body);
        $this->reportReviews($body);
        $this->reportPagination($body, $effective);

        if ($this->option('save')) {
            $path = 'yandex-probe/' . substr(md5($url), 0, 12) . '.html';
            \Storage::disk('local')->put($path, $body);
            $this->info("fixture saved: storage/app/{$path}");
        }

        return self::SUCCESS;
    }

    private function headers(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'ru-RU,ru;q=0.9',
        ];
    }

    private function looksBlocked(string $body): bool
    {
        return str_contains($body, 'SmartCaptcha')
            || str_contains($body, 'captcha-redirect')
            || str_contains($body, 'Checking your browser');
    }

    private function reportAggregateRating(string $body): void
    {
        $found = preg_match(
            '#<meta itemProp="reviewCount" content="(\d+)"#',
            $body,
            $reviewCount,
        ) === 1;
        $ratings = preg_match(
            '#<meta itemProp="ratingCount" content="(\d+)"#',
            $body,
            $ratingCount,
        ) === 1;
        $value = preg_match(
            '#<meta itemProp="ratingValue" content="([0-9.]+)"#',
            $body,
            $ratingValue,
        ) === 1;

        if ($found && $ratings && $value) {
            $this->info("aggregateRating: rating={$ratingValue[1]} ratingsCount={$ratingCount[1]} reviewCount={$reviewCount[1]}");
        } else {
            $this->warn('aggregateRating microdata NOT found — markup may have changed');
        }
    }

    private function reportReviews(string $body): void
    {
        $count = preg_match_all('#itemType="https?://schema\.org/Review"#', $body);

        $this->info("reviews on page: {$count}");

        if ($count === 0) {
            return;
        }

        $fields = [
            'author' => preg_match_all('#itemProp="author"#', $body),
            'datePublished' => preg_match_all('#itemProp="datePublished" content="([^"]+)"#', $body),
            'reviewBody' => preg_match_all('#itemProp="reviewBody"#', $body),
            'ratingValue' => preg_match_all('#itemProp="ratingValue" content="#', $body),
        ];

        foreach ($fields as $field => $found) {
            $this->line("  {$field}: {$found}");
        }

        preg_match('#itemProp="datePublished" content="([^"]+)"#', $body, $date);
        preg_match('#maps/user/([a-z0-9]+)#', $body, $user);

        $this->line('  sample date: ' . ($date[1] ?? 'none'));
        $this->line('  sample user uid: ' . ($user[1] ?? 'none'));
    }

    private function reportPagination(string $body, string $effective): void
    {
        preg_match_all('#href="([^"]*\?page=(\d+)[^"]*)"#', $body, $links);

        $pages = array_unique((array) ($links[2] ?? []));
        sort($pages, SORT_NUMERIC);

        if ($pages !== []) {
            $this->info('pagination links found: ?page=' . implode(', ?page=', $pages));
        } else {
            $this->warn('no ?page= links found — single page or pagination markup changed');
        }
    }
}
