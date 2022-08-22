<?php

namespace Druidfi\Mysqldump\Compress;

use Exception;

class CompressGzipstream implements CompressInterface
{
    private $fileHandler;
    private $compressContext;

    /**
     * @throws Exception
     */
    public function open(string $filename): bool
    {
        $this->fileHandler = fopen($filename, 'wb');

        if (false === $this->fileHandler) {
            throw new Exception('Output file is not writable');
        }

        $this->compressContext = deflate_init(ZLIB_ENCODING_GZIP, ['level' => 9]);

        return true;
    }

    /**
     * @throws Exception
     */
    public function write(string $str): int
    {
        $bytesWritten = fwrite($this->fileHandler, deflate_add($this->compressContext, $str, ZLIB_NO_FLUSH));

        if (false === $bytesWritten) {
            throw new Exception('Writing to file failed! Probably, there is no more free space left?');
        }

        return $bytesWritten;
    }

    public function close(): bool
    {
        fwrite($this->fileHandler, deflate_add($this->compressContext, '', ZLIB_FINISH));

        return fclose($this->fileHandler);
    }
}
