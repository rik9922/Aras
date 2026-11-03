<?php

namespace App\Providers;

use App\Models\AssignAmbassador;
use App\Observers\AssignAmbassadorObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        AssignAmbassador::observe(AssignAmbassadorObserver::class);

        Paginator::defaultView('vendor.pagination.custom');
        Paginator::useBootstrap();
    }
}
