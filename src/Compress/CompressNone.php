<?php

namespace Druidfi\Mysqldump\Compress;

use Exception;

class CompressNone implements CompressInterface
{
    private $fileHandler;

    public function open(string $filename)
    {
        $this->fileHandler = fopen($filename, "wb");

        if (false === $this->fileHandler) {
            throw new Exception("Output file is not writable");
        }

        return true;
    }

    public function write(string $str): int
    {
        $bytesWritten = fwrite($this->fileHandler, $str);

        if (false === $bytesWritten) {
            throw new Exception("Writing to file failed! Probably, there is no more free space left?");
        }

        return $bytesWritten;
    }

    public function close(): bool
    {
        return fclose($this->fileHandler);
    }
}
