<?php
declare(strict_types=1);
namespace Gracerpro\ConvertSportActivity\Strava;

use DateTimeImmutable;

readonly class Activity
{
    public function __construct(
        public string $id,
        public string $name,
        public DateTimeImmutable $startAt,
        public ActivityType $type,
        public float $distanceInMeter,
        public int $elapsedTimeSeconds,
        public ?int $movingTimeSeconds = null,
        public ?float $maxSpeedMeterPerSeconds = null,
        public ?float $averageSpeedMeterPerSeconds = null,
    ) {
    }
}
