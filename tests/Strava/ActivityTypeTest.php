<?php
declare(strict_types=1);
namespace Gracerpro\ConvertSportActivity\Tests\Strava;

use Gracerpro\ConvertSportActivity\Strava\ActivityType;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversClass(ActivityType::class)]
#[CoversMethod(ActivityType::class, 'fromServiceName')]
final class ActivityTypeTest extends TestCase
{
    public function testSuccessServiceName(): void
    {
        $type = ActivityType::fromServiceName('E-Bike Ride');

        $this->assertIsObject($type);
    }

    public function testSuccessValue(): void
    {
        $type = ActivityType::from('Ride');

        $this->assertIsObject($type);
    }

    public function testFail(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ActivityType::fromServiceName('AaaBbb123');
    }
}
