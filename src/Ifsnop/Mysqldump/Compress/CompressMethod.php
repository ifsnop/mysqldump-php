<?php

namespace Ifsnop\Mysqldump\Compress;

use Ifsnop\Mysqldump\Mysqldump;

/**
 * Enum with all available compression methods.
 */
abstract class CompressMethod
{
    public static array $enums = [
        Mysqldump::NONE,
        Mysqldump::GZIP,
        Mysqldump::BZIP2,
        Mysqldump::GZIPSTREAM,
    ];

    /**
     * @param string $c
     * @return boolean
     */
    public static function isValid(string $c): bool
    {
        return in_array($c, self::$enums);
    }
}
