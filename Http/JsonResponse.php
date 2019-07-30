<?php

declare(strict_types=1);

namespace Async\Http;

use Async\Http\Response;

class JsonResponse extends Response
{
    /**
     * @param mixed $body Any value that can be JSON encoded.
     * @param int $statusCode
     * @param array $headers Array of string|string[]
     */
    public function __construct(
        int $statusCode = 200,
        $body = '',
        array $headers = []
    ) {
        $json = \json_encode($body);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode data as JSON.');
        }

        parent::__construct($statusCode, $json, $headers);

        // forcibly override content type
        $this->headers = $this->withHeader('Content-Type', 'application/json')->getHeaders();
    }
}
