<?php

namespace Druidfi\Mysqldump\TypeAdapter;

use PDO;

abstract class AbstractTypeAdapter
{
    protected ?PDO $dbHandler = null;
    protected array $dumpSettings = [];

    public function __construct(?PDO $dbHandler = null, array $dumpSettings = [])
    {
        $this->dbHandler = $dbHandler;
        $this->dumpSettings = $dumpSettings;
    }
}
