<?php
declare(strict_types=1);
namespace Gracerpro\ConvertSportActivity;

use DateTimeImmutable;
use XMLReader;

class Gpx
{
    /**
     * @return GpsPoint[]
     */
    public function readPoints(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new GpxException("File not found.");
        }
        $reader = XMLReader::open($filePath);

        if ($reader === false) {
            throw new GpxException("Could not open XML file.");
        }

        try {
            $points = $this->getPoints($reader);
        } finally {
            $reader->close();
        }

        return $points;
    }

    /**
     * @return GpsPoint[]
     */
    private function getPoints(XMLReader $reader): array
    {
        // gpx.trk.trkseg.trkpt lat="56.3363610" lon="41.3054260", array of
        // <ele>117.7</ele>
        // <time>2019-09-06T17:30:23.000Z</time>

        $points = [];
        $prevPoint = null;

        libxml_clear_errors();

        while ($reader->read() !== false) {
            if ($reader->nodeType !== XMLReader::ELEMENT) {
                continue;
            }
            if ($reader->name !== 'trkpt') {
                continue;
            }

            $latitude = (float)$reader->getAttribute('lat');
            $longitude = (float)$reader->getAttribute('lon');
            $elevation = null;
            $timeText = '';

            while ($reader->read() !== false) {
                if ($reader->nodeType === XMLReader::ELEMENT) {
                    if ($reader->name === 'ele') {
                        $elevation = (float)$reader->readString();
                        continue;
                    }
                    if ($reader->name === 'time') {
                        $timeText = $reader->readString();
                        continue;
                    }
                }
                if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'trkpt') {
                    break;
                }
            }
            $this->checkError();

            if ($timeText === '') {
                throw new GpxException('The track point must have a time.');
            }

            $time = new DateTimeImmutable($timeText);
            $speed = null;

            if ($prevPoint) {
                $seconds = $time->getTimestamp() - $prevPoint->time->getTimestamp();
                if ($seconds > 0) {
                    $distance = $this->getDistance(
                        $prevPoint->latitude,
                        $prevPoint->longitude,
                        $latitude,
                        $longitude,
                    );
                    $speed = $distance / $seconds;
                }
            }

            $point = new GpsPoint(
                latitude: $latitude,
                longitude: $longitude,
                time: $time,
                elevation: $elevation,
                speed: $speed,
            );
            $points[] = $point;
            $prevPoint = $point;
        }
        $this->checkError();

        return $points;
    }

    private function checkError()
    {
        $error = libxml_get_last_error();
        if ($error !== false) {
            throw new GpxException($error->message, $error->code);
        }
    }

    /**
     * Calculates the great-circle distance between two points, with
     * the Vincenty formula.
     *
     * @param $latitudeFrom Latitude of start point in [deg decimal]
     * @param float $longitudeFrom Longitude of start point in [deg decimal]
     * @param float $latitudeTo Latitude of target point in [deg decimal]
     * @param float $longitudeTo Longitude of target point in [deg decimal]
     * @return float Distance between points in [m] (same as earthRadius)
     */
    private function getDistance(
        float $latitudeFrom,
        float $longitudeFrom,
        float $latitudeTo,
        float $longitudeTo,
    ) {
        $earthRadius = 6371000; // in m

        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
            pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);

        return $angle * $earthRadius;
    }
}
