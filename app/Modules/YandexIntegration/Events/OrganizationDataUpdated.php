<?php

namespace App\Modules\YandexIntegration\Events;

use Illuminate\Foundation\Events\Dispatchable;

readonly class OrganizationDataUpdated
{
    use Dispatchable;

    public function __construct(public int $organizationId)
    {
    }
}
