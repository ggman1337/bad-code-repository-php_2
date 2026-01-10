<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\ValueObject\Coordinates;
use App\Support\Exceptions\ValidationException;
use DateTimeImmutable;

class RouteService
{
    public function __construct(private DistanceCalculatorService $distanceCalculator)
    {
    }

    public function calculate(array $payload): array
    {
        $points = $payload['points'] ?? [];
        if (!is_array($points) || count($points) < 2) {
            throw new ValidationException(['points' => 'Маршрут должен содержать минимум две точки']);
        }

        $distance = 0.0;
        for ($i = 0; $i < count($points) - 1; $i++) {
            $distance += $this->distanceCalculator->calculateDistance(
                Coordinates::fromArray($points[$i]),
                Coordinates::fromArray($points[$i + 1])
            );
        }

        $averageSpeed = 30.0;
        $durationMinutes = (int) round(($distance / $averageSpeed) * 60);
        $durationMinutes = max($durationMinutes, 5);

        $bufferMinutes = (int) round($durationMinutes * 0.3);
        $totalMinutes = $durationMinutes + $bufferMinutes;

        $start = (new DateTimeImmutable('09:00'))->format('H:i');
        $endTime = (new DateTimeImmutable('09:00'))->modify("+{$totalMinutes} minutes")->format('H:i');

        return [
            'distance_km' => round($distance, 2),
            'duration_minutes' => $totalMinutes,
            'suggested_time' => [
                'start' => $start,
                'end' => $endTime,
            ],
        ];
    }

}
