<?php
declare(strict_types=1);
namespace Gracerpro\ConvertSportActivity\Strava;

use DateTimeImmutable;
use DateTimeZone;
use Gracerpro\ConvertSportActivity\ConvertException;
use Gracerpro\ConvertSportActivity\Gpx;
use Gracerpro\ConvertSportActivity\GpsPoint;
use Throwable;
use ZipArchive;

/**
 * @private
 */
readonly class ActivityResult
{
    public function __construct(
        public Activity $activity,
        public string $fileName,
    ) {
    }
}

class StravaArchive
{
    private Gpx $gpx;

    private DateTimeZone $dateTimeZone;

    private const ACTIVITIES_FILE_NAME = 'activities.csv';

    public function __construct()
    {
        $this->gpx = new Gpx();
        $this->dateTimeZone = new DateTimeZone('UTC');
    }

    public function readActivitiesCount(string $zipFilePath): int
    {
        $zip = $this->openZipArchive($zipFilePath);
        $count = 0;

        try {
            $file = $this->getActivitiesStream($zip);

            $this->getCsvLine($file); // skip header

            while (($row = $this->getCsvLine($file)) !== false) {
                if ($this->isBlankLine($row)) {
                    continue;
                }
                $count++;
            }

            fclose($file);
        } finally {
            $zip->close();
        }

        return $count;
    }

    public function convert(
        string $zipFilePath,
        StravaObserver $observer,
        $activitiesLimit = 0
    ) {
        $zip = $this->openZipArchive($zipFilePath);

        try {
            $this->convertArchive($zip, $observer, $activitiesLimit);
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

    private function convertArchive(ZipArchive $zip, StravaObserver $observer, int $activitiesLimit)
    {
        $header = $this->readHeader($zip);
        $this->readActivities($zip, $header, $observer, $activitiesLimit);
    }

    private function readActivities(
        ZipArchive $zip,
        ActivitiesHeader $header,
        StravaObserver $observer,
        int $activitiesLimit,
    ) {
        $file = $this->getActivitiesStream($zip);
        fgets($file); // skip header
        $count = 0;

        try {
            while (true) {
                $row = $this->getCsvLine($file);
                if ($row === false) {
                    break;
                }
                if ($this->isBlankLine($row)) {
                    continue;
                }
                $activityResult = $this->getActivity($row, $header);
                $points = [];

                if ($activityResult->fileName) {
                    $points = $this->getPoints($activityResult->fileName, $zip);
                }

                $observer->onNewActivity($activityResult->activity, $points, $count);
                ++$count;

                if ($activitiesLimit > 0 && $count == $activitiesLimit) {
                    break;
                }
            }
        } finally {
            fclose($file);
        }
    }

    /**
     * @return GpsPoint[]
     */
    private function getPoints(string $fileName, ZipArchive $zip): array
    {
        $xml = $zip->getFromName($fileName);

        if (!$xml) {
            throw new ConvertException('Could not find "' . $fileName . '" in zip archive.');
        }

        $stream = null;
        try {
            $stream = tmpfile();
            fwrite($stream, $xml);

            $uri = stream_get_meta_data($stream)['uri'];
            $points = $this->gpx->readPoints($uri);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return $points;
    }

    private function getActivity(array $data, ActivitiesHeader $header): ActivityResult
    {
        // For example, "Sep 2, 2019, 4:02:32 PM"
        $startAtStr = $data[$header->getIndex(ActivitiesHeader::NAME_DATE)] ?? '';
        if (!$startAtStr) {
            throw new ConvertException('Created date can not be empty.');
        }
        try {
            $startAt = new DateTimeImmutable($startAtStr, $this->dateTimeZone);
        } catch (Throwable) {
            throw new ConvertException('Could not parse date "' . $startAtStr . '".');
        }

        $sourceActivityType = $data[$header->getIndex(ActivitiesHeader::NAME_TYPE)];
        try {
            $type = ActivityType::fromServiceName($sourceActivityType);
        } catch (Throwable) {
            throw new ConvertException('Unknown activity type "' . $sourceActivityType . '".');
        }

        $elapsedTimeSeconds = isset($data[$header->getIndex(ActivitiesHeader::NAME_ELAPSED_TIME)])
            ? (int)$data[$header->getIndex(ActivitiesHeader::NAME_ELAPSED_TIME)]
            : 0;
        $movingSeconds = isset($data[$header->getIndex(ActivitiesHeader::NAME_MOVING_TIME)])
            ? (int)$data[$header->getIndex(ActivitiesHeader::NAME_MOVING_TIME)]
            : null;
        $maxSpeed = isset($data[$header->getIndex(ActivitiesHeader::NAME_MAX_SPEED)])
            ? (float)$data[$header->getIndex(ActivitiesHeader::NAME_MAX_SPEED)]
            : null;
        $avgSpeed = isset($data[$header->getIndex(ActivitiesHeader::NAME_AVG_SPEED)])
            ? (float)$data[$header->getIndex(ActivitiesHeader::NAME_AVG_SPEED)]
            : null;

        $activity = new Activity(
            id: $data[$header->getIndex(ActivitiesHeader::NAME_ID)] ?? '',
            name: $data[$header->getIndex(ActivitiesHeader::NAME_NAME)] ?? '',
            startAt: $startAt,
            type: $type,
            distanceInMeter: (float)$data[$header->getIndex(ActivitiesHeader::NAME_DISTANCE)] * 1000,
            elapsedTimeSeconds: $elapsedTimeSeconds,
            movingTimeSeconds: $movingSeconds,
            maxSpeedMeterPerSeconds: $maxSpeed,
            averageSpeedMeterPerSeconds: $avgSpeed,
        );

        return new ActivityResult(
            $activity,
            $data[$header->getIndex(ActivitiesHeader::NAME_FILE_NAME)] ?? '',
        );
    }

    private function readHeader(ZipArchive $zip)
    {
        $file = $this->getActivitiesStream($zip);
        $row = $this->getCsvLine($file);

        try {
            if ($row === false) {
                throw new ConvertException('Read csv false, activities header is null.');
            }
            $header = new ActivitiesHeader($row);
        } finally {
            fclose($file);
        }

        return $header;
    }

    private function isBlankLine(array $row): bool
    {
        return count($row) === 1 && $row[0] === null;
    }

    /**
     * @param resource $file
     */
    private function getCsvLine($file): array|false
    {
        return fgetcsv($file, null, ',', '"', '\\');
    }

    /**
     * @return resource
     */
    private function getActivitiesStream(ZipArchive $zip)
    {
        $file = $zip->getStream(self::ACTIVITIES_FILE_NAME);

        if (!$file) {
            throw new ConvertException('Could not find "' . self::ACTIVITIES_FILE_NAME . '" in zip archive.');
        }

        return $file;
    }
}
