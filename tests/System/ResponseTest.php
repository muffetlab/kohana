<?php

use Kohana\Cookie\Cookie;
use Kohana\Http\Response;
use Kohana\Testing\TestCase;
use Kohana\View\View;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for response class
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
#[Group('kohana')]
#[Group('kohana.core')]
#[Group('kohana.core.response')]
class Kohana_ResponseTest extends TestCase
{
    /**
     * Provider for test_body
     *
     * @return array
     */
    public static function provider_body(): array
    {
        $view = new class () extends View {
            public function __toString(): string
            {
                return 'foo';
            }
        };

        return [
            ['unit test', 'unit test'],
            [$view, 'foo'],
        ];
    }

    /**
     * Tests that we can set and read a body of a response
     *
     * @return void
     */
    #[DataProvider('provider_body')]
    #[Test]
    public function test_body($source, $expected)
    {
        $response = new Response();
        $response->body($source);
        $this->assertSame($response->body(), $expected);

        $response = (string) $response;
        $this->assertSame($response, $expected);
    }

    /**
     * Provides data for test_body_string_zero()
     *
     * @return array
     */
    public static function provider_body_string_zero(): array
    {
        return [
            ['0', '0'],
            ['0', '0'],
            [0, '0'],
        ];
    }

    /**
     * Test that Response::body() handles numerics correctly
     *
     * @param string $string
     * @param string $expected
     * @return void
     */
    #[DataProvider('provider_body_string_zero')]
    #[Test]
    public function test_body_string_zero($string, $expected)
    {
        $response = new Response();
        $response->body($string);

        $this->assertSame($expected, $response->body());
    }

    /**
     * provider for test_cookie_set()
     *
     * @return array
     */
    public static function provider_cookie_set(): array
    {
        return [
            [
                'test1',
                'foo',
                [
                    'test1' => [
                        'value' => 'foo',
                        'expiration' => Cookie::$expiration,
                    ],
                ],
            ],
            [
                [
                    'test2' => 'stfu',
                    'test3' => ['value' => 'snafu', 'expiration' => 123456789],
                ],
                null,
                [
                    'test2' => [
                        'value' => 'stfu',
                        'expiration' => Cookie::$expiration,
                    ],
                    'test3' => [
                        'value' => 'snafu',
                        'expiration' => 123456789,
                    ],
                ],
            ],
        ];
    }

    /**
     * Tests the Response::cookie() method, ensures
     * correct values are set, including defaults
     *
     * @param string|array $key
     * @param string|null $value
     * @param array|null $expected
     * @return void
     */
    #[DataProvider('provider_cookie_set')]
    #[Test]
    public function test_cookie_set($key, ?string $value, array $expected)
    {
        // Set up the Response and apply cookie
        $response = new Response();
        $response->cookie($key, $value);

        foreach ($expected as $_key => $_value) {
            $cookie = $response->cookie($_key);

            $this->assertSame($_value['value'], $cookie['value']);
            $this->assertSame($_value['expiration'], $cookie['expiration']);
        }
    }

    /**
     * Tests the Response::cookie() get functionality
     *
     * @return void
     */
    public function test_cookie_get()
    {
        $response = new Response();

        // Test for empty cookies
        $this->assertSame([], $response->cookie());

        // Test for no specific cookie
        $this->assertNull($response->cookie('foobar'));

        $response->cookie('foo', 'bar');
        $cookie = $response->cookie('foo');

        $this->assertSame('bar', $cookie['value']);
        $this->assertSame(Cookie::$expiration, $cookie['expiration']);
    }

    /**
     * Test the content type is sent when set
     */
    #[Test]
    public function test_content_type_when_set()
    {
        $content_type = 'application/json';
        $response = new Response();
        $response->headers('content-type', $content_type);
        $headers = $response->sendHeaders()->headers();
        $this->assertSame($content_type, (string) $headers['content-type']);
    }

}
