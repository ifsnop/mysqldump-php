<?php

namespace Druidfi\Mysqldump\Compress;

use Exception;

abstract class CompressManagerFactory
{
    // List of available compression methods as constants.
    const GZIP  = 'Gzip';
    const BZIP2 = 'Bzip2';
    const NONE  = 'None';
    const GZIPSTREAM = 'Gzipstream';

    public static array $methods = [
        self::NONE,
        self::GZIP,
        self::BZIP2,
        self::GZIPSTREAM,
    ];

    /**
     * @throws Exception
     */
    public static function create(string $method): CompressInterface
    {
        $method = ucfirst(strtolower($method));

        if (!in_array($method, self::$methods)) {
            throw new Exception("Compression method ($method) is not defined yet");
        }

        $methodClass = __NAMESPACE__."\\"."Compress".$method;

        return new $methodClass;
    }
}
