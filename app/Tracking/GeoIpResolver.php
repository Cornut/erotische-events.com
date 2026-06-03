<?php

namespace App\Tracking;

use GeoIp2\Database\Reader;
use Throwable;

class GeoIpResolver
{
    public function __construct(private readonly ?string $databasePath = null) {}

    /**
     * Resolve a 2-letter country code for an IP, or null. The IP is never
     * persisted by callers; only the resulting country is recorded.
     */
    public function countryFor(?string $ip): ?string
    {
        if ($ip === null || $ip === '' || $this->databasePath === null || ! is_file($this->databasePath)) {
            return null;
        }

        try {
            return (new Reader($this->databasePath))->country($ip)->country->isoCode;
        } catch (Throwable) {
            return null;
        }
    }
}
