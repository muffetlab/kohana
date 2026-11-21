<?php

use Kohana\Http\Request;
use Kohana\Http\Response;
use Kohana\Testing\TestCase;
use Kohana\Foundation\Application;
use Kohana\Http\Client\InternalClient;
use Kohana\Foundation\KohanaException;

/**
 * Unit tests for internal request client
 *
 * @group kohana
 * @group kohana.core
 * @group kohana.core.request
 * @group kohana.core.request.client
 * @group kohana.core.request.client.internal
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class Kohana_Request_Client_InternalTest extends TestCase
{
    protected $_log_object;

    // @codingStandardsIgnoreStart
    public function setUp(): void
    // @codingStandardsIgnoreEnd
    {
        parent::setUp();

        // temporarily save $log object
        $this->_log_object = Application::$log;
        Application::$log = null;
    }

    // @codingStandardsIgnoreStart
    public function tearDown(): void
    // @codingStandardsIgnoreEnd
    {
        // re-assign log object
        Application::$log = $this->_log_object;

        parent::tearDown();
    }

    public function provider_response_failure_status(): array
    {
        return [
            [
                '',
                'Welcome',
                'missing_action',
                'Welcome/missing_action',
                404
            ],
            [
                'kohana3',
                'missing_controller',
                'index',
                'kohana3/missing_controller/index',
                404
            ],
            [
                '',
                'Kohana\Routing\TemplateController',
                'missing_action',
                'kohana3/Template/missing_action',
                500
            ],
        ];
    }

    /**
     * Tests for correct exception messages
     *
     * @test
     * @dataProvider provider_response_failure_status
     *
     * @return void
     * @throws KohanaException
     */
    public function test_response_failure_status($directory, $controller, $action, $uri, $expected)
    {
        // Mock for request object
        $request = $this->getMockBuilder(Request::class)
            ->setMethods(['directory', 'controller', 'action', 'uri', 'execute', 'method'])
            ->setConstructorArgs([$uri])
            ->getMock();

        $request->expects($this->any())
            ->method('directory')
            ->will($this->returnValue($directory));

        $request->expects($this->any())
            ->method('controller')
            ->will($this->returnValue($controller));

        $request->expects($this->any())
            ->method('action')
            ->will($this->returnValue($action));

        $request->expects($this->any())
            ->method('uri')
            ->will($this->returnValue($uri));

        $request->expects($this->any())
            ->method('execute')
            ->will($this->returnValue($this->createMock(Response::class)));

        // mock `method` method to avoid fatal errors in newer versions of PHPUnit
        $request->expects($this->any())
            ->method('method')
            ->withAnyParameters();

        $internal_client = new InternalClient;

        $response = $internal_client->execute($request);

        $this->assertSame($expected, $response->status());
    }

}
