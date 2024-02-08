<?php

namespace HughCube\PUrl;

use HughCube\PUrl\Exceptions\InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Throwable;

class Url implements UriInterface
{
    /**
     * @var int[]
     */
    private $schemes = [
        'http' => 80,
        'https' => 443,
    ];

    private $scheme = null;

    private $host = null;

    private $port = null;

    private $user = null;

    private $pass = null;

    private $path = null;

    private $query = null;

    private $fragment = null;

    /**
     * 获取实例.
     *
     * @param mixed $url
     * @return static
     */
    public static function instance($url = null): Url
    {
        /** @phpstan-ignore-next-line */
        return new static($url);
    }

    /**
     * @param mixed $url
     * @return null|static
     */
    public static function parse($url): ?Url
    {
        try {
            $url = static::instance($url);
            if (static::isUrlString($url->toString())) {
                return $url;
            }
        } catch (Throwable $exception) {
        }

        return null;
    }

    /**
     * @param mixed $url
     */
    protected function __construct($url = null)
    {
        if ($url instanceof UriInterface) {
            $this->parsePsrUrl($url);
        } elseif (is_string($url)) {
            $this->parseStringUrl($url);
        } elseif (is_array($url)) {
            $this->parseArrayUrl($url);
        }
    }

    /**
     * 解析 Psr 标准库的url.
     */
    private function parsePsrUrl(UriInterface $url): void
    {
        $this->scheme = empty($scheme = $url->getScheme()) ? null : $scheme;
        $this->host = empty($host = $url->getHost()) ? null : $host;
        $this->port = empty($port = $url->getPort()) ? null : $port;
        $this->path = empty($path = $url->getPath()) ? null : $path;
        $this->query = empty($query = $url->getQuery()) ? null : $query;
        $this->fragment = empty($fragment = $url->getFragment()) ? null : $fragment;
        $user = $this->getUserInfo();
        $user = explode(':', $user);
        $this->user = (is_array($user) && isset($user[0])) ? $user[0] : null;
        $this->pass = (is_array($user) && isset($user[1])) ? $user[1] : null;
    }

    /**
     * 解析字符串url.
     */
    private function parseStringUrl(string $url): void
    {
        if (!static::isUrlString($url)) {
            throw new InvalidArgumentException('the parameter must be a url');
        }
        /** @var string[] $parts */
        $parts = parse_url($url);
        $this->parseArrayUrl($parts);
    }

    /**
     * 解析数组url.
     */
    private function parseArrayUrl(array $parts): void
    {
        $this->scheme = $parts['scheme'] ?? null;
        $this->host = $parts['host'] ?? null;
        $this->port = $parts['port'] ?? null;
        $this->user = $parts['user'] ?? null;
        $this->pass = $parts['pass'] ?? null;
        $this->path = $parts['path'] ?? null;
        $this->query = $parts['query'] ?? null;
        $this->fragment = $parts['fragment'] ?? null;
    }

    /**
     * 填充 Psr 标准库的url.
     */
    public function fillPsrUri(UriInterface $url): UriInterface
    {
        return $url->withScheme($this->getScheme())
            ->withUserInfo($this->getUser(), $this->getPass())
            ->withHost($this->getHost())
            ->withPort($this->getPort())
            ->withPath($this->getPath())
            ->withQuery($this->getQuery())
            ->withFragment($this->getFragment());
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme(): string
    {
        return strval($this->scheme);
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthority(): string
    {
        $authority = $host = $this->getHost();
        if (empty($host)) {
            return $authority;
        }
        $userInfo = $this->getUserInfo();
        if (!empty($userInfo)) {
            $authority = "$userInfo@$authority";
        }
        $port = $this->getPort();
        if ($this->isNonStandardPort() && !empty($port)) {
            $authority = "$authority:$port";
        }

        return $authority;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserInfo(): string
    {
        $userInfo = $user = $this->getUser();
        if (empty($user)) {
            return $userInfo;
        }
        $pass = $this->getPass();
        if (!empty($pass)) {
            $userInfo = "$userInfo:$pass";
        }

        return $userInfo;
    }

    /**
     * 获取 url user.
     *
     * @return string
     */
    public function getUser(): string
    {
        return strval($this->user);
    }

    /**
     * 获取 url pass.
     *
     * @return string
     */
    public function getPass(): string
    {
        return strval($this->pass);
    }

    /**
     * {@inheritdoc}
     */
    public function getHost(): string
    {
        return strval($this->host);
    }

    /**
     * {@inheritdoc}
     */
    public function getPort(): ?int
    {
        if (!empty($this->port)) {
            return $this->port;
        }
        $scheme = $this->getScheme();

        return $this->schemes[$scheme] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        if (empty($this->path)) {
            return '';
        }

        return '/' === substr($this->path, 0, 1) ? $this->path : "/$this->path";
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(): string
    {
        return strval($this->query);
    }

    /**
     * 获取query数组.
     */
    public function getQueryArray(): array
    {
        $query = $this->getQuery();
        $queryArray = [];
        if (!empty($query)) {
            parse_str($query, $queryArray);
        }

        return empty($queryArray) ? [] : $queryArray;
    }

    /**
     * 是否存在query的key.
     */
    public function hasQueryKey($key): bool
    {
        $queryArray = $this->getQueryArray();

        return array_key_exists($key, $queryArray);
    }

    /**
     * 是否存在query的key.
     */
    public function getQueryValue($key, $default = null)
    {
        $queryArray = $this->getQueryArray();

        return array_key_exists($key, $queryArray) ? $queryArray[$key] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getFragment(): string
    {
        return strval($this->fragment);
    }

    /**
     * Return the string representation as a URI reference.
     */
    public function toString(): string
    {
        $url = '';
        $scheme = $this->getScheme();
        if (!empty($scheme)) {
            $url = "$scheme://$url";
        }
        $authority = $this->getAuthority();
        if (!empty($authority)) {
            $url = "$url$authority";
        }
        $path = $this->getPath();
        if (!empty($path)) {
            $url = "$url$path";
        }
        $query = $this->getQuery();
        if (!empty($query)) {
            $url = "$url?$query";
        }
        $fragment = $this->getFragment();
        if (!empty($fragment)) {
            $url = "$url#$fragment";
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     * @return static
     */
    public function withScheme($scheme): UriInterface
    {
        $new = clone $this;
        $new->scheme = $scheme;

        return $new;
    }

    /**
     * {@inheritdoc}
     * @return static
     */
    public function withUserInfo($user, $password = null): UriInterface
    {
        $new = clone $this;
        $new->user = $user;
        $new->pass = $password;

        return $new;
    }

    /**
     * {@inheritdoc}
     * @return static
     */
    public function withHost($host): UriInterface
    {
        $new = clone $this;
        $new->host = $host;

        return $new;
    }

    /**
     * {@inheritdoc}
     * @return static
     */
    public function withPort($port): UriInterface
    {
        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    /**
     * {@inheritdoc}
     * @return static
     */
    public function withPath($path): UriInterface
    {
        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    /**
     * {@inheritdoc}
     * @return static
     */
    public function withQuery($query): UriInterface
    {
        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    /**
     * Return an instance with the specified query array.
     * @return static
     */
    public function withQueryArray(array $queryArray): Url
    {
        return $this->withQuery(http_build_query($queryArray));
    }

    /**
     * Create a new URI with a specific query string value removed.
     * @return static
     */
    public function withoutQueryValue($key): Url
    {
        $queryArray = $this->getQueryArray();
        if (isset($queryArray[$key])) {
            unset($queryArray[$key]);
        }

        return $this->withQueryArray($queryArray);
    }

    /**
     * Create a new URI with a specific query string value.
     * @return static
     */
    public function withQueryValue($key, $value): Url
    {
        $queryArray = $this->getQueryArray();
        $queryArray[$key] = $value;

        return $this->withQueryArray($queryArray);
    }

    /**
     * {@inheritdoc}
     * @return static
     */
    public function withFragment($fragment): UriInterface
    {
        $new = clone $this;
        $new->fragment = $fragment;

        return $new;
    }

    /**
     * @return static
     */
    public function withSortQuery(int $type = SORT_DESC, int $flags = SORT_REGULAR): Url
    {
        $array = $this->getQueryArray();

        if (SORT_DESC === $type) {
            krsort($array, $flags);
        } else {
            ksort($array, $flags);
        }

        return $this->withQueryArray($array);
    }

    public function getRawQuery(): string
    {
        $items = [];
        foreach ($this->getQueryArray() as $name => $value) {
            $items[] = "$name=$value";
        }

        return implode('&', $items);
    }

    /**
     * Check if host is matched.
     */
    public function matchHost(...$patterns): bool
    {
        if (empty($this->getHost())) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if ($pattern == $this->getHost()) {
                return true;
            }

            $pattern = preg_quote($pattern, '#');
            $pattern = str_replace('\*', '.*', $pattern);
            $pattern = str_replace('\|', '|', $pattern);
            $pattern = '#^(' . $pattern . ')\z#u';

            if (1 == preg_match($pattern, $this->getHost())) {
                return true;
            }
        }

        return false;
    }

    public function isIp(): bool
    {
        return false !== filter_var($this->getHost(), FILTER_VALIDATE_IP);
    }

    public function isLocalhost(): bool
    {
        return in_array($this->getHost(), ['localhost', '127.0.0.1'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Is a given port non-standard for the current scheme?
     */
    private function isNonStandardPort(): bool
    {
        if (!$this->scheme && $this->port) {
            return true;
        }
        if (!$this->host || !$this->port) {
            return false;
        }

        return !isset($this->schemes[$this->scheme])
            || $this->port !== $this->schemes[$this->scheme];
    }

    /**
     * is url string.
     */
    public static function isUrlString($url): bool
    {
        return false !== filter_var($url, FILTER_VALIDATE_URL);
    }

    public function getExtension($prefix = ''): ?string
    {
        $path = $this->getPath();
        if (empty($path)) {
            return null;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (empty($extension)) {
            return null;
        }

        return "$prefix$extension";
    }

    public function getFilename(): ?string
    {
        $path = $this->getPath();
        if (empty($path)) {
            return null;
        }

        $filename = pathinfo($path, PATHINFO_FILENAME);
        if (empty($filename)) {
            return null;
        }

        return $filename;
    }

    public function getBasename(): ?string
    {
        $path = $this->getPath();
        if (empty($path)) {
            return null;
        }

        $basename = pathinfo($path, PATHINFO_BASENAME);
        if (empty($basename)) {
            return null;
        }

        return $basename;
    }
}
