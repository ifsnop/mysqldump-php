<?php

namespace Druidfi\Mysqldump\Compress;

use Exception;

class CompressBzip2 implements CompressInterface
{
    private $fileHandler;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        if (!function_exists('bzopen')) {
            throw new Exception('Compression is enabled, but bzip2 lib is not installed or configured properly');
        }
    }

    /**
     * @throws Exception
     */
    public function open(string $filename): bool
    {
        $this->fileHandler = bzopen($filename, 'w');

        if (false === $this->fileHandler) {
            throw new Exception('Output file is not writable');
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function write(string $str): int
    {
        $bytesWritten = bzwrite($this->fileHandler, $str);

        if (false === $bytesWritten) {
            throw new Exception('Writing to file failed! Probably, there is no more free space left?');
        }

        return $bytesWritten;
    }

    public function close(): bool
    {
        return bzclose($this->fileHandler);
    }
}
