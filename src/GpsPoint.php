<?php
declare(strict_types=1);
namespace Gracerpro\ConvertSportActivity;

use DateTimeImmutable;

readonly class GpsPoint
{
    public function __construct(
        public float $latitude,
        public float $longitude,
        public DateTimeImmutable $time,
        public ?float $elevation = null,
        public ?float $speed = null,
    ) {
    }
}
