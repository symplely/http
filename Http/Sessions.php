<?php

declare(strict_types=1);

namespace Async\Http;

use Async\Http\SessionsInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class Sessions implements SessionsInterface
{
    const SESSION_KEY = '__SESSIONS_SESSION__';

    const SESSION_ID_LENGTH = 32;

    /**
     * The timestamp for "already expired."
     */
    const EXPIRED = 'Thu, 19 Nov 1981 08:52:00 GMT';

    /**
     * The cache limiter type, if any.
     *
     * @var string
     *
     * @see session_cache_limiter()
     */
    protected $cacheLimiter;

    /**
     * The cache expiration time in minutes.
     *
     * @var int
     *
     * @see session_cache_expire()
     */
    protected $cacheExpire;

    /**
     * The current Unix timestamp.
     *
     * @var int
     */
    protected $time;

    /**
     * Session data.
     *
     * @var mixed
     */
    private $data;

    /**
     * Session id.
     *
     * @var string
     */
    private $id;

    /**
     * Starts the session
     *
     * @param string $id The session id.
     * @param string $cacheLimiter The cache limiter type.
     * @param string $cacheExpire The cache expiration time in minutes.
     *
     * @throws RuntimeException when the ini settings not set or incorrect.
     */
    public function __construct(string $id = '', $cacheLimiter = 'nocache', $cacheExpire = 180)
    {
        $this->cacheLimiter = empty($cacheLimiter) ? 'nocache' : $cacheLimiter;
        $this->cacheExpire = (int) $cacheExpire;

        if (empty($id)) {
            $name = \session_name();
            if (isset($_COOKIE[$name])) {
                $id = $_COOKIE[$name];
            } elseif (isset($_GET[$name])) {
                $id = $_GET[$name];
            }
        }

        $this->id = $id;
        $this->start($id);
        $this->data = $_SESSION;

        if (\ini_get('session.use_trans_sid') != false) {
			// @codeCoverageIgnoreStart
            $message = "The .ini setting 'session.use_trans_sid' must be false.";
            throw new RuntimeException($message);
            // @codeCoverageIgnoreEnd
        }

        if (\ini_get('session.use_cookies') != false) {
			// @codeCoverageIgnoreStart
            $message = "The .ini setting 'session.use_cookies' must be false.";
            throw new RuntimeException($message);
            // @codeCoverageIgnoreEnd
        }

        if (\ini_get('session.use_only_cookies') != true) {
			// @codeCoverageIgnoreStart
            $message = "The .ini setting 'session.use_only_cookies' must be true.";
            throw new RuntimeException($message);
            // @codeCoverageIgnoreEnd
        }

        if (\ini_get('session.cache_limiter') !== '') {
			// @codeCoverageIgnoreStart
            $message = "The .ini setting 'session.cache_limiter' must be an empty string.";
            throw new RuntimeException($message);
            // @codeCoverageIgnoreEnd
        }

        \session_write_close();
        $_SESSION = null;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $middleware = null)
    {
        // retain the incoming session id
        $oldId = '';
        $oldName = \session_name();
        $cookies = $request->getCookieParams();
        if (! empty($cookies[$oldName])) {
            $oldId = $cookies[$oldName];
        }

        // update $_SESSION
        $this->close($oldId);

        $request = $request->withAttribute(static::SESSION_KEY, $this);

        // invoke the middleware
        if ($middleware instanceof RequestHandlerInterface) {
			$response = $middleware->handle($request);
        } elseif (\is_callable($middleware)) {
            $response = $middleware($request, $response);
        }

        // record the current time
        $this->time = \time();

        // is the session id still the same?
        $newId = \session_id();
        if ($newId !== $oldId) {
            // it changed; send the new one.
            // capture any session name changes as well.
            $response = $this->withSessionCookie($response, $newId);
        }

        // if there is a session id, also send the cache limiters
        if ($newId) {
            $response = $this->withCacheLimiter($response);
        }

        // update session id
        $this->id = $newId;

        // done!
        return $response;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function start($id = '')
    {
        \session_id($id);

        if (\session_status() !== \PHP_SESSION_ACTIVE) {
            \session_start([
                'use_trans_sid' => false,
                'use_cookies' => false,
                'use_only_cookies' => true,
                'cache_limiter' => ''
            ]);
        }
    }

    public function close($id = '')
    {
        $this->start(empty($id) ? $this->id : $id);
        $_SESSION = $this->toArray();
        \session_write_close();
    }

    public function destroy()
    {
        $this->start();
        $this->data = [];
        $this->regenerate();
        unset($_SESSION);
        unset($_COOKIE[\session_name()]);
        \session_unset();
        if (\session_status() == \PHP_SESSION_ACTIVE) {
            \session_destroy();
        }

        $_SESSION = [];
        $this->close();
    }

    /**
     * Get session from request.
     *
     * @param ServerRequestInterface $request
     *
     * @return Sessions|null
     */
    public static function getSession(ServerRequestInterface $request)
    {
        return $request->getAttribute(static::SESSION_KEY);
    }

    public function fromArray(array $data): void
    {
        $this->data = $data;
    }

    public function toArray() : array
    {
        $array = [];
        if (\is_array($this->data)) {
            foreach ($this->data as $key => $item) {
                $array[$key] = $item;
            }
        }

        return $array;
    }

    public function clear() : void
    {
        $this->data = [];
    }

    public function get(string $name, $default = null)
    {
        return $this->data[$name] ?? $default;
    }

    public function set(string $name, $value) : void
    {
        $this->data[$name] = $value;
    }

    public function has(string $name) : bool
    {
        return \array_key_exists($name, $this->data);
    }

    public function unset(string $name) : void
    {
        unset($this->data[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        $this->unset($offset);
    }

    /**
     * Get Offset
     *
     * @param  mixed $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->offsetGet($key);
    }
    /**
     * Set Offset
     *
     * @param  mixed $key
     * @param  mixed $value
     * @return void
     */
    public function __set($key, $value): void
    {
        $this->offsetSet($key, $value);
    }
    /**
     * Isset Offset
     *
     * @param  mixed   $key
     * @return bool
     */
    public function __isset($key): bool
    {
        return $this->offsetExists($key);
    }
    /**
     * Unset Offset
     *
     * @param  mixed $key
     * @return void
     */
    public function __unset($key): void
    {
        $this->offsetUnset($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->data);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function regenerate(): void
    {
        $this->id = static::generateToken();
    }

    public function generateTokenFor(string $keyName = '__csrf') : string
    {
        $this->data[$keyName] = static::generateToken();
        return $this->data[$keyName];
    }

    public function validateTokenFor(string $token, string $csrfKey = '__csrf') : bool
    {
        if (! isset($this->data[$csrfKey])) {
            return false;
        }

        return $token === $this->data[$csrfKey];
    }

    public static function generateToken() : string
    {
        return \bin2hex(\random_bytes(static::SESSION_ID_LENGTH));
    }

    /**
     * Adds a session cookie header to the Response.
     *
     * @param ResponseInterface $response The HTTP response.
     *
     * @param string $sessionId The new session ID.
     *
     * @return ResponseInterface
     *
     * @see https://github.com/php/php-src/blob/PHP-5.6.20/ext/session/session.c#L1337-L1408
     *
     */
    protected function withSessionCookie(ResponseInterface $response, $sessionId)
    {
        $cookie = \urlencode(\session_name()) . '=' . \urlencode($sessionId);

        $params = \session_get_cookie_params();

        if ($params['lifetime']) {
            $expires = $this->timestamp($params['lifetime']);
            $cookie .= "; expires={$expires}; max-age={$params['lifetime']}";
        }

        if ($params['domain']) {
            $cookie .= "; domain={$params['domain']}";
        }

        if ($params['path']) {
            $cookie .= "; path={$params['path']}";
        }

        if ($params['secure']) {
            $cookie .= '; secure';
        }

        if ($params['httponly']) {
            $cookie .= '; httponly';
        }

        return $response->withAddedHeader('Set-Cookie', $cookie);
    }

    /**
     * Returns a cookie-formatted timestamp.
     *
     * @param int $adj Adjust the time by this many seconds before formatting.
     *
     * @return string
     *
     */
    protected function timestamp($adj = 0)
    {
        return \gmdate('D, d M Y H:i:s T', $this->time + $adj);
    }

    /**
     * Returns a Response with added cache limiter headers.
     *
     * @param ResponseInterface $response The HTTP response.
     *
     * @return ResponseInterface
     *
     */
    protected function withCacheLimiter(ResponseInterface $response)
    {
        switch ($this->cacheLimiter) {
            case 'public':
                return $this->cacheLimiterPublic($response);
            case 'private_no_expire':
                return $this->cacheLimiterPrivateNoExpire($response);
            case 'private':
                return $this->cacheLimiterPrivate($response);
            case 'nocache':
                return $this->cacheLimiterNocache($response);
            default:
                return $response;
        }
    }

    /**
     * Returns a Response with 'public' cache limiter headers.
     *
     * @param ResponseInterface $response The HTTP response.
     *
     * @return ResponseInterface
     *
     * @see https://github.com/php/php-src/blob/PHP-5.6.20/ext/session/session.c#L1196-L1213
     *
     */
    protected function cacheLimiterPublic(ResponseInterface $response)
    {
        $maxAge = $this->cacheExpire * 60;
        $expires = $this->timestamp($maxAge);
        $cacheControl = "public, max-age={$maxAge}";
        $lastModified = $this->timestamp();

        return $response
            ->withAddedHeader('Expires', $expires)
            ->withAddedHeader('Cache-Control', $cacheControl)
            ->withAddedHeader('Last-Modified', $lastModified);
    }

    /**
     * Returns a Response with 'private_no_expire' cache limiter headers.
     *
     * @param ResponseInterface $response The HTTP response.
     *
     * @return ResponseInterface
     *
     * @see https://github.com/php/php-src/blob/PHP-5.6.20/ext/session/session.c#L1215-L1224
     *
     */
    protected function cacheLimiterPrivateNoExpire(ResponseInterface $response)
    {
        $maxAge = $this->cacheExpire * 60;
        $cacheControl = "private, max-age={$maxAge}, pre-check={$maxAge}";
        $lastModified = $this->timestamp();

        return $response
            ->withAddedHeader('Cache-Control', $cacheControl)
            ->withAddedHeader('Last-Modified', $lastModified);
    }

    /**
     * Returns a Response with 'private' cache limiter headers.
     *
     * @param ResponseInterface $response The HTTP response.
     *
     * @return ResponseInterface
     *
     * @see https://github.com/php/php-src/blob/PHP-5.6.20/ext/session/session.c#L1226-L1231
     *
     */
    protected function cacheLimiterPrivate(ResponseInterface $response)
    {
        $response = $response->withAddedHeader('Expires', self::EXPIRED);
        return $this->cacheLimiterPrivateNoExpire($response);
    }

    /**
     * Returns a Response with 'nocache' cache limiter headers.
     *
     * @param ResponseInterface $response The HTTP response.
     *
     * @return ResponseInterface
     *
     * @see https://github.com/php/php-src/blob/PHP-5.6.20/ext/session/session.c#L1233-L1243
     *
     */
    protected function cacheLimiterNocache(ResponseInterface $response)
    {
        return $response
            ->withAddedHeader('Expires', self::EXPIRED)
            ->withAddedHeader(
                'Cache-Control',
                'no-store, no-cache, must-revalidate, post-check=0, pre-check=0'
            )
            ->withAddedHeader('Pragma', 'no-cache');
    }
}
