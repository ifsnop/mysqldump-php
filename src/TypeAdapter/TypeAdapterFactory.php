<?php

namespace Druidfi\Mysqldump\TypeAdapter;

use Exception;
use PDO;

/**
 * TypeAdapter Factory.
 */
abstract class TypeAdapterFactory
{
    /**
     * @param string $c Type of database factory to create (Mysql, Sqlite,...)
     * @param ?PDO $dbHandler
     * @param array $dumpSettings
     * @return mixed
     * @throws Exception
     */
    public static function create(string $c, ?PDO $dbHandler = null, array $dumpSettings = []): TypeAdapterInterface
    {
        $c = ucfirst(strtolower($c));

        if (!TypeAdapter::isValid($c)) {
            throw new Exception("Database type support for ($c) not yet available");
        }

        $adapterClass = __NAMESPACE__."\\"."TypeAdapter".$c;

        return new $adapterClass($dbHandler, $dumpSettings);
    }
}
