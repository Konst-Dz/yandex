<?php

namespace App\Modules\Organizations\Events;

use Illuminate\Foundation\Events\Dispatchable;

readonly class OrganizationLinkSaved
{
    use Dispatchable;
    public function __construct(
        public int $organizationId,
        public string $url,
    ) {
    }
}
