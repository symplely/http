<?php

namespace Async\Tests;

use RuntimeException;
use Async\Http\Response;
use Async\Http\Sessions;
use Async\Http\ServerRequestInterface;
use Async\Http\ServerRequestFactory;
use Async\Http\SessionsInterface;
use PHPUnit\Framework\TestCase;

session_set_cookie_params(
    1,
    '/foo/bar',
    '.example.com',
    true,
    true
);

\ini_set('session.use_trans_sid', '0');
\ini_set('session.use_cookies', '0');
\ini_set('session.use_only_cookies', '1');
\ini_set('session.cache_limiter', '');

class SessionsTest extends TestCase
{
    protected $stream;
    protected $time;

    protected function setUp(): void
    {
        if (\session_status() == \PHP_SESSION_ACTIVE) {
            \session_unset();
            \session_destroy();
            \session_write_close();
        }

        $this->storage = new Sessions();
        $this->time = \time();
    }

    protected function tearDown(): void
    {
        $this->storage->destroy();
        $this->storage = null;
        $this->time = null;
    }

    protected function newSession($cacheLimiter)
    {
        $this->storage->destroy();
        return new Sessions('', $cacheLimiter);
    }

    public function testIni_use()
    {
        $handler = $this->newSession('');
        $this->assertTrue(\ini_get('session.use_trans_sid') == false);
        $this->assertTrue(\ini_get('session.use_cookies') == false);
        $this->assertTrue(\ini_get('session.use_only_cookies') == true);
        $this->assertTrue(\ini_get('session.cache_limiter') == '');
    }

    protected function timestamp($adj = 0)
    {
        return \gmdate('D, d M Y H:i:s T', $this->time + $adj);
    }

    protected function assertSessionCookie(array $headers, $sessionId)
    {
        $time = \time();

        $cookie = '';
        if (isset($headers['Set-Cookie'])) {
            $cookie = $headers['Set-Cookie'][0];
        } else {
            foreach($headers as $value) {
                $cookie .= $value[0];
            }
        }
        $parts = \explode(';', $cookie);

        // PHPSESSID=...
        $expect = \session_name() . "={$sessionId}";
        $actual = \trim($parts[0]);
        $this->assertSame($expect, $actual);

        // expires=...
        $expect = 'expires=' . $this->timestamp(+1);
        $actual = \trim($parts[1]);
        $this->assertSame($expect, $actual);

        // max-age=...
        $expect = 'max-age=1';
        $actual = \trim($parts[2]);
        $this->assertSame($expect, $actual);

        // domain
        $expect = 'domain=.example.com';
        $actual = \trim($parts[3]);
        $this->assertSame($expect, $actual);

        // path
        $expect = 'path=/foo/bar';
        $actual = \trim($parts[4]);
        $this->assertSame($expect, $actual);

        // secure; httponly
        $this->assertSame('secure', \trim($parts[5]));
        $this->assertSame('httponly', \trim($parts[6]));
    }

    public function testImplementsSessionInterface()
    {
        $session = new Sessions();
        $this->assertInstanceOf(SessionsInterface::class, $session);
    }

    public function testSettingDataInSessionMakesItAccessible()
    {
        $session = $this->newSession('');
        $this->assertFalse($session->has('foo'));
        $session->set('foo', 'bar');
        $this->assertTrue($session->has('foo'));
        $this->assertSame('bar', $session->get('foo'));
        return $session;
    }

    /**
     * @depends testSettingDataInSessionMakesItAccessible
     */
    public function testToArrayReturnsAllDataPreviouslySet(SessionsInterface $session)
    {
        $this->assertSame(['foo' => 'bar'], $session->toArray());
    }

    /**
     * @depends testSettingDataInSessionMakesItAccessible
     */
    public function testCanUnsetDataInSession(SessionsInterface $session)
    {
        $session->unset('foo');
        $this->assertFalse($session->has('foo'));
    }

    public function testClearingSessionRemovesAllData()
    {
        $original = [
            'foo' => 'bar',
            'baz' => 'bat',
        ];
        $session = $this->storage;
        $session->fromArray($original);
        $this->assertSame($original, $session->toArray());

        $session->clear();
        $this->assertNotSame($original, $session->toArray());
        $this->assertSame([], $session->toArray());
    }

    public function testGetIdReturnsEmptyStringIfNoIdentifierProvidedToConstructor()
    {
        $session = $this->storage;
        $this->assertSame('', $session->getId());
    }

    public function testGetIdReturnsValueProvidedToConstructor()
    {
        $session = new Sessions('1234abcd');
        $this->assertSame('1234abcd', $session->getId());
    }

    public function testFromArray(): void
    {
        $this->storage->fromArray(['a' => 'def']);
        $this->assertEquals(['a' => 'def'], $this->storage->toArray());
    }

    public function testAddToStorageAndGetFromStorage(): void
    {
        $this->storage->fromArray(['a' => 'def']);
        $this->storage['eaf'] = 'a';
        $this->storage->def = 'b';

        $this->assertTrue($this->storage->has('a'));
        $this->assertTrue(isset($this->storage->a));
        $this->assertEquals('a', $this->storage->eaf);
        $this->assertEquals('b', $this->storage['def']);
        $this->assertEquals(['a' => 'def', 'eaf' => 'a', 'def' => 'b'], $this->storage->toArray());
        $this->assertCount(3, $this->storage);
    }

    public function testUnsetKey(): void
    {
        $this->storage->fromArray(['a' => 'def']);
        $this->storage['qwerty'] = 'zxcvb';
        $this->assertCount(2, $this->storage);

        unset($this->storage->qwerty);
        $this->assertCount(1, $this->storage);

        $this->storage['uiop'] = 'hjkl';
        $this->assertCount(2, $this->storage);

        unset($this->storage['uiop']);
        $this->assertCount(1, $this->storage);

        $this->storage['rtyu'] = 'fghj';
        $this->assertCount(2, $this->storage);

        $this->storage->unset('rtyu');
        $this->assertCount(1, $this->storage);
    }

    public function testClose(): void
    {
        $this->storage->fromArray(['a' => 'def']);
        $this->storage['eaf'] = 'a';

        $this->storage->close();
        $this->assertEquals(\PHP_SESSION_NONE, \session_status());
        $this->assertCount(2, $this->storage);
    }

    public function testDestroy(): void
    {
        $this->storage->fromArray(['a' => 'def']);
        $this->storage['eaf'] = 'a';

        $this->storage->destroy();
        $this->assertEquals(\PHP_SESSION_NONE, \session_status());
        $this->assertEmpty($this->storage);
        $this->assertEmpty($_SESSION);
    }

    public function testGetIterator()
    {
        $this->storage->fromArray(['a' => 'def']);
        $this->storage['eaf'] = 'a';
        foreach ($this->storage as $key => $value) {
            $this->assertEquals($this->storage->{$key}, $value);
        }
    }

    public function testSessionStart()
    {
        $middleware = $this->getMockBuilder(Sessions::class)
            ->getMock();
        $middleware->expects(self::once())
            ->method('__invoke');
        $request = (ServerRequestFactory::fromGlobals())->withAttribute(Sessions::SESSION_KEY, $middleware);
        $callback = function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        };
        $middleware($request, new Response(), $callback);
    }

    public function testRestartPriorSession_sessionStart()
    {
        $request = ServerRequestFactory::fromGlobals();

        $response = new Response();


        $handler = $this->storage;
        $next = function ($request, $response) use ($handler) {
            $handler->start();
            return $response;
        };

        $response = $handler($request, $response, $next);

        $this->assertSessionCookie($response->getHeaders(), session_id());

        $handler->close();
    }

    public function testPriorSession_restartWithoutRegenerate()
    {
        // fake a prior session
        session_start();
        $sessionId = session_id();
        session_write_close();

        // now on to "this" session
        $_COOKIE[\session_name()] = $sessionId;
        $request = ServerRequestFactory::fromGlobals();

        $response = new Response();

        $handler = new Sessions();
        $next = function ($request, $response) {
            session_start();
            session_destroy();
            return $response;
        };

        $response = $handler($request, $response, $next);

        $expect = [];
        $actual = $response->getHeaders();
        $this->assertSame($expect, $actual);

        \session_write_close();
    }


    public function testPriorSession_restartAndRegenerate()
    {
        // fake a prior session
        \session_start();
        $sessionId = \session_id();
        \session_write_close();

        // now on to "this" session
        $_COOKIE[\session_name()] = $sessionId;
        $request = ServerRequestFactory::fromGlobals();

        $response = new Response();

        $regeneratedId = '';
        $handler = new Sessions($sessionId);
        $next = function ($request, $response) use ($handler, &$regeneratedId) {
            $handler->start();
            \session_regenerate_id();
            $regeneratedId = \session_id();
            return $response;
        };

        $response = $handler($request, $response, $next);

        $this->assertSessionCookie($response->getHeaders(), $regeneratedId);

        \session_write_close();
    }

    protected function getCacheLimiterHeaders($cacheLimiter)
    {
        $request = ServerRequestFactory::fromGlobals();

        $response = new Response();

        $handler = new Sessions('', $cacheLimiter);
        $next = function ($request, $response) use ($handler) {
            $handler->start();
            return $response;
        };

        $response = $handler($request, $response, $next);

        $headers = $response->getHeaders();
        $this->assertSessionCookie($headers, \session_id());
        unset($headers['Set-Cookie']);

        session_write_close();
        return $headers;
    }

    public function testCacheLimiter_public()
    {
        $expect = array(
            'Expires' => array(
                $this->timestamp(+10800),
            ),
            'Cache-Control' => array(
                'public, max-age=10800',
            ),
            'Last-Modified' => array(
                $this->timestamp(),
            ),
        );
        $actual = $this->getCacheLimiterHeaders('public');
        $this->assertSame($expect, $actual);
    }

    public function testCacheLimiter_privateNoExpire()
    {
        $expect = array(
            'Cache-Control' => array(
                'private, max-age=10800, pre-check=10800'
            ),
            'Last-Modified' => array(
                $this->timestamp()
            ),
        );
        $actual = $this->getCacheLimiterHeaders('private_no_expire');
        $this->assertSame($expect, $actual);
    }

    public function testCacheLimiter_private()
    {
        $expect = array(
            'Expires' => [
                'Thu, 19 Nov 1981 08:52:00 GMT',
            ],
            'Cache-Control' => array(
                'private, max-age=10800, pre-check=10800'
            ),
            'Last-Modified' => array(
                $this->timestamp()
            ),
        );
        $actual = $this->getCacheLimiterHeaders('private');
        $this->assertSame($expect, $actual);
    }

    public function testCacheLimiter_nocache()
    {
        $expect = [
            'Expires' => [
                'Thu, 19 Nov 1981 08:52:00 GMT',
            ],
            'Cache-Control' => [
                'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            ],
            'Pragma' => [
                'no-cache',
            ],
        ];
        $actual = $this->getCacheLimiterHeaders('nocache');
        $this->assertSame($expect, $actual);
    }
}
