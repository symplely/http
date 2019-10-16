<?php

declare(strict_types=1);

namespace Async\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Class UploadedFile
 *
 * @package Async\Http
 */
class UploadedFile implements UploadedFileInterface
{
    const WRITE_BUFFER = 8192;

    /**
     * @var string
     */
    protected $clientFilename;

    /**
     * @var string
     */
    protected $clientMediaType;

    /**
     * @var int
     */
    protected $error;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var bool
     */
    protected $moved = false;

    /**
     * @var int
     */
    protected $size;

    /**
     * @var StreamInterface
     */
    protected $stream;

    /**
     * UploadedFile constructor.
     * @param string|resource $file
     * @param int $size
     * @param int $error
     * @param string $clientFilename
     * @param string $clientMediaType
     * @throws \InvalidArgumentException
     */
    public function __construct($file, $size, $error, $clientFilename = null, $clientMediaType = null)
    {
        if (\is_int($error) && (0 <= $error) && (8 >= $error)) {
            $this->error = $error;
        } else {
            throw new \InvalidArgumentException('Error status must be one of UPLOAD_ERR_* constants');
        }

        if (\UPLOAD_ERR_OK === $error) {
            if (\is_string($file)) {
                $this->file = $file;
            } elseif (\is_resource($file)) {
                $this->stream = new Stream($file);
            } elseif ($file instanceof StreamInterface) {
                $this->stream = $file;
            } else {
                throw new \InvalidArgumentException(
                    '$file must be a valid file path, a resource or an instance of Psr\\Http\\Message\\StreamInterface'
                );
            }
        }

        if (is_int($size)) {
            $this->size = $size;
        } else {
            throw new \InvalidArgumentException('Size of UploadedFile must be an integer');
        }

        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientFilename()
    {
        return $this->clientFilename;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }

    /**
     * {@inheritdoc}
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     */
    public function getStream()
    {
        if (!$this->stream) {
            $this->stream = new Stream($this->file, 'r+');
        }
        return $this->stream;
    }

    /**
     * {@inheritdoc}
     */
    public function moveTo($targetPath)
    {
        if ($this->moved) {
            throw new \RuntimeException('This file has already been moved');
        }
        $src = $this->getStream();
        $src->rewind();
        $dest = new Stream($targetPath, 'w');
        while (!$src->eof()) {
            $data = $src->read(self::WRITE_BUFFER);
            if (!$dest->write($data)) {
                break;
            }
        }
        $src->close();
        $dest->close();
        $this->moved = true;
    }


    /**
     * Create a new uploaded file.
     *
     * If a size is not provided it will be determined by checking the size of
     * the file.
     *
     * @see http://php.net/manual/features.file-upload.post-method.php
     * @see http://php.net/manual/features.file-upload.errors.php
     *
     * @param StreamInterface $stream Underlying stream representing the
     *     uploaded file content.
     * @param int $size in bytes
     * @param int $error PHP file upload error
     * @param string $clientFilename Filename as provided by the client, if any.
     * @param string $clientMediaType Media type as provided by the client, if any.
     *
     * @return UploadedFileInterface
     *
     * @throws \InvalidArgumentException If the file resource is not readable.
     */
    public static function create(StreamInterface $file, ?int $size = null, int $error = UPLOAD_ERR_OK, ?string $clientFilename = null, ?string $clientMediaType = null): UploadedFileInterface
    {
        return new self($file, (int)$size, (int)$error, $clientFilename, $clientMediaType);
    }

    /**
     * Create a normalized tree of UploadedFile instances from the Environment.
     *
     * @internal This method is not part of the PSR-7 standard.
     *
     * @param array $globals The global server variables.
     *
     * @return array A normalized tree of UploadedFile instances or null if none are provided.
     */
    public static function fromGlobals(array $globals): array
    {
        if (isset($globals['files']) && \is_array($globals['files'])) {
            return $globals['files'];
        }

        if (!empty($_FILES)) {
            return self::parseUploadedFiles($_FILES);
        }

        return [];
    }

    /**
     * Parse a non-normalized, i.e. $_FILES superglobal, tree of uploaded file data.
     *
     * @internal This method is not part of the PSR-7 standard.
     *
     * @param array $uploadedFiles The non-normalized tree of uploaded file data.
     *
     * @return array A normalized tree of UploadedFile instances.
     */
    private static function parseUploadedFiles(array $uploadedFiles): array
    {
        $parsed = [];
        foreach ($uploadedFiles as $field => $uploadedFile) {
            if (!isset($uploadedFile['error'])) {
                if (\is_array($uploadedFile)) {
                    $parsed[$field] = self::parseUploadedFiles($uploadedFile);
                }
                continue;
            }

            $parsed[$field] = [];
            if (!\is_array($uploadedFile['error'])) {
                $parsed[$field] = new self(
                    $uploadedFile['tmp_name'],
                    isset($uploadedFile['size']) ? $uploadedFile['size'] : null,
                    $uploadedFile['error'],
                    isset($uploadedFile['name']) ? $uploadedFile['name'] : null,
                    isset($uploadedFile['type']) ? $uploadedFile['type'] : null
                );
            } else {
                $subArray = [];
                foreach ($uploadedFile['error'] as $fileIdx => $error) {
                    // Normalize sub array and re-parse to move the input's key name up a level
                    $subArray[$fileIdx]['name'] = $uploadedFile['name'][$fileIdx];
                    $subArray[$fileIdx]['type'] = $uploadedFile['type'][$fileIdx];
                    $subArray[$fileIdx]['tmp_name'] = $uploadedFile['tmp_name'][$fileIdx];
                    $subArray[$fileIdx]['error'] = $uploadedFile['error'][$fileIdx];
                    $subArray[$fileIdx]['size'] = $uploadedFile['size'][$fileIdx];

                    $parsed[$field] = self::parseUploadedFiles($subArray);
                }
            }
        }

        return $parsed;
    }
}
