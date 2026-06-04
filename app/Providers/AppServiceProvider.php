<?php

namespace App\Providers;

use App\Scraping\CurrencyNormalizer;
use App\Tracking\GeoIpResolver;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            GeoIpResolver::class,
            fn () => new GeoIpResolver(config('services.geoip.database')),
        );

        $this->app->bind(CurrencyNormalizer::class, fn () => CurrencyNormalizer::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
