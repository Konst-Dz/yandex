<?php

namespace App\Modules\YandexIntegration\Dto;

readonly class RatingSummary
{
    public function __construct(
        public float $rating,
        public int $ratingsCount,
        public int $reviewCount,
    ) {
    }
}
