<?php
declare(strict_types=1);
namespace Gracerpro\ConvertSportActivity\Tests\Strava;

use Gracerpro\ConvertSportActivity\ConvertException;
use Gracerpro\ConvertSportActivity\Strava\ActivitiesHeader;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversClass(ActivitiesHeader::class)]
#[CoversMethod(ActivitiesHeader::class, 'getIndex')]
final class ActivitiesHeaderTest extends TestCase
{
    public function testValidFields(): void
    {
        $header = new ActivitiesHeader([
            ActivitiesHeader::NAME_AVG_SPEED,
            ActivitiesHeader::NAME_DATE,
            ActivitiesHeader::NAME_DISTANCE,
        ]);

        $this->assertGreaterThanOrEqual(0, $header->getIndex(ActivitiesHeader::NAME_AVG_SPEED));
        $this->assertGreaterThanOrEqual(0, $header->getIndex(ActivitiesHeader::NAME_DISTANCE));
    }

    public function testFailField(): void
    {
        $header = new ActivitiesHeader([
            ActivitiesHeader::NAME_AVG_SPEED,
            ActivitiesHeader::NAME_DATE,
            ActivitiesHeader::NAME_DISTANCE,
        ]);

        $this->expectException(ConvertException::class);

        $header->getIndex(ActivitiesHeader::NAME_ID);
    }

    public function testNotTranslateField(): void
    {
        $name = '<span>message</span>';
        $header = new ActivitiesHeader([
            ActivitiesHeader::NAME_AVG_SPEED,
            ActivitiesHeader::NAME_DATE,
            $name,
        ]);

        $this->expectException(ConvertException::class);

        $header->getIndex($name);
    }

    public function testEmptyField(): void
    {
        $name = '';
        $header = new ActivitiesHeader([
            ActivitiesHeader::NAME_AVG_SPEED,
            ActivitiesHeader::NAME_DATE,
            $name,
        ]);

        $this->expectException(ConvertException::class);

        $header->getIndex($name);
    }
}
