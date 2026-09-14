<?php

namespace App\Modules\YandexIntegration\Dto;

use Carbon\CarbonInterface;

readonly class ReviewDto
{
    public function __construct(
        public string $externalId,
        public string $author,
        public string $text,
        public ?int $rating,
        public ?CarbonInterface $reviewedAt,
    ) {
    }
}
