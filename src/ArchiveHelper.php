<?php
declare(strict_types=1);
namespace Gracerpro\ConvertSportActivity;

use ZipArchive;

class ArchiveHelper
{
    public static function getPrefix(ZipArchive $zip): string|null
    {
        // try to add directory name

        if ($zip->numFiles <= 0) {
            return null;
        }

        $fileName = $zip->getNameIndex(0);
        $index = mb_stripos($fileName, '/');

        if ($index === false) {
            return null;
        }

        $directory = mb_substr($fileName, 0, $index);

        return $directory . '/';
    }
}
