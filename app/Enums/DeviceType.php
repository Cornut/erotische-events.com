<?php

namespace App\Enums;

enum DeviceType: string
{
    case Desktop = 'desktop';
    case Mobile = 'mobile';
    case Tablet = 'tablet';
    case Other = 'other';

    public static function fromUserAgent(?string $userAgent): self
    {
        if ($userAgent === null || $userAgent === '') {
            return self::Other;
        }

        $ua = strtolower($userAgent);

        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return self::Tablet;
        }

        if (str_contains($ua, 'mobi') || str_contains($ua, 'iphone') || str_contains($ua, 'android')) {
            return self::Mobile;
        }

        return self::Desktop;
    }
}
