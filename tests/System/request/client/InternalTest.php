<?php

use Kohana\Foundation\Application;
use Kohana\Foundation\KohanaException;
use Kohana\Http\Client\InternalClient;
use Kohana\Http\Request;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for internal request client
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
#[Group('kohana.core.request')]
#[Group('kohana.core.request.client')]
#[Group('kohana.core.request.client.internal')]
#[AllowMockObjectsWithoutExpectations]
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

    public static function provider_response_failure_status(): array
    {
        return [
            [
                '',
                'Welcome',
                'missing_action',
                'Welcome/missing_action',
                404,
            ],
            [
                'kohana3',
                'missing_controller',
                'index',
                'kohana3/missing_controller/index',
                404,
            ],
            [
                '',
                'Kohana\Routing\TemplateController',
                'missing_action',
                'kohana3/Template/missing_action',
                500,
            ],
        ];
    }

    /**
     * Tests for correct exception messages
     *
     *
     * @return void
     * @throws KohanaException
     */
    #[DataProvider('provider_response_failure_status')]
    #[Test]
    public function test_response_failure_status($directory, $controller, $action, $uri, $expected)
    {
        // PHPUnit cannot double classes with a method named method(), so use a real request
        $request = new Request($uri);
        $request->directory($directory);
        $request->controller($controller);
        $request->action($action);
        $internal_client = new InternalClient();

        $response = $internal_client->execute($request);

        $this->assertSame($expected, $response->status());
    }

}
