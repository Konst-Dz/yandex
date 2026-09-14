<?php

namespace App\Modules\YandexIntegration\Listeners;

use App\Modules\Organizations\Events\OrganizationLinkSaved;
use App\Modules\YandexIntegration\Jobs\ParseOrganizationJob;

class OnOrganizationLinkSaved
{
    public function handle(OrganizationLinkSaved $event): void
    {
        ParseOrganizationJob::dispatch($event->organizationId, $event->url);
    }
}
