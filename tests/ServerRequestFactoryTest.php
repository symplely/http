<?php

namespace Async\Tests;

use Async\Http\UploadedFile;
use Async\Http\ServerRequestFactory;
use PHPUnit\Framework\TestCase;

class ServerRequestFactoryTest extends TestCase
{
    public function mock(array $data = []): array
    {
        if ((isset($data['HTTPS']) && $data['HTTPS'] !== 'off')
            || ((isset($data['REQUEST_SCHEME']) && $data['REQUEST_SCHEME'] === 'https'))
        ) {
            $scheme = 'https';
            $port = 443;
        } else {
            $scheme = 'http';
            $port = 80;
        }

        return array_merge([
            'HTTP_ACCEPT_CHARSET' => 'utf-8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US',
            'HTTP_USER_AGENT' => 'Http Server',
            'QUERY_STRING' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_SCHEME' => $scheme,
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
            'REQUEST_URI' => '',
            'SCRIPT_NAME' => '',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => $port,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ], $data);
    }

    public function testCreateServerRequest()
    {
        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest('POST', 'http://domain.tld:9090/subdir?test=true#phpunit');
        $this->assertInstanceOf(\Psr\Http\Message\ServerRequestInterface::class, $request);
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertInstanceOf(\Psr\Http\Message\UriInterface::class, $request->getUri());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('domain.tld', $request->getUri()->getHost());
        $this->assertEquals(9090, $request->getUri()->getPort());
        $this->assertEquals('http://domain.tld:9090/subdir?test=true#phpunit', (string)$request->getUri());
    }

    public function testCreateServerRequestFromArray()
    {
        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequestFromArray(array(
            'CONTENT_LENGTH' => '128',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'HTTP_HOST' => 'domain.tld:9090',
            'HTTP_INVALID' => null,
            'REDIRECT_HOST' => 'http://domain.tld',
            'HTTP_X_REWRITE_URL' => '/some-fancy-url',
            'HTTP_X_ORIGINAL_URL' => '/subdir?test=true#phpunit',
            'QUERY_STRING' => 'test=true',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => 'http://domain.tld:9090/subdir#phpunit',
            'SERVER_PORT' => '9090',
            'SERVER_PROTOCOL' => 'HTTP/1.0'
        ));
        $this->assertInstanceOf(\Psr\Http\Message\ServerRequestInterface::class, $request);
        $this->assertEquals('1.0', $request->getProtocolVersion());
        $this->assertInstanceOf(\Psr\Http\Message\UriInterface::class, $request->getUri());
        $this->assertEquals('128', $request->getHeaderLine('Content-Length'));
        $this->assertEquals('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('domain.tld', $request->getUri()->getHost());
        $this->assertEquals(9090, $request->getUri()->getPort());
        $this->assertEquals('http://domain.tld:9090/subdir?test=true#phpunit', (string)$request->getUri());
    }

    public function testServerRequestFactoryGetUriVersion()
    {
        $factory = new ServerRequestFactory();
        $uri = $factory->getUri(['SERVER_NAME' => 'localhost', 'SERVER_PORT' => '88', 'ORIG_PATH_INFO' => '/subdir/']);
        $this->assertInstanceOf(\Psr\Http\Message\UriInterface::class, $uri);
        $request = $factory->createServerRequestFromArray(array('REQUEST_METHOD' => 'GET'));
        $this->assertEquals('1.1', $request->getProtocolVersion());
    }

    public function testCreateFromGlobals()
    {
        $_SERVER = $this->mock([
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_HOST' => 'example.com',
            'PHP_AUTH_PW' => 'sekrit',
            'PHP_AUTH_USER' => 'josh',
            'QUERY_STRING' => 'abc=123',
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_CONTENT-TYPE' => 'multipart/form-data',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
            'REQUEST_URI' => '/foo/bar',
            'SCRIPT_NAME' => '/index.php',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 8080,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ]);

        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('1.1', $request->getProtocolVersion());

        $this->assertEquals('application/json', $request->getHeaderLine('Accept'));
        $this->assertEquals('utf-8', $request->getHeaderLine('Accept-Charset'));
        $this->assertEquals('en-US', $request->getHeaderLine('Accept-Language'));
        $this->assertEquals('multipart/form-data', $request->getHeaderLine('Content-Type'));

        $uri = $request->getUri();
        $this->assertEquals('josh:sekrit', $uri->getUserInfo());
        $this->assertEquals('example.com', $uri->getHost());
        $this->assertEquals('8080', $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());
    }

    public function testCreateFromGlobalsWithParsedBody()
    {
        $_SERVER = $this->mock([
            'HTTP_CONTENT_TYPE' => 'multipart/form-data',
            'REQUEST_METHOD' => 'POST',
        ]);

        $_POST = [
            'def' => '456',
        ];

        $request = ServerRequestFactory::fromGlobals();

        // $_POST should be placed into the parsed body
        $this->assertEquals($_POST, $request->getParsedBody());
    }

    public function testCreateFromGlobalsBodyPointsToPhpInput()
    {
        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('php://input', $request->getBody()->getMetadata('uri'));
    }

    public function testCreateFromGlobalsWithUploadedFiles()
    {
        $_SERVER = $this->mock([
            'HTTP_CONTENT_TYPE' => 'multipart/form-data',
            'REQUEST_METHOD' => 'POST',
        ]);

        $_FILES = [
            'uploaded_file' => [
                'name' => [
                    0 => 'foo.jpg',
                    1 => 'bar.jpg',
                ],

                'type' => [
                    0 => 'image/jpeg',
                    1 => 'image/jpeg',
                ],

                'tmp_name' => [
                    0 => '/tmp/phpUA3XUw',
                    1 => '/tmp/phpXUFS0x',
                ],

                'error' => [
                    0 => 0,
                    1 => 0,
                ],

                'size' => [
                    0 => 358708,
                    1 => 236162,
                ],
            ]
        ];

        $request = ServerRequestFactory::fromGlobals();

        // $_FILES should be mapped to an array of UploadedFile objects
        $uploadedFiles = $request->getUploadedFiles();
        $this->assertCount(1, $uploadedFiles);
        $this->assertArrayHasKey('uploaded_file', $uploadedFiles);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['uploaded_file'][0]);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['uploaded_file'][1]);
    }

    public function testCreateFromGlobalsParsesBodyWithFragmentedContentType()
    {
        $_SERVER = $this->mock([
            'HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded;charset=utf-8',
            'REQUEST_METHOD' => 'POST',
        ]);

        $_POST = [
            'def' => '456',
        ];

        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals($_POST, $request->getParsedBody());
    }

    public function testFromGlobalsUsesCookieHeaderInsteadOfCookieSuperGlobal()
    {
        $_COOKIE = [
            'foo_bar' => 'bat',
        ];

        $_SERVER = $this->mock([
            'HTTP_COOKIE' => 'foo_bar=baz'
        ]);

        $request = ServerRequestFactory::fromGlobals();
        $this->assertSame(['foo_bar' => 'baz'], $request->getCookieParams());
    }

    public function cookieHeaderValues()
    {
        return [
            'ows-without-fold' => [
                "\tfoo=bar ",
                ['foo' => 'bar'],
            ],
            'url-encoded-value' => [
                'foo=bar%3B+',
                ['foo' => 'bar; '],
            ],
            'double-quoted-value' => [
                'foo="bar"',
                ['foo' => 'bar'],
            ],
            'multiple-pairs' => [
                'foo=bar; baz="bat"; bau=bai',
                ['foo' => 'bar', 'baz' => 'bat', 'bau' => 'bai'],
            ],
            'same-name-pairs' => [
                'foo=bar; foo="bat"',
                ['foo' => 'bat'],
            ],
            'period-in-name' => [
                'foo.bar=baz',
                ['foo.bar' => 'baz'],
            ],
        ];
    }

    /**
     * @dataProvider cookieHeaderValues
     * @param string $cookieHeader
     * @param array $expectedCookies
     */
    public function testCookieHeaderVariations($cookieHeader, array $expectedCookies)
    {
        $_SERVER = $this->mock([
            'HTTP_COOKIE' => $cookieHeader
        ]);

        $request = ServerRequestFactory::fromGlobals();
        $this->assertSame($expectedCookies, $request->getCookieParams());
    }
}
