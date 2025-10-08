<?php
declare(strict_types=1);
namespace Gracerpro\ConvertSportActivity\Adidas;

use DateTimeImmutable;
use Gracerpro\ConvertSportActivity\ConvertException;
use Throwable;
use Gracerpro\ConvertSportActivity\GpsPoint;
use Gracerpro\ConvertSportActivity\Gpx;
use ZipArchive;

class AdidasArchive
{
    private Gpx $gpx;

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
        string $stravaArchivePath,
        AdidasObserver $observer,
        int $activitiesLimit = 0
    ): void {
        $zip = $this->openZipArchive($stravaArchivePath);

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
            $message = 'Could not open Strava zip archive.';
            if (is_integer($openResult)) {
                $message .= ' Return code is "' . $openResult . '".';
            }
            throw new ConvertException($message);
        }

        return $zip;
    }

    /**
     * @return string[]
     */
    private function readNames(ZipArchive $zip): array
    {
        $names = [];
        $startName = 'Sport-sessions/';
        $startNameSize = strlen($startName);

        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $name = $zip->getNameIndex($i);

            if ($name === false) {
                throw new ConvertException('Could not find name for index "' . $i . '".');
            }

            // 1. Sport-sessions/2023-07-10_13-05-32-UTC_935b13d7-5e1e-44e1-9238-73c65dbe28c1.json
            // 2. Sport-sessions/GPS-data/2023-07-10_13-05-32-UTC_935b13d7-5e1e-44e1-9238-73c65dbe28c1.json
            // 3. Sport-sessions/GPS-data/2023-07-10_13-05-32-UTC_935b13d7-5e1e-44e1-9238-73c65dbe28c1.gpx
            // 4. Sport-sessions/Elevation-data/2023-07-10_13-05-32-UTC_935b13d7-5e1e-44e1-9238-73c65dbe28c1.json

            if (str_starts_with($name, $startName)) {
                $slashIndex = strrpos($name, '/');

                if ($startNameSize === $slashIndex + 1) { // 1.
                    $fileName = substr($name, $slashIndex + 1);
                    $dotIndex = strrpos($fileName, '.');
                    $name = substr($fileName, 0, $dotIndex !== false ? $dotIndex : null);
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

        // 1. Sport-sessions/{name}.json
        // 2. Sport-sessions/GPS-data/{name}.json
        // 3. Sport-sessions/GPS-data/{name}.gpx
        // 4. Sport-sessions/Elevation-data/{name}.json

        foreach ($names as $name) {
            $activity = $this->readActivity($zip, 'Sport-sessions/' . $name .'.json');

            $points = [];
            $jsonIndex = $zip->locateName('Sport-sessions/GPS-data/' . $name . '.json');

            if ($jsonIndex !== false) {
                $points = $this->readJsonPoints($zip, $jsonIndex);
            } else {
                $gpsIndex = $zip->locateName('Sport-sessions/GPS-data/' . $name . '.gpx');
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

        $data = json_decode($json, true);

        if ($data === null) {
            throw new ConvertException('Could not decode JSON in "' . $zipName . '".');
        }
        if (!is_array($data)) {
            throw new ConvertException('JSON data must be an object in "' . $zipName . '".');
        }
        /**
         * @var array{
         *   id: string,
         *   start_time: int,
         *   start_time_timezone_offset: int,
         *   duration: int,
         *   end_time: int,
         *   sport_type_id: string,
         *   features?: array{
         *     type: string,
         *     attributes: array<string, string|int|float|bool|null>
         *   }[]
         * } $data
         */
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
            throw new ConvertException('Could not get GPS content.');
        }

        $data = json_decode($json, true);

        if ($data === null) {
            throw new ConvertException('Could not decode GPS content.');
        }
        if (!is_array($data)) {
            throw new ConvertException('GPS points must be an array.');
        }

        /**
         * @var array{
         *   timestamp: int,
         *   latitude: float,
         *   longitude: float,
         *   altitude?: float,
         *   speed?: float,
         *   distance?: int,
         * }[] $data
         */
        $points = [];
        foreach ($data as $point) {
            $points[] = new GpsPoint(
                latitude: (float)$point['latitude'],
                longitude: (float)$point['longitude'],
                time: (new DateTimeImmutable())->setTimestamp(intdiv($point['timestamp'], 1000)),
                elevation: 0,
                speed: $point['speed'] ?? 0,
            );
        }

        return $points;
    }

    /**
     * @throws ConvertException
     *
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

            $metaData = stream_get_meta_data($stream);

            if (!isset($metaData['uri'])) {
                throw new ConvertException('Could not find "uri" field on stream meta data.');
            }

            $uri = $metaData['uri'];
            $points = $this->gpx->readPoints($uri);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return $points;
    }
}
