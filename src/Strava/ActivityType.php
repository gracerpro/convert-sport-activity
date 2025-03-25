<?php
declare(strict_types=1);
namespace Gracerpro\ConvertSportActivity\Strava;

use InvalidArgumentException;

/**
 * @see https://developers.strava.com/docs/reference/#api-models-ActivityType
 */
enum ActivityType: string
{
    case Ride = 'Ride';
    case ElectricBikeRide = 'E-Bike Ride'; // old value
    case Walk = 'Walk';
    case Run = 'Run';
    case Handcycle = 'Handcycle';
    case AlpineSki = 'AlpineSki';
    case BackcountrySki = 'BackcountrySki';
    case Canoeing = 'Canoeing';
    case Crossfit = 'Crossfit';
    case EBikeRide = 'EBikeRide';
    case Elliptical = 'Elliptical';
    case Golf = 'Golf';
    case Hike = 'Hike';
    case IceSkate = 'IceSkate';
    case InlineSkate = 'InlineSkate';
    case Kayaking = 'Kayaking';
    case Kitesurf = 'Kitesurf';
    case NordicSki = 'NordicSki';
    case RockClimbing = 'RockClimbing';
    case RollerSki = 'RollerSki';
    case Rowing = 'Rowing';
    case Sail = 'Sail';
    case Skateboard = 'Skateboard';
    case Snowboard = 'Snowboard';
    case Snowshoe = 'Snowshoe';
    case Soccer = 'Soccer';
    case StairStepper = 'StairStepper';
    case StandUpPaddling = 'StandUpPaddling';
    case Surfing = 'Surfing';
    case Swim = 'Swim';
    case Velomobile = 'Velomobile';
    case VirtualRide = 'VirtualRide';
    case VirtualRun = 'VirtualRun';
    case WeightTraining = 'WeightTraining';
    case Wheelchair = 'Wheelchair';
    case Windsurf = 'Windsurf';
    case Workout = 'Workout';
    case Yoga = 'Yoga';

    /**
     * @throws InvalidArgumentException
     */
    public static function fromServiceName(string $name): ActivityType
    {
        static $map = null;

        if ($map === null) {
            foreach (self::cases() as $case) {
                $map[strtolower($case->value)] = $case->name;
            }
        }

        $lowerName = strtolower($name);
        if (isset($map[$lowerName])) {
            return constant(self::class . '::' . $map[$lowerName]);
        }

        throw new InvalidArgumentException('"' . $name . '" is not a valid backing value for enum ' . self::class);
    }
}
