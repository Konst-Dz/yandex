<?php

namespace App\Modules\YandexIntegration\Dto;

readonly class OrganizationData
{
    public function __construct(
        public RatingSummary $summary,
        public array $reviews,
    ) {
    }
}
