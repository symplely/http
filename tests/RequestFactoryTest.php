<?php

namespace Async\Tests;

use Async\Http\RequestFactory;
use PHPUnit\Framework\TestCase;

class RequestFactoryTest extends TestCase
{
    public function testCreateRequest()
    {
        $factory = new RequestFactory();
        $request = $factory->createRequest('GET', 'http://domain.tld:9090/subdir?test=true#phpunit');
        $this->assertInstanceOf('Psr\\Http\\Message\\RequestInterface', $request);
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertInstanceOf('Psr\\Http\\Message\\UriInterface', $uri = $request->getUri());
        $this->assertEquals('http://domain.tld:9090/subdir?test=true#phpunit', (string)$uri);
    }
}
