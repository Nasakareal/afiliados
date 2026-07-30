<?php

namespace Tests\Unit;

use App\Services\GoogleMapsUrlParser;
use PHPUnit\Framework\TestCase;

class GoogleMapsUrlParserTest extends TestCase
{
    public function test_it_prioritizes_place_coordinates_over_viewport_coordinates(): void
    {
        $parser = new GoogleMapsUrlParser();
        $url = 'google.com/maps/place/Monumento/@19.6751315,-101.2289137,13.17z/data=!4m5!8m2!3d19.701789!4d-101.2071705';

        $this->assertSame('https://'.$url, $parser->normalize($url));
        $this->assertSame([19.701789, -101.2071705], $parser->coordinates($url));
    }

    public function test_it_supports_standard_query_and_viewport_links(): void
    {
        $parser = new GoogleMapsUrlParser();

        $this->assertSame(
            [19.7026, -101.1922],
            $parser->coordinates('https://www.google.com/maps?q=19.7026,-101.1922')
        );
        $this->assertSame(
            [19.6751315, -101.2289137],
            $parser->coordinates('https://www.google.com/maps/@19.6751315,-101.2289137,17z')
        );
    }
}
