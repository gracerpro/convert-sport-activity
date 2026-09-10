<?php
declare(strict_types=1);
namespace Gracerpro\ConvertSportActivity\Adidas;

use DateTimeImmutable;
use Exception;
use Gracerpro\ConvertSportActivity\ArchiveHelper;
use Gracerpro\ConvertSportActivity\Exceptions\ConvertException;
use Throwable;
use Gracerpro\ConvertSportActivity\GpsPoint;
use Gracerpro\ConvertSportActivity\Gpx;
use ZipArchive;

class AdidasArchive
{
    private Gpx $gpx;

    private string|null $namePrefix = null;

    private const SESSIONS_NAME = 'Sport-sessions/';

    public function __construct()
    {
        $this->gpx = new Gpx();
    }

    public function readActivitiesCount(string $zipFilePath): int
    {
        $zip = $this->openZipArchive($zipFilePath);

        try {
            $count = count($this->readNames($zip));
        } finally {
            $zip->close();
        }

        return $count;
    }

    /**
     * @param int $activitiesLimit Limit of an activities, 0 is unlimit.
     */
    public function convert(
        string $archivePath,
        AdidasObserver $observer,
        int $activitiesLimit = 0
    ): void {
        $zip = $this->openZipArchive($archivePath);

        try {
            $this->readActivities($zip, $observer, $activitiesLimit);
        } finally {
            $zip->close();
        }
    }

    private function openZipArchive(string $filePath): ZipArchive
    {
        $zip = new ZipArchive();
        $openResult = $zip->open($filePath, ZipArchive::RDONLY);

        if ($openResult !== true) {
            $message = 'Could not open zip archive.';
            if (is_integer($openResult)) {
                $message .= ' Return code is "' . $openResult . '".';
            }
            throw new ConvertException($message);
        }

        return $zip;
    }

    private function getNamePrefix(ZipArchive $zip): string
    {
        if ($this->namePrefix !== null) {
            return $this->namePrefix;
        }

        $this->namePrefix = ArchiveHelper::getPrefix($zip);

        if ($this->namePrefix === null) {
            $this->namePrefix = '';
        }

        return $this->namePrefix;
    }

    /**
     * @return string[]
     */
    private function readNames(ZipArchive $zip): array
    {
        $names = [];
        $startName = self::SESSIONS_NAME;

        if ($zip->numFiles > 0) {
            $index = $zip->locateName($startName);

            if ($index === false) {
                $prefix = $this->getNamePrefix($zip);
                $startName2 = $prefix . $startName;

                $index = $zip->locateName($startName2);

                if ($index !== false) {
                    $startName = $startName2;
                }
            }
        }
        $startNameSize = strlen($startName);

        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $name = $zip->getNameIndex($i);

            if ($name === false) {
                throw new ConvertException("Name is null, but it's impossible.");
            }

            // 1. Sport-sessions/2023-07-10_13-05-32-UTC_935b13d7-5e1e-44e1-9238-73c65dbe28c1.json
            // 2. Sport-sessions/GPS-data/2023-07-10_13-05-32-UTC_935b13d7-5e1e-44e1-9238-73c65dbe28c1.json
            // 3. Sport-sessions/GPS-data/2023-07-10_13-05-32-UTC_935b13d7-5e1e-44e1-9238-73c65dbe28c1.gpx
            // 4. Sport-sessions/Elevation-data/2023-07-10_13-05-32-UTC_935b13d7-5e1e-44e1-9238-73c65dbe28c1.json

            if ($name === $startName) {
                continue;
            }

            if (str_starts_with($name, $startName)) {
                $slashIndex = strrpos($name, '/');

                if ($startNameSize === $slashIndex + 1) { // 1.
                    $fileName = substr($name, $slashIndex + 1);
                    $dotIndex = strrpos($fileName, '.');

                    if ($dotIndex === false) {
                        throw new ConvertException('Unknown entry in zip archive, a point is expected in the name.');
                    }

                    $name = substr($fileName, 0, $dotIndex);
                    $names[$name] = true;
                }
            }
        }

        return array_keys($names);
    }

    private function readActivities(
        ZipArchive $zip,
        AdidasObserver $observer,
        int $activitiesLimit,
    ): void {
        $count = 0;
        $names = $this->readNames($zip);

        if (count($names) === 0) {
            return;
        }

        $prefix = '';
        $first = $zip->locateName(self::SESSIONS_NAME . $names[0] .'.json');

        if ($first === false) {
            $prefix = $this->getNamePrefix($zip);
        }

        // 1. Sport-sessions/{name}.json
        // 2. Sport-sessions/GPS-data/{name}.json
        // 3. Sport-sessions/GPS-data/{name}.gpx
        // 4. Sport-sessions/Elevation-data/{name}.json

        foreach ($names as $name) {
            $activity = $this->readActivity($zip, $prefix . self::SESSIONS_NAME . $name .'.json');

            /** @var GpsPoint[] $points */
            $points = [];
            $jsonIndex = $zip->locateName($prefix . self::SESSIONS_NAME . 'GPS-data/' . $name . '.json');

            if ($jsonIndex !== false) {
                $points = $this->readJsonPoints($zip, $jsonIndex);
            } else {
                $gpsIndex = $zip->locateName($prefix . self::SESSIONS_NAME . 'GPS-data/' . $name . '.gpx');

                if ($gpsIndex !== false) {
                    $points = $this->readGpxPoints($zip, $gpsIndex);
                }
            }

            $observer->onNewActivity($activity, $points, $count);
            ++$count;

            if ($activitiesLimit > 0 && $count == $activitiesLimit) {
                break;
            }
        }
    }

    private function readActivity(ZipArchive $zip, string $zipName): Activity
    {
        $json = $zip->getFromName($zipName);

        if ($json === false) {
            throw new ConvertException('Could not get a content by zip name "' . $zipName . '".');
        }

        /**
         * @var array{
         *   id: string,
         *   start_time: int,
         *   end_time: int,
         *   features?: array{
         *     type: 'track_metrics'|'map'|'heart_rate'|'initial_values'|'origin',
         *     attributes: array{
         *       distance?: int,
         *       average_speed?: string|float,
         *       average_pace: float,
         *       max_speed?: string|float,
         *     }
         *   }[],
         *   sport_type_id: string|int,
         *   duration: int,
         *   start_time_timezone_offset: int,
         *   end_time_timezone_offset: int,
         * } $data
         */
        $data = json_decode($json, true);
        $startTime = (int)($data['start_time'] / 1000);
        $endTime = (int)($data['end_time'] / 1000);
        $avgSpeed = null;
        $maxSpeed = null;
        $distanceInMeter = 0;

        if (isset($data['features'])) {
            foreach ($data['features'] as $feature) {
                if ($feature['type'] === 'track_metrics') {
                    $distanceInMeter = (int)($feature['attributes']['distance'] ?? 0);
                    $avgSpeed = isset($feature['attributes']['average_speed'])
                        ? (float)$feature['attributes']['average_speed']
                        : null;
                    $maxSpeed = isset($feature['attributes']['max_speed'])
                        ? (float)$feature['attributes']['max_speed']
                        : null;
                }
            }
        }

        $sourceActivityType = (string)$data['sport_type_id'];
        try {
            $activityType = ActivityType::from($sourceActivityType);
        } catch (Throwable) {
            throw new ConvertException('Unknown activity type "' . $sourceActivityType . '".');
        }

        return new Activity(
            id: (string)$data['id'],
            type: $activityType,
            startAt: (new DateTimeImmutable())->setTimestamp($startTime),
            startTimeZoneOffset: (int)($data['start_time_timezone_offset'] / 1000),
            distanceInMeter: $distanceInMeter,
            elapsedTimeSeconds: $endTime - $startTime,
            movingTimeSeconds: (int)($data['duration'] / 1000),
            averageSpeedMeterPerSeconds: $avgSpeed,
            maxSpeedMeterPerSeconds: $maxSpeed,
        );
    }

    /**
     * @return GpsPoint[]
     */
    private function readJsonPoints(ZipArchive $zip, int $index): array
    {
        $json = $zip->getFromIndex($index);

        if ($json === false) {
            throw new ConvertException('Could not get a GPS content.');
        }

        /**
         * @var array{
         *   latitude: float,
         *   longitude: float,
         *   altitude: float,
         *   timestamp: int,
         *   speed: float,
         * }[]
         */
        $data = json_decode($json, true);

        $points = [];
        foreach ($data as $point) {
            $points[] = new GpsPoint(
                latitude: (float)$point['latitude'],
                longitude: (float)$point['longitude'],
                time: (new DateTimeImmutable())->setTimestamp(intdiv($point['timestamp'], 1000)),
                elevation: 0,
                speed: $point['speed'],
            );
        }

        return $points;
    }

    /**
     * @return GpsPoint[]
     */
    private function readGpxPoints(ZipArchive $zip, int $index): array
    {
        $xml = $zip->getFromIndex($index);

        if (!$xml) {
            throw new ConvertException('Could not find file by index "' . $index . '" in zip archive.');
        }

        $stream = null;
        try {
            $stream = tmpfile();
            fwrite($stream, $xml);

            $streamData = stream_get_meta_data($stream);

            if (!isset($streamData['uri'])) {
                throw new ConvertException('Uri field is null on stream data.');
            }

            $points = $this->gpx->readPoints($streamData['uri']);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return $points;
    }
}
