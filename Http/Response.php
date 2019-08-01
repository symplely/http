<?php

declare(strict_types=1);

namespace Async\Http;

use Async\Http\MessageAbstract;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class Response
 *
 * @package Async\Http
 */
class Response extends MessageAbstract implements ResponseInterface
{
    /**
     * Valid HTTP status codes and reasons.
     *
     * Verified 2019-01-20
     *
     * @see https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     *
     * @var string[]
     */
    protected $validStatusCodes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * HTTP reason phrase.
     *
     * @var string
     */
    protected $reasonPhrase = '';

    /**
     * HTTP status code.
     *
     * @var int
     */
    protected $statusCode;

    /**
     * @param int|string $bodyStatusCode
     * @param StreamInterface|resource|string $body
     * @param array $headers Array of string|string[]
     */
    public function __construct($bodyStatusCode = 200, $body = '', array $headers = [])
    {
        if (!\is_int($bodyStatusCode) && empty($body)) {
            $body = $bodyStatusCode;
            $bodyStatusCode = 200;;
        }

        $this->body         = $this->filterBody($body);
        $this->headers      = $this->filterHeaders($headers);
        $this->statusCode   = $this->filterStatusCode($bodyStatusCode);
        $this->reasonPhrase = $this->filterReasonPhrase('');
    }

    /**
     * {@inheritdoc}
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * {@inheritDoc}
     */
    public function withStatus($code, $reasonPhrase = ''): ResponseInterface
    {
        $message = clone $this;

        $message->statusCode   = $this->filterStatusCode($code);
        $message->reasonPhrase = $this->filterReasonPhrase(
            $reasonPhrase,
            $message->statusCode
        );

        return $message;
    }

    /**
     * Create a new response.
     *
     * @param int $code HTTP status code; defaults to 200
     * @param string $reasonPhrase Reason phrase to associate with status code
     *     in generated response; if none is provided implementations MAY use
     *     the defaults as suggested in the HTTP specification.
     *
     * @return ResponseInterface
     */
    public static function create(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $response = new self();
        return $response->withStatus($code, $reasonPhrase);
    }

    /**
     * Filters a status code to make sure it's valid.
     *
     * @param int $statusCode
     *
     * @return int
     *
     * @throws \InvalidArgumentException When invalid code given.
     */
    private function filterStatusCode(int $statusCode): int
    {
        if (!isset($this->validStatusCodes[$statusCode])) {
            throw new \InvalidArgumentException(
                sprintf('HTTP status code "%s" is invalid.', $statusCode)
            );
        }

        return $statusCode;
    }

    /**
     * Filters a reason phrase to make sure it's valid.
     *
     * @param string $reasonPhrase
     * @param int|null $statusCode
     *
     * @return string
     */
    private function filterReasonPhrase(string $reasonPhrase, ?int $statusCode = null): string
    {
        if ($statusCode === null) {
            $statusCode = $this->statusCode;
        }

        if (empty($reasonPhrase)
            && !empty($this->validStatusCodes[$statusCode])
        ) {
            return $this->validStatusCodes[$statusCode];
        }

        return $reasonPhrase;
    }
}
