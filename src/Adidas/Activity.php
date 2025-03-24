<?php
declare(strict_types=1);
namespace Gracerpro\ConvertSportActivity\Adidas;

use DateTimeImmutable;

readonly class Activity
{
    public function __construct(
        public string $id,
        public DateTimeImmutable $startAt,
        public ActivityType $type,
        public float $distanceInMeter,
        public int $elapsedTimeSeconds,
        public ?int $movingTimeSeconds = null,
        public ?float $maxSpeedMeterPerSeconds = null,
        public ?float $averageSpeedMeterPerSeconds = null,
        public int $startTimeZoneOffset = 0,
    ) {
    }
}
