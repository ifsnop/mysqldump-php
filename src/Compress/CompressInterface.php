<?php

namespace Druidfi\Mysqldump\Compress;

interface CompressInterface
{
    public function open(string $filename): bool;

    public function write(string $str): int;

    public function close(): bool;
}
