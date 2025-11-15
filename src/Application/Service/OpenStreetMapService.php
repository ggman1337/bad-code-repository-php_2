<?php

declare(strict_types=1);

namespace App\Application\Service;

class OpenStreetMapService
{
    public function calculateDistance(float $startLat, float $startLon, float $endLat, float $endLon): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($endLat - $startLat);
        $dLon = deg2rad($endLon - $startLon);
        $startLat = deg2rad($startLat);
        $endLat = deg2rad($endLat);

        $a = sin($dLat / 2) ** 2 + cos($startLat) * cos($endLat) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 2);
    }
}
