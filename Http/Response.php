<?php

declare(strict_types=1);

namespace Async\Http;

use Async\Http\MessageAbstract;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class Response
 *
 * @package Async\Http
 */
class Response extends MessageAbstract implements ResponseInterface, StatusCodeInterface
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
    public const REASON_PHRASES = [        
		//Informational 1xx
		self::STATUS_CONTINUE                        => 'Continue',
		self::STATUS_SWITCHING_PROTOCOLS             => 'Switching Protocols',
		self::STATUS_PROCESSING                      => 'Processing',
        103                                          => 'Early Hints',
		//Successful 2xx
		self::STATUS_OK                              => 'OK',
		self::STATUS_CREATED                         => 'Created',
		self::STATUS_ACCEPTED                        => 'Accepted',
		self::STATUS_NON_AUTHORITATIVE_INFORMATION   => 'Non-Authoritative Information',
		self::STATUS_NO_CONTENT                      => 'No Content',
		self::STATUS_RESET_CONTENT                   => 'Reset Content',
		self::STATUS_PARTIAL_CONTENT                 => 'Partial Content',
		self::STATUS_MULTI_STATUS                    => 'Multi-Status',
		self::STATUS_ALREADY_REPORTED                => 'Already Reported',
		self::STATUS_IM_USED                         => 'IM Used',
		//Redirection 3xx
		self::STATUS_MULTIPLE_CHOICES                => 'Multiple Choices',
		self::STATUS_MOVED_PERMANENTLY               => 'Moved Permanently',
		self::STATUS_FOUND                           => 'Found',
		self::STATUS_SEE_OTHER                       => 'See Other',
		self::STATUS_NOT_MODIFIED                    => 'Not Modified',
		self::STATUS_USE_PROXY                       => 'Use Proxy',
		self::STATUS_RESERVED                        => 'Reserved',
		self::STATUS_TEMPORARY_REDIRECT              => 'Temporary Redirect',
		self::STATUS_PERMANENT_REDIRECT              => 'Permanent Redirect',
		//Client Error 4xx
		self::STATUS_BAD_REQUEST                     => 'Bad Request',
		self::STATUS_UNAUTHORIZED                    => 'Unauthorized',
		self::STATUS_PAYMENT_REQUIRED                => 'Payment Required',
		self::STATUS_FORBIDDEN                       => 'Forbidden',
		self::STATUS_NOT_FOUND                       => 'Not Found',
		self::STATUS_METHOD_NOT_ALLOWED              => 'Method Not Allowed',
		self::STATUS_NOT_ACCEPTABLE                  => 'Not Acceptable',
		self::STATUS_PROXY_AUTHENTICATION_REQUIRED   => 'Proxy Authentication Required',
		self::STATUS_REQUEST_TIMEOUT                 => 'Request Timeout',
		self::STATUS_CONFLICT                        => 'Conflict',
		self::STATUS_GONE                            => 'Gone',
		self::STATUS_LENGTH_REQUIRED                 => 'Length Required',
		self::STATUS_PRECONDITION_FAILED             => 'Precondition Failed',
		self::STATUS_PAYLOAD_TOO_LARGE               => 'Request Entity Too Large',
		self::STATUS_URI_TOO_LONG                    => 'Request-URI Too Long',
		self::STATUS_UNSUPPORTED_MEDIA_TYPE          => 'Unsupported Media Type',
		self::STATUS_RANGE_NOT_SATISFIABLE           => 'Requested Range Not Satisfiable',
		self::STATUS_EXPECTATION_FAILED              => 'Expectation Failed',
		self::STATUS_IM_A_TEAPOT                     => 'I\'m a teapot',
		self::STATUS_MISDIRECTED_REQUEST             => 'Misdirected Request',
		self::STATUS_UNPROCESSABLE_ENTITY            => 'Unprocessable Entity',
		self::STATUS_LOCKED                          => 'Locked',
		self::STATUS_FAILED_DEPENDENCY               => 'Failed Dependency',
		425                                          => 'Unordered Collection',
		self::STATUS_UPGRADE_REQUIRED                => 'Upgrade Required',
		self::STATUS_PRECONDITION_REQUIRED           => 'Precondition Required',
		self::STATUS_TOO_MANY_REQUESTS               => 'Too Many Requests',
		self::STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE => 'Request Header Fields Too Large',
		444                                          => 'Connection Closed Without Response',
		self::STATUS_UNAVAILABLE_FOR_LEGAL_REASONS   => 'Unavailable For Legal Reasons',
		499                                          => 'Client Closed Request',
		//Server Error 5xx
		self::STATUS_INTERNAL_SERVER_ERROR           => 'Internal Server Error',
		self::STATUS_NOT_IMPLEMENTED                 => 'Not Implemented',
		self::STATUS_BAD_GATEWAY                     => 'Bad Gateway',
		self::STATUS_SERVICE_UNAVAILABLE             => 'Service Unavailable',
		self::STATUS_GATEWAY_TIMEOUT                 => 'Gateway Timeout',
		self::STATUS_VERSION_NOT_SUPPORTED           => 'HTTP Version Not Supported',
		self::STATUS_VARIANT_ALSO_NEGOTIATES         => 'Variant Also Negotiates',
		self::STATUS_INSUFFICIENT_STORAGE            => 'Insufficient Storage',
		self::STATUS_LOOP_DETECTED                   => 'Loop Detected',
		self::STATUS_NOT_EXTENDED                    => 'Not Extended',
		self::STATUS_NETWORK_AUTHENTICATION_REQUIRED => 'Network Authentication Required',
		599                                          => 'Network Connect Timeout Error',
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
    protected $statusCode = self::STATUS_OK;

    /**
     * @param int|string $bodyStatusCode
     * @param StreamInterface|resource|string $body
     * @param array $headers Array of string|string[]
     */
    public function __construct($bodyStatusCode = self::STATUS_OK, $body = '', array $headers = [])
    {
        if (!\is_int($bodyStatusCode) && empty($body)) {
            $body = $bodyStatusCode;
            $bodyStatusCode = self::STATUS_OK;
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
    public static function create(int $code = self::STATUS_OK, string $reasonPhrase = ''): ResponseInterface
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
        if (!isset($this::REASON_PHRASES[$statusCode])) {
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
            && !empty($this::REASON_PHRASES[$statusCode])
        ) {
            return $this::REASON_PHRASES[$statusCode];
        }

        return $reasonPhrase;
    }
}
