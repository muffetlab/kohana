<?php

use Kohana\Foundation\Application;
use Kohana\Foundation\KohanaException;
use Kohana\Http\Client\CurlClient;
use Kohana\Http\Client\ExternalClient;
use Kohana\Http\Client\HttpClient;
use Kohana\Http\Client\StreamClient;
use Kohana\Http\Exceptions\RequestException;
use Kohana\Http\Request;
use Kohana\Http\Response;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for external request client
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
#[Group('kohana')]
#[Group('kohana.request')]
#[Group('kohana.request.client')]
#[Group('kohana.request.client.external')]
#[AllowMockObjectsWithoutExpectations]
class Kohana_Request_Client_ExternalTest extends TestCase
{
    /**
     * Provider for test_factory()
     *
     * @return  array
     */
    public static function provider_factory(): array
    {
        ExternalClient::$client = StreamClient::class;

        $return = [
            [[], null, StreamClient::class],
            [[], StreamClient::class, StreamClient::class],
        ];

        if (extension_loaded('curl')) {
            $return[] = [[], CurlClient::class, CurlClient::class];
        }

        if (extension_loaded('http')) {
            $return[] = [[], HttpClient::class, HttpClient::class];
        }

        return $return;
    }

    /**
     * Tests the [Request_Client_External::factory()] method
     *
     *
     * @param array $params params
     * @param string|null $client Client
     * @param string $expected Expected
     * @return  void
     * @throws RequestException
     */
    #[DataProvider('provider_factory')]
    public function test_factory(array $params, ?string $client, string $expected)
    {
        $this->assertInstanceOf($expected, ExternalClient::factory($params, $client));
    }

    /**
     * Data provider for test_options
     *
     * @return  array
     */
    public static function provider_options(): array
    {
        return [
            [
                null,
                null,
                [],
            ],
            [
                ['foo' => 'bar', 'stfu' => 'snafu'],
                null,
                ['foo' => 'bar', 'stfu' => 'snafu'],
            ],
            [
                'foo',
                'bar',
                ['foo' => 'bar'],
            ],
            [
                ['foo' => 'bar'],
                'foo',
                ['foo' => 'bar'],
            ],
        ];
    }

    /**
     * Tests the [Request_Client_External::options()] method
     *
     *
     * @param   mixed  $key  key
     * @param   mixed  $value  value
     * @param array $expected Expected
     * @return  void
     */
    #[DataProvider('provider_options')]
    public function test_options($key, $value, array $expected)
    {
        // Create a mock external client
        $client = new StreamClient();

        $client->options($key, $value);
        $this->assertSame($expected, $client->options());
    }

    /**
     * Data provider for test_execute
     *
     * @return  array
     */
    public static function provider_execute(): array
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
                    'body' => $json,
                ],
            ],
            [
                'application/json',
                $json,
                $post,
                [
                    'content-type' => 'application/x-www-form-urlencoded; charset=' . Application::$charset,
                    'body' => http_build_query($post, '', '&'),
                ],
            ],
        ];
    }

    /**
     * Tests the [Request_Client_External::_send_message()] method
     *
     *
     * @param $content_type
     * @param $body
     * @param $post
     * @param $expected
     * @return  void
     * @throws KohanaException
     * @throws RequestException
     * @throws ReflectionException
     */
    #[DataProvider('provider_execute')]
    public function test_execute($content_type, $body, $post, $expected)
    {
        $old_request = Request::$initial;
        Request::$initial = true;

        // Create a mock Request
        $request = new Request('https://kohana.top/');
        $request->method(\Kohana\Http\Contracts\Request::POST)
            ->headers('content-type', $content_type)
            ->body($body)
            ->post($post);

        $client = $this->mockAbstract(ExternalClient::class, [], ['sendMessage']);
        $client->expects($this->once())
            ->method('sendMessage')
            ->with($request)
            ->willReturn($this->createMock(Response::class));

        $request->client($client);

        $this->assertInstanceOf(Response::class, $request->execute());
        $this->assertSame($expected['body'], $request->body());
        $this->assertSame($expected['content-type'], (string) $request->headers('content-type'));

        Request::$initial = $old_request;
    }

}
