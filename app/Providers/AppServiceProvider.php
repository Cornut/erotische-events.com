<?php

namespace App\Providers;

use App\Scraping\CurrencyNormalizer;
use App\Scraping\EventImportService;
use App\Scraping\EventScraperService;
use App\Scraping\EventsUrlResolver;
use App\Scraping\Extractors\IcalExtractor;
use App\Scraping\Extractors\LlmEventExtractor;
use App\Scraping\Extractors\StructuredDataExtractor;
use App\Scraping\HttpPageFetcher;
use App\Scraping\Llm\AnthropicLlmClient;
use App\Scraping\Llm\LlmClient;
use App\Scraping\UrlDiscoveryService;
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

        $this->app->bind(LlmClient::class, AnthropicLlmClient::class);

        $this->app->bind(UrlDiscoveryService::class, function ($app) {
            return new UrlDiscoveryService(
                $app->make(HttpPageFetcher::class),
                $app->make(AnthropicLlmClient::class),
                [
                    $app->make(StructuredDataExtractor::class),
                    $app->make(IcalExtractor::class),
                ],
            );
        });

        $this->app->bind(EventScraperService::class, function ($app) {
            return new EventScraperService(
                $app->make(HttpPageFetcher::class),
                [
                    $app->make(StructuredDataExtractor::class),
                    $app->make(IcalExtractor::class),
                ],
                new LlmEventExtractor($app->make(AnthropicLlmClient::class)),
                $app->make(EventImportService::class),
                $app->make(EventsUrlResolver::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
