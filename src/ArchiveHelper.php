<?php
declare(strict_types=1);
namespace Gracerpro\ConvertSportActivity;

use Gracerpro\ConvertSportActivity\Exceptions\ConvertException;
use ZipArchive;

class ArchiveHelper
{
    public static function getPrefix(ZipArchive $zip): string|null
    {
        if ($zip->numFiles <= 0) {
            return null;
        }

        $fileName = $zip->getNameIndex(0);

        if ($fileName === false) {
            throw new ConvertException("Entry name is null, but it's impossible.");
        }

        $index = mb_stripos($fileName, '/');

        if ($index === false) {
            return null;
        }

        return mb_substr($fileName, 0, $index + 1);
    }
}
