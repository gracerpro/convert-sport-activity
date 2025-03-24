<?php
declare(strict_types=1);
namespace Gracerpro\ConvertSportActivity\Adidas;

use Gracerpro\ConvertSportActivity\GpsPoint;

interface AdidasObserver
{
    /**
     * @param GpsPoint[] $points
     */
    public function onNewActivity(Activity $activity, array $points, int $index);
}
