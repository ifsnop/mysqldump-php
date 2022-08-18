<?php

namespace Ifsnop\Mysqldump\Compress;

interface CompressInterface
{
    public function open(string $filename);

    public function write(string $str): int;

    public function close(): bool;
}
