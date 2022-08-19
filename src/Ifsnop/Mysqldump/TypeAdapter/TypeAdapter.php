<?php

namespace Ifsnop\Mysqldump\TypeAdapter;

/**
 * Enum with all available TypeAdapter implementations.
 */
abstract class TypeAdapter
{
    public static array $enums = [
        "Sqlite",
        "Mysql"
    ];

    public static function isValid(string $c): bool
    {
        return in_array($c, self::$enums);
    }
}
