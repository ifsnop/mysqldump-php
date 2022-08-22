<?php

namespace Druidfi\Mysqldump\Compress;

use Druidfi\Mysqldump\Mysqldump;
use Exception;

abstract class CompressManagerFactory
{
    public static array $methods = [
        Mysqldump::NONE,
        Mysqldump::GZIP,
        Mysqldump::BZIP2,
        Mysqldump::GZIPSTREAM,
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
