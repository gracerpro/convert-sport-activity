<?php
declare(strict_types=1);
namespace Gracerpro\ConvertSportActivity\Tests;

use Gracerpro\ConvertSportActivity\Gpx;
use Gracerpro\ConvertSportActivity\GpxException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;

#[CoversClass(Gpx::class)]
#[CoversMethod(Gpx::class, 'readPoints')]
final class GpxTest extends TestCase
{
    public function testReadPoints(): void
    {
        $gpx = new Gpx();
        $filePath = __DIR__ . '/data/activity.gpx';

        $points = $gpx->readPoints($filePath);

        $this->assertCount(50, $points);
    }

    public function testNotFoundGpx(): void
    {
        $gpx = new Gpx();
        $filePath = __DIR__ . '/data/file-not-found.gpx';

        $this->expectException(GpxException::class);

        $gpx->readPoints($filePath);
    }

    public function testFailGpx(): void
    {
        $gpx = new Gpx();
        $filePath = __DIR__ . '/data/fail.gpx';

        $this->expectException(GpxException::class);

        $gpx->readPoints($filePath);
    }
}
