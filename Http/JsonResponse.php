<?php

namespace Async\Http;

use Async\Http\Response;

class JsonResponse extends Response
{
    /**
     * @param int $statusCod
     * @param mixed $body Any value that can be JSON encoded.
     */
    public function __construct(
        int $statusCode = 200,
        $body = ''
    ) {
        $json = json_encode($body);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode data as JSON.');
        }

        parent::__construct($statusCode, $json);

        // forcibly override content type
        $this->headers = $this->withHeader('Content-Type', 'application/json')->getHeaders();
    }
}
