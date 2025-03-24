<?php
declare(strict_types=1);
namespace Gracerpro\ConvertSportActivity\Adidas;

/**
 * @see https://github.com/glennreyes/runtastic-gpx/blob/master/index.js
 */
enum ActivityType: string
{
    case Run = '1';
    case NordicWalking = '2';
    case Ride = '3';
    case MountainBiking = '4';
    case Other = '5';
    case Skating = '6';
    case Hiking = '7';
    case CrossCountrySkiing = '8';
    case Skiing = '9';
    case SnowBoarding = '10';
    case Motorbiking = '11';
    case Driving = '12';
    case Snowshoeing = '13';
    case IndoorRun = '14';
    case IndoorRide = '15';
    case Elliptical = '16';
    case Rowing = '17';
    case Swimming = '18';
    case Walk = '19';
    case Riding = '20';
    case Golfing = '21';
    case Race_cycling = '22';
    case Tennis = '23';
    case Badminton = '24';
    case Sailing = '29';
    case Windsurfing = '30';
    case Pilates = '31';
    case Climbing = '32';
    case Frisbee = '33';
    case WeightTraining = '34';
    case Volleyball = '35';
    case Handbike = '36';
    case CrossSkating = '37';
    case Soccer = '38';
    case SmoveyWalking = '39';
    case NordicCrossSkating = '41';
    case Surfing = '42';
    case KiteSurfing = '43';
    case Kayaking = '44';
    case Basketball = '45';
    case Paragliding = '47';
    case WakeBoarding = '48';
    case Freecrossen = '49';
    case Diving = '50';
    case BackCountrySkiing = '53';
    case IceSkating = '54';
    case Sledding = '55';
    case SnowmanBuilding = '56';
    case SnowballFight = '57';
    case Curling = '58';
    case IceStock = '59';
    case Biathlon = '60';
    case KiteSkiing = '61';
    case SpeedSkiing = '62';
    case Baseball = '68';
    case Crossfit = '69';
    case IceHockey = '71';
    case Skateboarding = '72';
    case Rugby = '75';
    case Standup_paddling = '76';
    case ElectricBikeRide = '85';
}
