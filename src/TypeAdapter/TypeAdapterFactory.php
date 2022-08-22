<?php

namespace Druidfi\Mysqldump\TypeAdapter;

use Exception;
use PDO;

abstract class TypeAdapterFactory
{
    /**
     * @throws Exception
     */
    public static function create(string $c, ?PDO $conn = null, array $dumpSettings = []): TypeAdapterInterface
    {
        $c = ucfirst(strtolower($c));

        if (!TypeAdapter::isValid($c)) {
            throw new Exception("Database type support for ($c) not yet available");
        }

        $adapterClass = __NAMESPACE__."\\"."TypeAdapter".$c;

        return new $adapterClass($conn, $dumpSettings);
    }
}
