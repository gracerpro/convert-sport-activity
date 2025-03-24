<?php
declare(strict_types=1);
namespace Gracerpro\ConvertSportActivity\Strava;

use Gracerpro\ConvertSportActivity\GpsPoint;

interface StravaObserver
{
    /**
     * @param GpsPoint[] $points
     */
    public function onNewActivity(Activity $activity, array $points, int $index);
}
