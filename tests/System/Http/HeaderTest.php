<?php

use Kohana\Foundation\Application;
use Kohana\Foundation\KohanaException;
use Kohana\Http\HeaderBag;
use Kohana\Http\Response;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit Tests for Kohana_HTTP_Header
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @copyright  (c) 2008-2014 Kohana Team
 * @license    https://kohana.top/license
 */
#[Group('kohana')]
#[Group('kohana.core')]
#[Group('kohana.core.http')]
#[Group('kohana.core.http.header')]
class Kohana_HTTP_HeaderTest extends TestCase
{
    /**
     * Provides data for test_accept_quality
     *
     * @return  array
     */
    public static function provider_accept_quality(): array
    {
        return [
            [
                [
                    'text/html; q=1',
                    'text/plain; q=.5',
                    'application/json; q=.1',
                    'text/*',
                ],
                [
                    'text/html' => 1.0,
                    'text/plain' => 0.5,
                    'application/json' => 0.1,
                    'text/*' => 1.0,
                ],
            ],
            [
                [
                    'text/*',
                    'text/html; level=1; q=0.4',
                    'application/xml+rss; q=0.5; level=4',
                ],
                [
                    'text/*' => 1.0,
                    'text/html; level=1' => 0.4,
                    'application/xml+rss; level=4' => 0.5,
                ],
            ],
        ];
    }

    /**
     * Tests the `accept_quality` method parses the quality values
     * correctly out of header parts
     *
     *
     * @param   array  $parts     input
     * @param   array  $expected  expected output
     * @return  void
     */
    #[DataProvider('provider_accept_quality')]
    public function test_accept_quality(array $parts, array $expected)
    {
        $out = HeaderBag::acceptQuality($parts);

        foreach ($out as $value) {
            $this->assertIsFloat($value);
        }

        $this->assertSame($expected, $out);
    }

    /**
     * Data provider for test_parse_accept_header
     *
     * @return  array
     */
    public static function provider_parse_accept_header(): array
    {
        return [
            [
                'text/html, text/plain, text/*, */*',
                [
                    'text' => [
                        'html' => 1.0,
                        'plain' => 1.0,
                        '*' => 1.0,
                    ],
                    '*' => [
                        '*' => 1.0,
                    ],
                ],
            ],
            [
                'text/html; q=.5, application/json, application/xml+rss; level=1; q=.7, text/*, */*',
                [
                    'text' => [
                        'html' => 0.5,
                        '*' => 1.0,
                    ],
                    'application' => [
                        'json' => 1.0,
                        'xml+rss; level=1' => 0.7,
                    ],
                    '*' => [
                        '*' => 1.0,
                    ],
                ],
            ],
        ];
    }

    /**
     * Tests the `parse_accept_header` method parses the Accept: header
     * correctly and returns expected output
     *
     *
     * @param string $accept Accept in
     * @param   array   $expected  expected out
     * @return  void
     */
    #[DataProvider('provider_parse_accept_header')]
    public function test_parse_accept_header(string $accept, array $expected)
    {
        $this->assertSame($expected, HeaderBag::parseAcceptHeader($accept));
    }

    /**
     * Provides data for test_parse_charset_header
     *
     * @return  array
     */
    public static function provider_parse_charset_header(): array
    {
        return [
            [
                'utf-8, utf-10, utf-16, iso-8859-1',
                [
                    'utf-8' => 1.0,
                    'utf-10' => 1.0,
                    'utf-16' => 1.0,
                    'iso-8859-1' => 1.0,
                ],
            ],
            [
                'utf-8, utf-10; q=.9, utf-16; q=.5, iso-8859-1; q=.75',
                [
                    'utf-8' => 1.0,
                    'utf-10' => 0.9,
                    'utf-16' => 0.5,
                    'iso-8859-1' => 0.75,
                ],
            ],
            [
                null,
                [
                    '*' => 1.0,
                ],
            ],
        ];
    }

    /**
     * Tests the `parse_charset_header` method parsed the Accept-Charset header
     * correctly
     *
     *
     * @param string|null $accept Accept
     * @param array $expected Expected
     * @return  void
     */
    #[DataProvider('provider_parse_charset_header')]
    public function test_parse_charset_header(?string $accept, array $expected)
    {
        $this->assertSame($expected, HeaderBag::parseCharsetHeader($accept));
    }

    /**
     * Provides data for test_parse_charset_header
     *
     * @return  array
     */
    public static function provider_parse_encoding_header(): array
    {
        return [
            [
                'compress, gzip, blowfish',
                [
                    'compress' => 1.0,
                    'gzip' => 1.0,
                    'blowfish' => 1.0,
                ],
            ],
            [
                'compress, gzip; q=0.12345, blowfish; q=1.0',
                [
                    'compress' => 1.0,
                    'gzip' => 0.12345,
                    'blowfish' => 1.0,
                ],
            ],
            [
                null,
                [
                    '*' => 1.0,
                ],
            ],
            [
                '',
                [
                    'identity' => 1.0,
                ],
            ],
        ];
    }

    /**
     * Tests the `parse_encoding_header` method parses the Accept-Encoding header
     * correctly
     *
     *
     * @param string|null $accept Accept
     * @param array $expected Expected
     * @return  void
     */
    #[DataProvider('provider_parse_encoding_header')]
    public function test_parse_encoding_header(?string $accept, array $expected)
    {
        $this->assertSame($expected, HeaderBag::parseEncodingHeader($accept));
    }

    /**
     * Provides data for test_parse_charset_header
     *
     * @return  array
     */
    public static function provider_parse_language_header(): array
    {
        return [
            [
                'en, en-us, en-gb, fr, fr-fr, es-es',
                [
                    'en' => [
                        '*' => 1.0,
                        'us' => 1.0,
                        'gb' => 1.0,
                    ],
                    'fr' => [
                        '*' => 1.0,
                        'fr' => 1.0,
                    ],
                    'es' => [
                        'es' => 1.0,
                    ],
                ],
            ],
            [
                'en; q=.9, en-us, en-gb, fr; q=.5, fr-fr; q=0.4, es-es; q=0.9, en-gb-gb; q=.45',
                [
                    'en' => [
                        '*' => 0.9,
                        'us' => 1.0,
                        'gb' => 1.0,
                        'gb-gb' => 0.45,
                    ],
                    'fr' => [
                        '*' => 0.5,
                        'fr' => 0.4,
                    ],
                    'es' => [
                        'es' => 0.9,
                    ],
                ],
            ],
            [
                null,
                [
                    '*' => [
                        '*' => 1.0,
                    ],
                ],
            ],
        ];
    }

    /**
     * Tests the `parse_language_header` method parses the Accept-Language header
     * correctly
     *
     *
     * @param string|null $accept Accept
     * @param array $expected Expected
     * @return  void
     */
    #[DataProvider('provider_parse_language_header')]
    public function test_parse_language_header(?string $accept, array $expected)
    {
        $this->assertSame($expected, HeaderBag::parseLanguageHeader($accept));
    }

    /**
     * Data provider for test_create_cache_control
     *
     * @return  array
     */
    public static function provider_create_cache_control(): array
    {
        return [
            [
                [
                    'public',
                    'max-age' => 1800,
                    'must-revalidate',
                    's-max-age' => 3600,
                ],
                'public, max-age=1800, must-revalidate, s-max-age=3600',
            ],
            [
                [
                    'max-age' => 1800,
                    's-max-age' => 1800,
                    'public',
                    'must-revalidate',
                ],
                'max-age=1800, s-max-age=1800, public, must-revalidate',
            ],
            [
                [
                    'private',
                    'no-cache',
                    'max-age' => 0,
                    'must-revalidate',
                ],
                'private, no-cache, max-age=0, must-revalidate',
            ],
        ];
    }

    /**
     * Tests that `create_cache_control()` outputs the correct cache control
     * string from the supplied input
     *
     *
     * @param   array   $input     input
     * @param string $expected Expected
     * @return  void
     */
    #[DataProvider('provider_create_cache_control')]
    public function test_create_cache_control(array $input, string $expected)
    {
        $this->assertSame($expected, HeaderBag::createCacheControl($input));
    }

    /**
     * Data provider for parse_cache_control
     *
     * @return  array
     */
    public static function provider_parse_cache_control(): array
    {
        return [
            [
                'public, max-age=1800, must-revalidate, s-max-age=3600',
                [
                    'public',
                    'max-age' => 1800,
                    'must-revalidate',
                    's-max-age' => 3600,
                ],
            ],
            [
                'max-age=1800, s-max-age=1800, public, must-revalidate',
                [
                    'max-age' => 1800,
                    's-max-age' => 1800,
                    'public',
                    'must-revalidate',
                ],
            ],
            [
                'private, no-cache, max-age=0, must-revalidate',
                [
                    'private',
                    'no-cache',
                    'max-age' => 0,
                    'must-revalidate',
                ],
            ],
        ];
    }

    /**
     * Tests that `parse_cache_control()` outputs the correct cache control
     * parsed data from the input string
     *
     *
     * @param string $input Input
     * @param   array   $expected  expected
     * @return  void
     */
    #[DataProvider('provider_parse_cache_control')]
    public function test_parse_cache_control(string $input, array $expected)
    {
        $parsed = HeaderBag::parseCacheControl($input);

        $this->assertIsArray($parsed);

        foreach ($expected as $key => $value) {
            if (is_int($key)) {
                $this->assertTrue(in_array($value, $parsed));
            } else {
                $this->assertArrayHasKey($key, $parsed);
                $this->assertSame($value, $parsed[$key]);
            }
        }
    }

    /**
     * Data provider for test_offsetSet
     *
     * @return  array
     */
    // @codingStandardsIgnoreStart
    public static function provider_offsetSet(): array
    // @codingStandardsIgnoreEnd
    {
        return [
            [
                [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'text/html, text/plain; q=.1, */*',
                    'Accept-Language' => 'en-gb, en-us, en; q=.1',
                ],
                [
                    ['Accept-Encoding', 'compress, gzip', false],
                ],
                [
                    'content-type' => 'application/x-www-form-urlencoded',
                    'accept' => 'text/html, text/plain; q=.1, */*',
                    'accept-language' => 'en-gb, en-us, en; q=.1',
                    'accept-encoding' => 'compress, gzip',
                ],
            ],
            [
                [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'text/html, text/plain; q=.1, */*',
                    'Accept-Language' => 'en-gb, en-us, en; q=.1',
                ],
                [
                    ['Accept-Encoding', 'compress, gzip', false],
                    ['Accept-Encoding', 'bzip', false],
                ],
                [
                    'content-type' => 'application/x-www-form-urlencoded',
                    'accept' => 'text/html, text/plain; q=.1, */*',
                    'accept-language' => 'en-gb, en-us, en; q=.1',
                    'accept-encoding' => ['compress, gzip', 'bzip'],
                ],
            ],
            [
                [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'text/html, text/plain; q=.1, */*',
                    'Accept-Language' => 'en-gb, en-us, en; q=.1',
                ],
                [
                    ['Accept-Encoding', 'compress, gzip', false],
                    ['Accept-Encoding', 'bzip', true],
                    ['Accept', 'text/*', false],
                ],
                [
                    'content-type' => 'application/x-www-form-urlencoded',
                    'accept' => ['text/html, text/plain; q=.1, */*', 'text/*'],
                    'accept-language' => 'en-gb, en-us, en; q=.1',
                    'accept-encoding' => 'bzip',
                ],
            ],
        ];
    }

    /**
     * Ensures that offsetSet normalizes the array keys
     *
     *
     * @param array $constructor constructor
     * @param array $to_set to_set
     * @param array $expected expected
     * @return  void
     * @throws ReflectionException
     */
    // @codingStandardsIgnoreStart
    #[DataProvider('provider_offsetSet')]
    public function test_offsetSet(array $constructor, array $to_set, array $expected)
    // @codingStandardsIgnoreEnd
    {
        $http_header = new HeaderBag($constructor);

        $reflection = new ReflectionClass($http_header);
        $method = $reflection->getMethod('offsetSet');

        foreach ($to_set as $args) {
            $method->invokeArgs($http_header, $args);
        }

        $this->assertSame($expected, $http_header->getArrayCopy());
    }

    /**
     * Data provider for test_offsetGet
     *
     * @return  array
     */
    // @codingStandardsIgnoreStart
    public static function provider_offsetGet(): array
    // @codingStandardsIgnoreEnd
    {
        return [
            [
                [
                    'FoO' => 'bar',
                    'START' => 'end',
                    'true' => true,
                ],
                'FOO',
                'bar',
            ],
            [
                [
                    'FoO' => 'bar',
                    'START' => 'end',
                    'true' => true,
                ],
                'true',
                true,
            ],
            [
                [
                    'FoO' => 'bar',
                    'START' => 'end',
                    'true' => true,
                ],
                'True',
                true,
            ],
            [
                [
                    'FoO' => 'bar',
                    'START' => 'end',
                    'true' => true,
                ],
                'Start',
                'end',
            ],
            [
                [
                    'content-type' => 'bar',
                    'Content-Type' => 'end',
                    'Accept' => '*/*',
                ],
                'content-type',
                'end',
            ],
        ];
    }

    /**
     * Ensures that offsetGet normalizes the array keys
     *
     *
     * @param array $state start state
     * @param string $key key to retrieve
     * @param mixed $expected expected
     * @return  void
     */
    // @codingStandardsIgnoreStart
    #[DataProvider('provider_offsetGet')]
    public function test_offsetGet(array $state, string $key, $expected)
    // @codingStandardsIgnoreEnd
    {
        $header = new HeaderBag($state);

        $this->assertSame($expected, $header->offsetGet($key));
    }

    /**
     * Data provider for test_offsetExists
     *
     * @return  array
     */
    // @codingStandardsIgnoreStart
    public static function provider_offsetExists(): array
    // @codingStandardsIgnoreEnd
    {
        return [
            [
                [
                    'Accept' => 'text/html, application/json',
                    'Accept-Language' => 'en, en-GB',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'Content-Type',
                true,
            ],
            [
                [
                    'Accept' => 'text/html, application/json',
                    'Accept-Language' => 'en, en-GB',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'CONTENT-TYPE',
                true,
            ],
            [
                [
                    'Accept' => 'text/html, application/json',
                    'Accept-Language' => 'en, en-GB',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'accept-language',
                true,
            ],
            [
                [
                    'Accept' => 'text/html, application/json',
                    'Accept-Language' => 'en, en-GB',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'x-powered-by',
                false,
            ],
        ];
    }

    /**
     * Ensures that offsetExists normalizes the array key
     *
     *
     * @param   array    $state     state
     * @param string $key Key
     * @param bool $expected Expected
     * @return  void
     */
    // @codingStandardsIgnoreStart
    #[DataProvider('provider_offsetExists')]
    public function test_offsetExists(array $state, string $key, bool $expected)
    // @codingStandardsIgnoreEnd
    {
        $header = new HeaderBag($state);

        $this->assertSame($expected, $header->offsetExists($key));
    }

    /**
     * Data provider for test_offsetUnset
     *
     * @return  array
     */
    // @codingStandardsIgnoreStart
    public static function provider_offsetUnset(): array
    // @codingStandardsIgnoreEnd
    {
        return [
            [
                [
                    'Accept' => 'text/html, application/json',
                    'Accept-Language' => 'en, en-GB',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'Accept-Language',
                [
                    'accept' => 'text/html, application/json',
                    'content-type' => 'application/x-www-form-urlencoded',
                ],
            ],
            [
                [
                    'Accept' => 'text/html, application/json',
                    'Accept-Language' => 'en, en-GB',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'ACCEPT',
                [
                    'accept-language' => 'en, en-GB',
                    'content-type' => 'application/x-www-form-urlencoded',
                ],
            ],
            [
                [
                    'Accept' => 'text/html, application/json',
                    'Accept-Language' => 'en, en-GB',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'content-type',
                [
                    'accept' => 'text/html, application/json',
                    'accept-language' => 'en, en-GB',
                ],
            ],
        ];
    }

    /**
     * Tests that `offsetUnset` normalizes the key names properly
     *
     *
     * @param   array   $state     state
     * @param string $remove Remove
     * @param   array   $expected  expected
     * @return  void
     */
    // @codingStandardsIgnoreStart
    #[DataProvider('provider_offsetUnset')]
    public function test_offsetUnset(array $state, string $remove, array $expected)
    // @codingStandardsIgnoreEnd
    {
        $header = new HeaderBag($state);
        $header->offsetUnset($remove);

        $this->assertSame($expected, $header->getArrayCopy());
    }

    /**
     * Provides data for test_parse_header_string
     *
     * @return  array
     */
    public static function provider_parse_header_string(): array
    {
        return [
            [
                [
                    "Content-Type: application/x-www-form-urlencoded\r\n",
                    "Accept: text/html, text/plain; q=.5, application/json, */* \r\n",
                    "X-Powered-By: Kohana Baby     \r\n",
                ],
                [
                    'content-type' => 'application/x-www-form-urlencoded',
                    'accept' => 'text/html, text/plain; q=.5, application/json, */* ',
                    'x-powered-by' => 'Kohana Baby     ',
                ],
            ],
            [
                [
                    "Content-Type: application/x-www-form-urlencoded\r\n",
                    "Accept: text/html, text/plain; q=.5, application/json, */* \r\n",
                    "X-Powered-By: Kohana Baby     \r\n",
                    "Content-Type: application/json\r\n",
                ],
                [
                    'content-type' => [
                        'application/x-www-form-urlencoded',
                        'application/json',
                    ],
                    'accept' => 'text/html, text/plain; q=.5, application/json, */* ',
                    'x-powered-by' => 'Kohana Baby     ',
                ],
            ],
        ];
    }

    /**
     * Tests that `parse_header_string` performs as expected
     *
     *
     * @param array $headers headers
     * @param array $expected expected
     * @return  void
     */
    #[DataProvider('provider_parse_header_string')]
    public function test_parse_header_string(array $headers, array $expected)
    {
        $http_header = new HeaderBag([]);

        foreach ($headers as $header) {

            $this->assertEquals(strlen($header), $http_header->parseHeaderString(null, $header));
        }

        $this->assertSame($expected, $http_header->getArrayCopy());
    }

    /**
     * Data Provider for test_accepts_at_quality
     *
     * @return  array
     */
    public static function provider_accepts_at_quality(): array
    {
        return [
            [
                [
                    'Accept' => 'application/json, text/html; q=.5, text/*; q=.1, */*',
                ],
                'application/json',
                false,
                1.0,
            ],
            [
                [
                    'Accept' => 'application/json, text/html; q=.5, text/*; q=.1, */*',
                ],
                'text/html',
                false,
                0.5,
            ],
            [
                [
                    'Accept' => 'application/json, text/html; q=.5, text/*; q=.1, */*',
                ],
                'text/plain',
                false,
                0.1,
            ],
            [
                [
                    'Accept' => 'application/json, text/html; q=.5, text/*; q=.1, */*',
                ],
                'text/plain',
                true,
                false,
            ],
            [
                [
                    'Accept' => 'application/json, text/html; q=.5, text/*; q=.1, */*',
                ],
                'application/xml',
                false,
                1.0,
            ],
            [
                [
                    'Accept' => 'application/json, text/html; q=.5, text/*; q=.1, */*',
                ],
                'application/xml',
                true,
                false,
            ],
            [
                [],
                'application/xml',
                false,
                1.0,
            ],
            [
                [],
                'application/xml',
                true,
                false,
            ],
        ];
    }

    /**
     * Tests `accepts_at_quality` parsed the Accept: header as expected
     *
     *
     * @param array $state starting state
     * @param string $accept Accept header to test
     * @param bool $explicit explicitly check
     * @param mixed $expected expected output
     * @return  void
     * @throws KohanaException
     */
    #[DataProvider('provider_accepts_at_quality')]
    public function test_accepts_at_quality(array $state, string $accept, bool $explicit, $expected)
    {
        $header = new HeaderBag($state);

        $this->assertSame($expected, $header->acceptsAtQuality($accept, $explicit));
    }

    /**
     * Data provider for test_preferred_accept
     *
     * @return  array
     */
    public static function provider_preferred_accept(): array
    {
        return [
            [
                [
                    'Accept' => 'application/json, text/html; q=.5, text/*; q=.1, */*',
                ],
                ['text/html', 'application/json', 'text/plain'],
                false,
                'application/json',
            ],
            [
                [
                    'Accept' => 'application/json, text/html; q=.5, text/*; q=.1, */*',
                ],
                ['text/plain', 'application/xml', 'image/jpeg'],
                false,
                'application/xml',
            ],
            [
                [
                    'Accept' => 'application/json, text/html; q=.5, text/*; q=.1',
                ],
                ['text/plain', 'application/xml', 'image/jpeg'],
                false,
                'text/plain',
            ],
            [
                [
                    'Accept' => 'application/json, text/html; q=.5, text/*; q=.1, */*',
                ],
                ['text/plain', 'application/xml', 'image/jpeg'],
                true,
                false,
            ],
        ];
    }

    /**
     * Tests `preferred_accept` returns the correct preferred type
     *
     *
     * @param array $state state
     * @param array $accepts accepts
     * @param bool $explicit Explicit
     * @param string|false $expected Expected
     * @return  void
     * @throws KohanaException
     */
    #[DataProvider('provider_preferred_accept')]
    public function test_preferred_accept(array $state, array $accepts, bool $explicit, $expected)
    {
        $header = new HeaderBag($state);

        $this->assertSame($expected, $header->preferredAccept($accepts, $explicit));
    }

    /**
     * Data provider for test_accepts_charset_at_quality
     *
     * @return  array
     */
    public static function provider_accepts_charset_at_quality(): array
    {
        return [
            [
                [
                    'Accept-Charset' => 'utf-8, utf-10, utf-16, iso-8859-1',
                ],
                'utf-8',
                1.0,
            ],
            [
                [
                    'Accept-Charset' => 'utf-8, utf-10, utf-16, iso-8859-1',
                ],
                'utf-16',
                1.0,
            ],
            [
                [
                    'Accept-Charset' => 'utf-8; q=.1, utf-10, utf-16; q=.2, iso-8859-1',
                ],
                'utf-8',
                0.1,
            ],
            [
                [
                    'Accept-Charset' => 'utf-8; q=.1, utf-10, utf-16; q=.2, iso-8859-1; q=.5',
                ],
                'iso-8859-1',
                0.5,
            ],
        ];
    }

    /**
     * Tests `accepts_charset_at_quality` works as expected, returning the correct
     * quality value
     *
     *
     * @param array $state state
     * @param string $charset charset
     * @param float $expected Expected
     * @return  void
     */
    #[DataProvider('provider_accepts_charset_at_quality')]
    public function test_accepts_charset_at_quality(array $state, string $charset, float $expected)
    {
        $header = new HeaderBag($state);

        $this->assertSame($expected, $header->acceptsCharsetAtQuality($charset));
    }

    /**
     * Data provider for test_preferred_charset
     *
     * @return  array
     */
    public static function provider_preferred_charset(): array
    {
        return [
            [
                ['Accept-Charset' => 'utf-8, utf-10, utf-16, iso-8859-1'],
                ['utf-8', 'utf-16'],
                'utf-8',
            ],
            [
                ['Accept-Charset' => 'utf-8, utf-10, utf-16, iso-8859-1'],
                ['UTF-10'],
                'UTF-10',
            ],
        ];
    }

    /**
     * Tests `preferred_charset` works as expected, returning the correct charset
     * from the list supplied
     *
     *
     * @param array $state state
     * @param array $charsets charsets
     * @param string $expected expected
     * @return  void
     */
    #[DataProvider('provider_preferred_charset')]
    public function test_preferred_charset(array $state, array $charsets, string $expected)
    {
        $header = new HeaderBag($state);

        $this->assertSame($expected, $header->preferredCharset($charsets));
    }

    /**
     * Data provider for test_accepts_encoding_at_quality
     *
     * @return  array
     */
    public static function provider_accepts_encoding_at_quality(): array
    {
        return [
            [
                [
                    'accept-encoding' => 'compress, gzip, blowfish; q=.7, *; q=.5',
                ],
                'gzip',
                false,
                1.0,
            ],
            [
                [
                    'accept-encoding' => 'compress, gzip, blowfish; q=.7, *; q=.5',
                ],
                'gzip',
                true,
                1.0,
            ],
            [
                [
                    'accept-encoding' => 'compress, gzip, blowfish; q=.7, *; q=.5',
                ],
                'blowfish',
                false,
                0.7,
            ],
            [
                [
                    'accept-encoding' => 'compress, gzip, blowfish; q=.7, *; q=.5',
                ],
                'bzip',
                false,
                0.5,
            ],
            [
                [
                    'accept-encoding' => 'compress, gzip, blowfish; q=.7, *; q=.5',
                ],
                'bzip',
                true,
                0.0,
            ],
        ];
    }

    /**
     * Tests `accepts_encoding_at_quality` parses and returns the correct
     * quality value for Accept-Encoding headers
     *
     *
     * @param array $state state
     * @param string $encoding encoding
     * @param bool $explicit explicit
     * @param float $expected expected
     * @return  void
     */
    #[DataProvider('provider_accepts_encoding_at_quality')]
    public function test_accepts_encoding_at_quality(array $state, string $encoding, bool $explicit, float $expected)
    {
        $header = new HeaderBag($state);
        $this->assertSame($expected, $header->acceptsEncodingAtQuality($encoding, $explicit));
    }

    /**
     * Data provider for test_preferred_encoding
     *
     * @return  array
     */
    public static function provider_preferred_encoding(): array
    {
        return [
            [
                [
                    'accept-encoding' => 'compress, gzip, blowfish; q=.7, *; q=.5',
                ],
                ['gzip', 'blowfish', 'bzip'],
                false,
                'gzip',
            ],
            [
                [
                    'accept-encoding' => 'compress, gzip, blowfish; q=.7, *; q=.5',
                ],
                ['bzip', 'ROT-13'],
                false,
                'bzip',
            ],
            [
                [
                    'accept-encoding' => 'compress, gzip, blowfish; q=.7, *; q=.5',
                ],
                ['bzip', 'ROT-13'],
                true,
                false,
            ],
            [
                [
                    'accept-encoding' => 'compress, gzip, blowfish; q=.2, *; q=.5',
                ],
                ['ROT-13', 'blowfish'],
                false,
                'ROT-13',
            ],
        ];
    }

    /**
     * Tests that `preferred_encoding` parses and returns the correct
     * encoding type
     *
     *
     * @param array $state state in
     * @param array $encodings encodings to interrogate
     * @param bool $explicit explicit check
     * @param string|false $expected Expected output
     * @return  void
     */
    #[DataProvider('provider_preferred_encoding')]
    public function test_preferred_encoding(array $state, array $encodings, bool $explicit, $expected)
    {
        $header = new HeaderBag($state);
        $this->assertSame($expected, $header->preferredEncoding($encodings, $explicit));
    }

    /**
     * Data provider for test_accepts_language_at_quality
     *
     * @return  array
     */
    public static function provider_accepts_language_at_quality(): array
    {
        return [
            [
                [
                    'accept-language' => 'en-us; q=.9, en-gb; q=.7, en; q=.5, fr-fr; q=.9, fr; q=.8',
                ],
                'en',
                false,
                0.5,
            ],
            [
                [
                    'accept-language' => 'en-us; q=.9, en-gb; q=.7, en; q=.5, fr-fr; q=.9, fr; q=.8',
                ],
                'en-gb',
                false,
                0.7,
            ],
            [
                [
                    'accept-language' => 'en-us; q=.9, en-gb; q=.7, en; q=.5, fr-fr; q=.9, fr; q=.8',
                ],
                'en',
                true,
                0.5,
            ],
            [
                [
                    'accept-language' => 'en-us; q=.9, en-gb; q=.7, en; q=.5, fr-fr; q=.9, fr; q=.8',
                ],
                'fr-ni',
                false,
                0.8,
            ],
            [
                [
                    'accept-language' => 'en-us; q=.9, en-gb; q=.7, en; q=.5, fr-fr; q=.9, fr; q=.8',
                ],
                'fr-ni',
                true,
                0.0,
            ],
            [
                [
                    'accept-language' => 'en-US',
                ],
                'en-us',
                true,
                1.0,
            ],
        ];
    }

    /**
     * Tests `accepts_language_at_quality` parses the Accept-Language header
     * correctly and identifies the correct quality supplied, explicit or not
     *
     *
     * @param array $state state in
     * @param string $language language to interrogate
     * @param bool $explicit explicit check
     * @param float $expected expected output
     * @return  void
     */
    #[DataProvider('provider_accepts_language_at_quality')]
    public function test_accepts_language_at_quality(array $state, string $language, bool $explicit, float $expected)
    {
        $header = new HeaderBag($state);
        $this->assertSame($expected, $header->acceptsLanguageAtQuality($language, $explicit));
    }

    /**
     * Data provider for test_preferred_language
     *
     * @return  array
     */
    public static function provider_preferred_language(): array
    {
        return [
            [
                [
                    'accept-language' => 'en-us; q=.9, en-gb; q=.7, en; q=.5, fr-fr; q=.9, fr; q=.8',
                ],
                ['en', 'fr', 'en-gb'],
                false,
                'fr',
            ],
            [
                [
                    'accept-language' => 'en-us; q=.9, en-gb; q=.7, en; q=.5, fr-fr; q=.9, fr; q=.8',
                ],
                ['en', 'fr', 'en-gb'],
                true,
                'fr',
            ],
            [
                [
                    'accept-language' => 'en-us; q=.9, en-gb; q=.7, en; q=.5, fr-fr; q=.9, fr; q=.8',
                ],
                ['en-au', 'fr-ni', 'fr'],
                false,
                'fr-ni',
            ],
            [
                [
                    'accept-language' => 'en-us; q=.9, en-gb; q=.7, en; q=.5, fr-fr; q=.9, fr; q=.8',
                ],
                ['en-au', 'fr-ni', 'fr'],
                true,
                'fr',
            ],
            [
                [
                    'accept-language' => 'en-US',
                ],
                ['en-us'],
                true,
                'en-us',
            ],
        ];
    }

    /**
     * Tests that `preferred_language` correctly identifies the right
     * language based on the Accept-Language header and `$explicit` setting
     *
     *
     * @param array $state state in
     * @param array $languages languages to interrogate
     * @param bool $explicit explicit check
     * @param string $expected expected output
     * @return  void
     */
    #[DataProvider('provider_preferred_language')]
    public function test_preferred_language(array $state, array $languages, bool $explicit, string $expected)
    {
        $header = new HeaderBag($state);
        $this->assertSame($expected, $header->preferredLanguage($languages, $explicit));
    }

    /**
     * Data provider for test_send_headers
     *
     * @return  array
     */
    public static function provider_send_headers(): array
    {
        $content_type = Application::$contentType . '; charset=' . Application::$charset;

        return [
            [
                [],
                [
                    'HTTP/1.1 200 OK',
                    'Content-Type: ' . $content_type,
                ],
                false,
            ],
            [
                [],
                [
                    'HTTP/1.1 200 OK',
                    'Content-Type: ' . $content_type,
                    'X-Powered-By: ' . Application::version(),
                ],
                true,
            ],
            [
                [
                    'accept' => 'text/html, text/plain, text/*, */*',
                    'accept-charset' => 'utf-8, utf-10, iso-8859-1',
                    'accept-encoding' => 'compress, gzip',
                    'accept-language' => 'en, en-gb, en-us',
                ],
                [
                    'HTTP/1.1 200 OK',
                    'Accept: text/html, text/plain, text/*, */*',
                    'Accept-Charset: utf-8, utf-10, iso-8859-1',
                    'Accept-Encoding: compress, gzip',
                    'Accept-Language: en, en-gb, en-us',
                    'Content-Type: ' . $content_type,
                ],
                false,
            ],
            [
                [
                    'accept' => 'text/html, text/plain, text/*, */*',
                    'accept-charset' => 'utf-8, utf-10, iso-8859-1',
                    'accept-encoding' => 'compress, gzip',
                    'accept-language' => 'en, en-gb, en-us',
                    'content-type' => 'application/json',
                    'x-powered-by' => 'Mohana',
                    'x-ssl-enabled' => 'TRUE',
                ],
                [
                    'HTTP/1.1 200 OK',
                    'Accept: text/html, text/plain, text/*, */*',
                    'Accept-Charset: utf-8, utf-10, iso-8859-1',
                    'Accept-Encoding: compress, gzip',
                    'Accept-Language: en, en-gb, en-us',
                    'Content-Type: application/json',
                    'X-Powered-By: Mohana',
                    'X-Ssl-Enabled: TRUE',
                ],
                true,
            ],
        ];
    }

    /**
     * Tests that send headers processes the headers sent to PHP correctly
     *
     *
     * @param array $state state in
     * @param array $expected expected out
     * @return  void
     * @throws KohanaException
     */
    #[DataProvider('provider_send_headers')]
    public function test_send_headers(array $state, array $expected, $expose)
    {
        Application::$expose = $expose;

        $response = new Response();
        $response->headers($state);

        $this->assertSame($expected, $response->sendHeaders(false, function ($response, $headers) {
            return $headers;
        }));
    }
}
