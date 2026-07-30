<?php

namespace App\Services;

class GoogleMapsUrlParser
{
    /**
     * Add the scheme commonly omitted when a Google Maps address is copied
     * from the browser's address bar.
     */
    public function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (strpos($value, '//') === 0) {
            return 'https:'.$value;
        }

        if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $value)
            && preg_match('~^(?:www\.)?(?:google\.[a-z.]+|maps\.google\.[a-z.]+|maps\.app\.goo\.gl|goo\.gl)(?:/|$)~i', $value)) {
            return 'https://'.$value;
        }

        return $value;
    }

    /**
     * Return [latitude, longitude] from a Google Maps URL.
     *
     * Place coordinates (!3d...!4d...) must be checked before @ coordinates:
     * the latter only describe the map viewport and can point kilometres away.
     */
    public function coordinates(?string $value): ?array
    {
        $decoded = rawurldecode((string) $value);
        $number = '-?\d{1,3}(?:\.\d+)?';
        $patterns = [
            '~!3d('.$number.')!4d('.$number.')~i',
            '~[?&](?:q|query|ll)=('.$number.'),\s*('.$number.')~i',
            '~/place/('.$number.'),\s*('.$number.')(?:[/]|$)~i',
            '~@('.$number.'),\s*('.$number.')(?:,|/|$)~i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $decoded, $matches, PREG_SET_ORDER)) {
                $match = end($matches);
                $latitude = (float) $match[1];
                $longitude = (float) $match[2];

                if ($latitude >= -90 && $latitude <= 90
                    && $longitude >= -180 && $longitude <= 180) {
                    return [$latitude, $longitude];
                }
            }
        }

        return null;
    }
}
