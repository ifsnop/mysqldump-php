<?php

namespace Druidfi\Mysqldump\TypeAdapter;

use PDO;

abstract class AbstractTypeAdapter
{
    protected ?PDO $db = null;
    protected array $dumpSettings = [];

    public function __construct(?PDO $dbHandler = null, array $dumpSettings = [])
    {
        $this->db = $dbHandler;
        $this->dumpSettings = $dumpSettings;
        $this->init();
    }

    protected function init()
    {
        // If adapter has some specific init things, implement it in there.
    }
}
