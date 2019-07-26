<?php

namespace Async\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;

/**
 * Class UploadedFileFactory
 *
 * @package Async\Http
 */
class UploadedFileFactory implements UploadedFileFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createUploadedFile(StreamInterface $file, ?int $size = null, int $error = UPLOAD_ERR_OK, ?string $clientFilename = null, ?string $clientMediaType = null): UploadedFileInterface
    {
        return new UploadedFile($file, (int)$size, (int)$error, $clientFilename, $clientMediaType);
    }
}
