<?php

declare(strict_types=1);

namespace Async\Http;

use Async\Http\CookieInterface;

/**
 * Class Cookie
 *
 * @package Async\Http
 */
class Cookie implements CookieInterface
{
    const EXPIRY_FORMAT = 'l, d-M-Y H:i:s T';

    /**
     * @var string|null
     */
    protected $domain;

    /**
     * @var \DateTimeInterface|null
     */
    protected $expiry;

    /**
     * @var bool
     */
    protected $httpOnly = false;

    /**
     * @var int
     */
    protected $maxAge = 0;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $path;

    /**
     * @var bool
     */
    protected $secure = false;

    /**
     * @var string|null
     */
    protected $value;

    /**
     * @var bool
     */
    protected $strict;

    /**
     * Cookie constructor.
     * @param string $name
     * @param string|null $value
     */
    public function __construct($name, ?string $value = null)
    {
        $this->name  = $name;
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpiry()
    {
        return $this->expiry;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxAge()
    {
        return $this->maxAge;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function isHttpOnly()
    {
        return $this->httpOnly;
    }

    /**
     * {@inheritdoc}
     */
    public function isSecure()
    {
        return $this->secure;
    }

    /**
     * {@inheritdoc}
     */
    public function withDomain($domain)
    {
        $clone = clone $this;
        $clone->domain = $domain;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withExpiry($expiry)
    {
        if (null !== $expiry) {
            self::assertCookieExpiry($expiry);
        }
        $clone = clone $this;
        $clone->expiry = self::normalizeCookieExpiry($expiry);
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withHttpOnly($flag)
    {
        $clone = clone $this;
        $clone->httpOnly = (bool) $flag;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withMaxAge($age)
    {
        $clone = clone $this;
        $clone->maxAge = (int) $age;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withName($name)
    {
        $clone = clone $this;
        $clone->name = $name;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withPath($path)
    {
        $clone = clone $this;
        $clone->path = $path;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withSecure($flag)
    {
        $clone = clone $this;
        $clone->secure = (bool) $flag;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withValue($value)
    {
        $clone = clone $this;
        $clone->value = $value;
        return $clone;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $params = array($this->name . '=' . \urlencode($this->value));
        if ($this->domain) {
            $params[] = "Domain={$this->domain}";
        }
        if ($this->expiry) {
            $params[] = "Expires=" . $this->expiry->format(self::EXPIRY_FORMAT);
        }
        if ($this->httpOnly) {
            $params[] = 'HttpOnly';
        }
        if ($this->maxAge > 0) {
            $params[] = "Max-Age={$this->maxAge}";
        }
        if ($this->path) {
            $params[] = "Path={$this->path}";
        }
        if ($this->secure) {
            $params[] = 'Secure';
        }
        return \implode('; ', $params);
    }

    /**
     * @param mixed $value
     */
    public static function assertCookieExpiry($value)
    {
        if (!($value instanceof \DateTime) && !\is_string($value) && !\is_int($value)) {
            throw new \InvalidArgumentException(
                "Cookie expiry must be string, int or an instance of \\DateTime; '%s' given",
                \is_object($value) ? \get_class($value) : \gettype($value)
            );
        }
    }

    /**
     * @param \DateTimeInterface|string|int|null $value
     * @return string
     */
    public static function normalizeCookieExpiry($value)
    {
        if (\is_string($value)) {
            $value = \DateTime::createFromFormat(Cookie::EXPIRY_FORMAT, $value);
        } elseif (\is_int($value)) {
            $value = \DateTime::createFromFormat('U', $value);
        }
        if ($value instanceof \DateTime) {
            return $value;
        }
    }

    /**
     * Create Cookie using `$name` and `$value` pair.
     */
    public static function make(string $name, string $value = ''): Cookie
    {
        return new self($name, $value);
    }

    /**
     * @param string $header
     * @return CookieInterface
     */
    public static function create($header)
    {
        $parts = \preg_split('~\\s*[;]\\s*~', $header);
        list($name, $value) = \explode('=', \array_shift($parts), 2);
        $cookie = new self($name);
        if (\is_string($value)) {
            $cookie = $cookie->withValue(\urldecode($value));
        }
        while ($nvp = \array_shift($parts)) {
            $nvp = \explode('=', $nvp, 2);
            $value = \count($nvp) === 2 ? $nvp[1] : null;
            switch (\strtolower($nvp[0])) {
                case 'domain':
                    $cookie = $cookie->withDomain($value);
                    break;
                case 'expires':
                    $cookie = $cookie->withExpiry($value);
                    break;
                case 'httponly':
                    $cookie = $cookie->withHttpOnly(true);
                    break;
                case 'max-age':
                    $cookie = $cookie->withMaxAge($value);
                    break;
                case 'path':
                    $cookie = $cookie->withPath($value);
                    break;
                case 'secure':
                    $cookie = $cookie->withSecure(true);
                    break;
            }
        }
        return $cookie;
    }

    /**
     * Parse a cookie header according to RFC 6265.
     *
     * PHP will replace special characters in cookie names, which results in other cookies not being available due to
     * overwriting. Thus, the server request should take the cookies from the request header instead.
     *
     * @param string $cookieHeader A string cookie header value.
     * @return array key/value cookie pairs.
     */
    public static function parseCookieHeader($cookieHeader): array
    {
        if (\is_array($cookieHeader)) {
            $cookieHeader = isset($cookieHeader[0]) ? $cookieHeader[0] : '';
        }

        if (!\is_string($cookieHeader)) {
            throw new \InvalidArgumentException('Cannot parse Cookie data. Header value must be a string.');
        }

        $cookieHeader = \rtrim($cookieHeader, "\r\n");
        \preg_match_all('(
            (?:^\\n?[ \t]*|;[ ])
            (?P<name>[!#$%&\'*+-.0-9A-Z^_`a-z|~]+)
            =
            (?P<DQUOTE>"?)
                (?P<value>[\x21\x23-\x2b\x2d-\x3a\x3c-\x5b\x5d-\x7e]*)
            (?P=DQUOTE)
            (?=\\n?[ \t]*$|;[ ])
        )x', $cookieHeader, $matches, \PREG_SET_ORDER);

        $cookies = [];
        foreach ($matches as $match) {
            $cookies[$match['name']] = \urldecode($match['value']);
        }

        return $cookies;
    }

    /**
     * Create a list of Cookies from a Cookie header value string.
     */
    public static function listFromString(string $string): array
    {
        $cookies = self::splitOnDelimiter($string);

        return \array_map(function ($cookiePair) {
            return self::oneFromPair($cookiePair);
        }, $cookies);
    }

    /**
     * Create one Cookie from a cookie key/value header value string.
     */
    public static function oneFromPair(string $string): Cookie
    {
        list($cookieName, $cookieValue) = self::splitPair($string);

        $cookie = new self($cookieName);

        if ($cookieValue !== null) {
            $cookie = $cookie->withValue($cookieValue);
        }

        return $cookie;
    }

    public static function splitOnDelimiter(string $string): array
    {
        $splitAttributes = \preg_split('@\s*[;]\s*@', $string);

        \assert(\is_array($splitAttributes));

        return \array_filter($splitAttributes);
    }

    public static function splitPair(string $string): array
    {
        $pairParts = \explode('=', $string, 2);
        $pairParts[1] = $pairParts[1] ?? '';

        return \array_map('urldecode', $pairParts);
    }
}
