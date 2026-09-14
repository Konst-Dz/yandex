<?php

namespace App\Modules\YandexIntegration\Jobs;

use App\Modules\Organizations\Contracts\OrganizationServiceInterface;
use App\Modules\YandexIntegration\Contracts\MapsParserInterface;
use App\Modules\YandexIntegration\Dto\ReviewDto;
use App\Modules\YandexIntegration\Events\OrganizationDataUpdated;
use App\Modules\YandexIntegration\Exceptions\MarkupChangedException;
use App\Modules\YandexIntegration\Exceptions\ParsingException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ParseOrganizationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public array $backoff = [30, 120];

    public function __construct(
        public int $organizationId,
        public string $url,
    ) {
    }

    public function handle(MapsParserInterface $parser, OrganizationServiceInterface $organizations): void
    {
        $lock = Cache::lock('parse:' . $this->organizationId, 600);

        if (!$lock->get()) {
            Log::channel('parsing')->warning('parser.job_locked', [
                'organization_id' => $this->organizationId,
            ]);

            return;
        }

        try {
            Log::channel('parsing')->info('parser.job_start', [
                'organization_id' => $this->organizationId,
                'url' => $this->url,
                'attempt' => $this->job?->attempts() ?? 1,
            ]);

            $organizations->markParsing($this->organizationId);

            $data = $parser->parse($this->url);

            $organizations->importParsed(
                $this->organizationId,
                $data->summary->rating,
                $data->summary->ratingsCount,
                $data->summary->reviewCount,
                array_map(static fn (ReviewDto $review) => [
                    'external_id' => $review->externalId,
                    'author' => $review->author,
                    'text' => $review->text,
                    'rating' => $review->rating,
                    'reviewed_at' => $review->reviewedAt?->toIso8601String(),
                ], $data->reviews),
            );

            OrganizationDataUpdated::dispatch($this->organizationId);

            Log::channel('parsing')->info('parser.job_done', [
                'organization_id' => $this->organizationId,
            ]);
        } catch (MarkupChangedException $e) {
            $organizations->markFailed($this->organizationId, $e->getMessage());
            $this->fail($e);
        } catch (ParsingException $e) {
            $organizations->markFailed($this->organizationId, $e->getMessage());
            throw $e;
        } finally {
            $lock->release();
        }
    }

    public function failed(Throwable $e): void
    {
        Log::channel('parsing')->error('parser.job_failed', [
            'organization_id' => $this->organizationId,
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ]);
    }
}
