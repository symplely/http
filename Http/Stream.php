<?php

declare(strict_types=1);

namespace Async\Http;

use Psr\Http\Message\StreamInterface;

/**
 * Class Stream
 *
 * @package Async\Http
 */
class Stream implements StreamInterface
{
    /**
     * @var string[]
     */
    private const WRITABLE_MODES = ['r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+'];

    /**
     * @var string[]
     */
    private const READABLE_MODES = ['r', 'r+', 'w+', 'a+', 'x+', 'c+'];

    /**
     * Stream of data.
     *
     * @var resource|null
     */
    private $stream = null;

    /**
     * @param resource|string|mixed $stream
     *
     * @throws \InvalidArgumentException If a resource or string isn't given.
     */
    public function __construct($stream = 'php://memory')
    {
        if (\is_resource($stream)) {
            $this->stream = $stream;
            \rewind($this->stream);
        } elseif (\is_string($stream)) {
            $handle = \fopen('php://temp', 'rb+');
            if ($handle) {
                $this->stream = $handle;
                \fwrite($this->stream, $stream);
                \rewind($this->stream);
            }
        } else {
            throw new \InvalidArgumentException(
                \sprintf(
                    '%s must be constructed with a resource or string; %s given.',
                    self::class,
                    \gettype($stream)
                )
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        if ($this->stream === null) {
            return '';
        }
        $string = \stream_get_contents($this->stream, -1, 0);
        if (!$string) {
            return '';
        }
        return $string;
    }

    /**
     * {@inheritDoc}
     */
    public function close(): void
    {
        if ($this->stream === null) {
            return;
        }
        \fclose($this->stream);
        $this->stream = null;
    }

    /**
     * {@inheritDoc}
     */
    public function detach()
    {
        $stream = $this->stream;
        $this->stream = null;
        return $stream;
    }

    /**
     * {@inheritDoc}
     */
    public function getSize(): ?int
    {
        if ($this->stream === null) {
            return null;
        }

        $stats = \fstat($this->stream);
        return $stats['size'] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function tell(): int
    {
        if ($this->stream === null) {
            throw new \RuntimeException('Stream is not open.');
        }

        $position = ftell($this->stream);
        if ($position === false) {
            throw new \RuntimeException('Unable to get position of stream.');
        }

        return $position;
    }

    /**
     * {@inheritDoc}
     */
    public function eof(): bool
    {
        return $this->stream === null ? true : feof($this->stream);
    }

    /**
     * {@inheritDoc}
     */
    public function isSeekable(): bool
    {
        if ($this->invalidResource()) {
            return false;
        }

        $seekable = $this->getMetadata('seekable');
        if ($seekable === null) {
            return false;
        }

        return $seekable;
    }

    protected function invalidResource()
    {
        return  $this->stream == null || !\is_resource($this->stream);
    }

    /**
     * {@inheritDoc}
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if ($this->stream === null) {
            throw new \RuntimeException('Stream is not open.');
        }

        if (0 > \fseek($this->stream, $offset, $whence)) {
            throw new \RuntimeException(
                \sprintf('Failed to seek to offset %s.', $offset)
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): void
    {
        if ($this->stream === null) {
            throw new \RuntimeException('Stream is not open.');
        }

        if (!\rewind($this->stream)) {
            throw new \RuntimeException('Failed to rewind stream.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isWritable(): bool
    {
        if ($this->stream === null) {
            return false;
        }

        $mode = $this->getMetadata('mode');
        if ($mode === null) {
            return false;
        }

        $mode = \str_replace(['b', 'e'], '', $mode);
        return \in_array($mode, self::WRITABLE_MODES, true);
    }

    /**
     * {@inheritDoc}
     */
    public function write($string): int
    {
        if ($this->stream === null) {
            throw new \RuntimeException('Stream is not open.');
        }
        if (!$this->isWritable()) {
            throw new \RuntimeException('Stream is not writable.');
        }
        return \fwrite($this->stream, $string) ?: 0;
    }

    /**
     * {@inheritDoc}
     */
    public function isReadable(): bool
    {
        if ($this->stream === null) {
            return false;
        }
        $mode = $this->getMetadata('mode');
        if ($mode === null) {
            return false;
        }
        $mode = \str_replace(['b', 'e'], '', $mode);
        return \in_array($mode, self::READABLE_MODES, true);
    }

    /**
     * {@inheritDoc}
     */
    public function read($length): string
    {
        if ($this->stream === null) {
            throw new \RuntimeException('Stream is not open.');
        }

        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream is not readable.');
        }

        return \fread($this->stream, $length) ?: '';
    }

    /**
     * {@inheritDoc}
     */
    public function getContents(): string
    {
        if ($this->stream === null) {
            throw new \RuntimeException('Stream is not open.');
        }

        $string = \stream_get_contents($this->stream);
        if ($string === false) {
            throw new \RuntimeException('Failed to get contents of stream.');
        }

        return $string;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata($key = null)
    {
        if ($this->stream === null) {
            return null;
        }

        $metadata = \stream_get_meta_data($this->stream);
        if ($key) {
            $metadata = isset($metadata[$key]) ? $metadata[$key] : null;
        }

        return $metadata;
    }


    /**
     * Create a new stream from a string.
     *
     * The stream SHOULD be created with a temporary resource.
     *
     * @param string $content String content with which to populate the stream.
     *
     * @return StreamInterface
     */
    public static function create(string $content = ''): StreamInterface
    {
        return new self($content);
    }

    /**
     * Create a stream from an existing file.
     *
     * The file MUST be opened using the given mode, which may be any mode
     * supported by the `fopen` function.
     *
     * The `$filename` MAY be any string supported by `fopen()`.
     *
     * @param string $filename Filename or stream URI to use as basis of stream.
     * @param string $mode Mode with which to open the underlying filename/stream.
     *
     * @return StreamInterface
     * @throws \RuntimeException If the file cannot be opened.
     * @throws \InvalidArgumentException If the mode is invalid.
     */
    public static function createFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return new self(\fopen($filename, $mode));
    }

    /**
     * Create a new stream from an existing resource.
     *
     * The stream MUST be readable and may be writable.
     *
     * @param resource $resource PHP resource to use as basis of stream.
     *
     * @return StreamInterface
     */
    public static function createFromResource($resource): StreamInterface
    {
        return new self($resource);
    }
}
