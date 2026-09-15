<?php

namespace App\Modules\YandexIntegration\Services;

use App\Modules\YandexIntegration\Exceptions\BlockedByAntibotException;
use App\Modules\YandexIntegration\Exceptions\EmptyResponseException;
use App\Modules\YandexIntegration\Exceptions\SourceUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class YandexHttpClient
{
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36';

    private const BLOCK_MARKERS = ['SmartCaptcha', 'captcha-redirect', 'Checking your browser'];

    public function get(string $url): ?string
    {
        try {
            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->withHeaders([
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'ru-RU,ru;q=0.9',
                ])
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->get($url);
        } catch (ConnectionException $e) {
            throw new SourceUnavailableException("connection failed for {$url}: {$e->getMessage()}", 0, $e);
        }

        if ($response->status() === 404) {
            return null;
        }

        if ($response->status() === 403 || $response->status() === 429) {
            throw new BlockedByAntibotException("HTTP {$response->status()} for {$url}");
        }

        if ($response->failed()) {
            throw new SourceUnavailableException("HTTP {$response->status()} for {$url}");
        }

        $body = $response->body();

        if (trim($body) === '') {
            throw new EmptyResponseException("empty response body for {$url}");
        }

        foreach (self::BLOCK_MARKERS as $marker) {
            if (str_contains($body, $marker)) {
                throw new BlockedByAntibotException("captcha page returned for {$url}");
            }
        }

        return $body;
    }
}
