<?php

namespace App\Providers;

use App\Modules\Organizations\Contracts\OrganizationServiceInterface;
use App\Modules\Organizations\Events\OrganizationLinkSaved;
use App\Modules\Organizations\Services\OrganizationService;
use App\Modules\YandexIntegration\Contracts\MapsParserInterface;
use App\Modules\YandexIntegration\Listeners\OnOrganizationLinkSaved;
use App\Modules\YandexIntegration\Services\YandexMapsParser;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrganizationServiceInterface::class, OrganizationService::class);
        $this->app->bind(MapsParserInterface::class, YandexMapsParser::class);
    }

    public function boot(): void
    {
        Event::listen(OrganizationLinkSaved::class, OnOrganizationLinkSaved::class);
    }
}
