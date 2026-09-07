<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SlippyMapMath;
use Tests\TestCase;

class SlippyMapMathTest extends TestCase
{
    public function test_it_computes_the_correct_tile_for_a_known_point(): void
    {
        // LPT2-GCP-015: 4.27412582, 103.43658211 at zoom 15.
        // Reference values computed independently in Python from the
        // standard OSM slippy-map formula
        // (https://wiki.openstreetmap.org/wiki/Slippy_map_tilenames),
        // not derived from this class, so this is a real cross-check.
        $tile = SlippyMapMath::tileForPoint(4.27412582, 103.43658211, 15);

        $this->assertSame(25799, $tile['x']);
        $this->assertSame(15994, $tile['y']);
        $this->assertEqualsWithDelta(0.0276, $tile['fractionX'], 0.001);
        $this->assertEqualsWithDelta(0.5982, $tile['fractionY'], 0.001);
    }

    public function test_the_equator_and_prime_meridian_fall_in_the_centre_tiles_at_zoom_1(): void
    {
        $tile = SlippyMapMath::tileForPoint(0.0, 0.0, 1);

        $this->assertSame(1, $tile['x']);
        $this->assertSame(1, $tile['y']);
    }
}
