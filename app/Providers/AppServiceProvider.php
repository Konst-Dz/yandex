<?php

namespace App\Providers;

use App\Modules\Organizations\Contracts\OrganizationServiceInterface;
use App\Modules\Organizations\Services\OrganizationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrganizationServiceInterface::class, OrganizationService::class);
    }

    public function boot(): void
    {
    }
}
