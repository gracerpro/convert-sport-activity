<?php
declare(strict_types=1);
namespace Gracerpro\ConvertSportActivity\Strava;

use Gracerpro\ConvertSportActivity\ConvertException;

class ActivitiesHeader
{
    public const NAME_ID = 'Activity ID';
    public const NAME_DATE = 'Activity Date';
    public const NAME_NAME = 'Activity Name';
    public const NAME_TYPE = 'Activity Type';
    public const NAME_ELAPSED_TIME = 'Elapsed Time'; // int
    public const NAME_DISTANCE = 'Distance'; // float, km
    public const NAME_FILE_NAME = 'Filename';
    public const NAME_MOVING_TIME = 'Moving Time';
    public const NAME_MAX_SPEED = 'Max Speed';
    public const NAME_AVG_SPEED = 'Average Speed';

    /**
     * @var int[]
     */
    private array $indexes = [];

    public function __construct(array $fields)
    {
        foreach ($fields as $i => $name) {
            if ($name === '') {
                continue;
            }
            // skip header like this
            // <span class="translation_missing" title="translation missing: en-US.lib.export.portability_exporter.activities.horton_values.sport_type">Sport Type</span>
            if ($name[0] === '<') {
                continue;
            }
            if (!isset($this->indexes[$name])) {
                $this->indexes[$name] = $i;
            }
        }
    }

    public function getIndex(string $name): int
    {
        if (!isset($this->indexes[$name])) {
            throw new ConvertException('Could not find index for "' . $name . '" header.');
        }

        return $this->indexes[$name];
    }
}
