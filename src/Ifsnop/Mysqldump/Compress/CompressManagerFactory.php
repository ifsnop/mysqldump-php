<?php

namespace Ifsnop\Mysqldump\Compress;

use Exception;

abstract class CompressManagerFactory
{
    public static function create(string $c): CompressInterface
    {
        $c = ucfirst(strtolower($c));

        if (!CompressMethod::isValid($c)) {
            throw new Exception("Compression method ($c) is not defined yet");
        }

        $methodClass = __NAMESPACE__."\\"."Compress".$c;

        return new $methodClass;
    }
}
