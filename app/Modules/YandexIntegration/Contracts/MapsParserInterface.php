<?php

namespace App\Modules\YandexIntegration\Contracts;

use App\Modules\YandexIntegration\Dto\OrganizationData;

interface MapsParserInterface
{
    public function parse(string $url): OrganizationData;
}
