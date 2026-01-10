<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\ValueObject\Coordinates;

class DistanceCalculatorService
{
    public function calculateDistance(Coordinates $start, Coordinates $end): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($end->latitude() - $start->latitude());
        $dLon = deg2rad($end->longitude() - $start->longitude());
        $startLat = deg2rad($start->latitude());
        $endLat = deg2rad($end->latitude());

        $a = sin($dLat / 2) ** 2 + cos($startLat) * cos($endLat) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 2);
    }
}
