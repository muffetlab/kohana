<?php

use Kohana\Kohana;
use Kohana\Request;
use Kohana\Request\Client\External;
use Kohana\Request\Client\Stream;
use Kohana\Unittest\TestCase;

/**
 * Unit tests for external request client
 *
 * @group kohana
 * @group kohana.request
 * @group kohana.request.client
 * @group kohana.request.client.external
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class Kohana_Request_Client_ExternalTest extends TestCase
{
    /**
     * Provider for test_factory()
     *
     * @return  array
     */
    public function provider_factory(): array
    {
        External::$client = '\Kohana\Request\Client\Stream';

        $return = [
            [[], null, '\Kohana\Request\Client\Stream'],
            [[], '\Kohana\Request\Client\Stream', '\Kohana\Request\Client\Stream'],
        ];

        if (extension_loaded('curl')) {
            $return[] = [[], '\Kohana\Request\Client\Curl', '\Kohana\Request\Client\Curl'];
        }

        if (extension_loaded('http')) {
            $return[] = [[], '\Kohana\Request\Client\HTTP', '\Kohana\Request\Client\HTTP'];
        }

        return $return;
    }

    /**
     * Tests the [Request_Client_External::factory()] method
     *
     * @dataProvider provider_factory
     *
     * @param array $params params
     * @param string|null $client Client
     * @param string $expected Expected
     * @return  void
     * @throws Request_Exception
     */
    public function test_factory(array $params, ?string $client, string $expected)
    {
        $this->assertInstanceOf($expected, External::factory($params, $client));
    }

    /**
     * Data provider for test_options
     *
     * @return  array
     */
    public function provider_options(): array
    {
        return [
            [
                null,
                null,
                []
            ],
            [
                ['foo' => 'bar', 'stfu' => 'snafu'],
                null,
                ['foo' => 'bar', 'stfu' => 'snafu']
            ],
            [
                'foo',
                'bar',
                ['foo' => 'bar']
            ],
            [
                ['foo' => 'bar'],
                'foo',
                ['foo' => 'bar']
            ],
        ];
    }

    /**
     * Tests the [Request_Client_External::options()] method
     *
     * @dataProvider provider_options
     *
     * @param   mixed  $key  key
     * @param   mixed  $value  value
     * @param array $expected Expected
     * @return  void
     */
    public function test_options($key, $value, array $expected)
    {
        // Create a mock external client
        $client = new Stream;

        $client->options($key, $value);
        $this->assertSame($expected, $client->options());
    }

    /**
     * Data provider for test_execute
     *
     * @return  array
     */
    public function provider_execute(): array
    {
        $json = '{"foo": "bar", "snafu": "stfu"}';
        $post = ['foo' => 'bar', 'snafu' => 'stfu'];

        return [
            [
                'application/json',
                $json,
                [],
                [
                    'content-type' => 'application/json',
                    'body' => $json
                ]
            ],
            [
                'application/json',
                $json,
                $post,
                [
                    'content-type' => 'application/x-www-form-urlencoded; charset=' . Kohana::$charset,
                    'body' => http_build_query($post, null, '&')
                ]
            ],
        ];
    }

    /**
     * Tests the [Request_Client_External::_send_message()] method
     *
     * @dataProvider provider_execute
     *
     * @param $content_type
     * @param $body
     * @param $post
     * @param $expected
     * @return  void
     * @throws Kohana_Exception
     * @throws Request_Exception
     * @throws ReflectionException
     */
    public function test_execute($content_type, $body, $post, $expected)
    {
        $old_request = Request::$initial;
        Request::$initial = true;

        // Create a mock Request
        $request = new Request('https://kohana.top/');
        $request->method(\Kohana\HTTP\Request::POST)
            ->headers('content-type', $content_type)
            ->body($body)
            ->post($post);

        $client = $this->getMockForAbstractClass('\Kohana\Request\Client\External', [], '', true, true, true, ['_send_message']);
        $client->expects($this->once())
            ->method('_send_message')
            ->with($request)
            ->will($this->returnValue($this->createMock('\Kohana\Response')));

        $request->client($client);

        $this->assertInstanceOf('\Kohana\Response', $request->execute());
        $this->assertSame($expected['body'], $request->body());
        $this->assertSame($expected['content-type'], (string) $request->headers('content-type'));

        Request::$initial = $old_request;
    }

}
