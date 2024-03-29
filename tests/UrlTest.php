<?php

namespace HughCube\PUrl\Tests;

use Exception;
use HughCube\PUrl\Exceptions\ExceptionInterface;
use HughCube\PUrl\Exceptions\InvalidArgumentException;
use HughCube\PUrl\Url;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Throwable;

class UrlTest extends TestCase
{
    public function testInstanceOfPsrUriInterface()
    {
        $url = Url::instance();
        $this->assertInstanceOf(UriInterface::class, $url);
    }

    public function testBadStringUrl()
    {
        $exception = null;

        try {
            Url::instance('php.net');
        } catch (Exception|Throwable $exception) {
        }
        $this->assertInstanceOf(ExceptionInterface::class, $exception);
        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);

        $this->assertNull(Url::parse('php.net'));
    }

    /**
     * @dataProvider dataProviderUrl
     */
    public function testStringUrl($string)
    {
        $url = Url::instance($string);
        $this->assertEquals($string, $url->toString());

        $this->assertInstanceOf(Url::class, Url::parse($url));
        $this->assertSame($url->toString(), Url::parse($url)->toString());
    }

    /**
     * @dataProvider dataProviderUrl
     */
    public function testPsrUrl($string)
    {
        $url = Url::instance($string);
        $url = Url::instance($url);
        $this->assertEquals($string, $url->toString());

        $this->assertInstanceOf(Url::class, Url::parse($url));
        $this->assertSame($url->toString(), Url::parse($url)->toString());
    }

    /**
     * @dataProvider dataProviderUrl
     */
    public function testArrayUrl($string)
    {
        $parts = parse_url($string);
        $url = Url::instance($parts);
        $this->assertEquals($string, $url->toString());

        $this->assertInstanceOf(Url::class, Url::parse($url));
        $this->assertSame($url->toString(), Url::parse($url)->toString());
    }

    /**
     * @dataProvider dataProviderUrl
     */
    public function testGetScheme($string)
    {
        $scheme = parse_url($string, PHP_URL_SCHEME);
        $url = Url::instance($string);
        $this->assertEquals($scheme, $url->getScheme());
    }

    /**
     * @dataProvider dataProviderUrl
     */
    public function testGetter(
        $string,
        $scheme,
        $authority,
        $userInfo,
        $user,
        $pass,
        $host,
        $port,
        $path,
        $query,
        $queryArray,
        $fragment
    ) {
        foreach ([
            Url::instance($string),
            Url::instance(Url::instance($string)),
            Url::instance(parse_url(Url::instance($string)->toString())),
        ] as $url
        ) {
            /** @var Url $url */
            $this->assertEquals($string, $url->toString());
            $this->assertEquals($string, strval($url));
            $this->assertEquals($scheme, $url->getScheme());
            $this->assertEquals($authority, $url->getAuthority());
            $this->assertEquals($userInfo, $url->getUserInfo());
            $this->assertEquals($user, $url->getUser());
            $this->assertEquals($pass, $url->getPass());
            $this->assertEquals($host, $url->getHost());
            $this->assertEquals($port, $url->getPort());
            $this->assertEquals($path, $url->getPath());
            $this->assertEquals($port, $url->getPort());
            $this->assertEquals($query, $url->getQuery());
            $this->assertEquals($queryArray, $url->getQueryArray());
            $this->assertEquals($fragment, $url->getFragment());

            $this->assertInstanceOf(Url::class, $url->withSortQuery());
            $this->assertTrue(is_string($url->getRawQuery()));
        }
    }

    public static function dataProviderUrl(): array
    {
        return [
            [
                'url'        => 'https://www.google.com/search?q=test&oq=test&sourceid=chrome&ie=UTF-8',
                'scheme'     => 'https',
                'authority'  => 'www.google.com',
                'userInfo'   => '',
                'user'       => '',
                'pass'       => '',
                'host'       => 'www.google.com',
                'port'       => 443,
                'path'       => '/search',
                'query'      => 'q=test&oq=test&sourceid=chrome&ie=UTF-8',
                'queryArray' => ['q' => 'test', 'oq' => 'test', 'sourceid' => 'chrome', 'ie' => 'UTF-8'],
                'fragment'   => '',
            ],
            [
                'url'        => 'https://www.google.com/search?q=%E4%BD%A0%E5%A5%BD%E5%91%80&oq=%E4%BD%A0%E5%A5%BD%E5%91%80&aqs=chrome..69i57j0l5.4993j0j7&sourceid=chrome&ie=UTF-8#test',
                'scheme'     => 'https',
                'authority'  => 'www.google.com',
                'userInfo'   => '',
                'user'       => '',
                'pass'       => '',
                'host'       => 'www.google.com',
                'port'       => 443,
                'path'       => '/search',
                'query'      => 'q=%E4%BD%A0%E5%A5%BD%E5%91%80&oq=%E4%BD%A0%E5%A5%BD%E5%91%80&aqs=chrome..69i57j0l5.4993j0j7&sourceid=chrome&ie=UTF-8',
                'queryArray' => [
                    'q'        => '你好呀',
                    'oq'       => '你好呀',
                    'aqs'      => 'chrome..69i57j0l5.4993j0j7',
                    'sourceid' => 'chrome',
                    'ie'       => 'UTF-8',
                ],
                'fragment' => 'test',
            ],
        ];
    }

    public function testMatchHost()
    {
        $url = Url::instance('https://www.google.com/search?q=test&oq=test&sourceid=chrome&ie=UTF-8#test');

        $this->assertFalse($url->matchHost('www.baidu.com'));
        $this->assertFalse($url->matchHost('*baidu.com'));
        $this->assertFalse($url->matchHost('*baidu.com|agoogle.com'));
        $this->assertFalse($url->matchHost('google.com'));

        $this->assertTrue($url->matchHost('*google.com'));
        $this->assertTrue($url->matchHost('*.google.com'));
        $this->assertTrue($url->matchHost('*google*'));
        $this->assertTrue($url->matchHost('*.google.com|*.baidu.com'));
    }

    public function testGetExtension()
    {
        $url = Url::instance('https://www.google.com/search?q=test&oq=test&sourceid=chrome&ie=UTF-8#test');
        $this->assertNull($url->getExtension());

        $url = Url::instance('https://www.google.com/search.php?q=test&oq=test&sourceid=chrome&ie=UTF-8#test');
        $this->assertSame('php', $url->getExtension());

        $url = Url::instance('https://www.google.com/search.php?q=test&oq=test&sourceid=chrome&ie=UTF-8#test');
        $this->assertSame('.php', $url->getExtension('.'));
    }

    public function testGetFilename()
    {
        $url = Url::instance('https://www.google.com/search/?q=test&oq=test&sourceid=chrome&ie=UTF-8#test');
        $this->assertSame('search', $url->getFilename());

        $url = Url::instance('https://www.google.com/search?q=test&oq=test&sourceid=chrome&ie=UTF-8#test');
        $this->assertSame('search', $url->getFilename());

        $url = Url::instance('https://www.google.com/search.php?q=test&oq=test&sourceid=chrome&ie=UTF-8#test');
        $this->assertSame('search', $url->getFilename());

        $url = Url::instance('https://www.google.com/search.inc.php?q=test&oq=test&sourceid=chrome&ie=UTF-8#test');
        $this->assertSame('search.inc', $url->getFilename());
    }

    public function testGetBasename()
    {
        $url = Url::instance('https://www.google.com/search/?q=test&oq=test&sourceid=chrome&ie=UTF-8#test');
        $this->assertSame('search', $url->getBasename());

        $url = Url::instance('https://www.google.com/search?q=test&oq=test&sourceid=chrome&ie=UTF-8#test');
        $this->assertSame('search', $url->getBasename());

        $url = Url::instance('https://www.google.com/search.php?q=test&oq=test&sourceid=chrome&ie=UTF-8#test');
        $this->assertSame('search.php', $url->getBasename());

        $url = Url::instance('https://www.google.com/search.inc.php?q=test&oq=test&sourceid=chrome&ie=UTF-8#test');
        $this->assertSame('search.inc.php', $url->getBasename());
    }
}
