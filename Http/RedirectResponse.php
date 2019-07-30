<?php

namespace Async\Http;

use Async\Http\Response;

class RedirectResponse extends Response
{
    /**
     * Creates a response that redirects to a new URI.
     *
     * @param string $uri URI to redirect to.
     * @param int $statusCode HTTP status code.
     * @param array $headers Array of string|string[]
     */
    public function __construct(int $statusCode = 302, string $uri, array $headers = [])
    {
        $body = '<html><body><p>This page has been moved <a href="'
            .htmlspecialchars($uri, ENT_QUOTES, 'UTF-8')
            .'">here</a>.</p></body></html>';

        parent::__construct($statusCode, $body, $headers);

        // forcibly override location
        $this->headers = $this->withHeader('Location', $uri)->getHeaders();
    }
}
