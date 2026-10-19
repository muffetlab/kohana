<?php

namespace Kohana\Request\Client;

use Kohana\Request\Client as Request_Client;
use Kohana\Request;
use Kohana\Response;
use Kohana\Kohana as Kohana;
use Kohana\Profiler;
use Kohana\HTTP\Exception as HTTP_Exception;
use ReflectionClass;
use Kohana\Exception as Kohana_Exception;
use Exception;

/**
 * Request Client for internal execution
 *
 * @package    Kohana
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 * @since      3.1.0
 */
class Internal extends Request_Client
{
    /**
     * @var    array
     */
    protected $_previous_environment;

    /**
     * Processes the request, executing the controller action that handles this
     * request, determined by the [Route].
     *
     *     $request->execute();
     *
     * @param Request $request
     * @param Response $response
     * @return  Response
     * @throws Kohana_Exception
     * @uses    [Kohana::$profiling]
     * @uses    [Profiler]
     */
    public function execute_request(Request $request, Response $response): Response
    {
        // Directory
        $directory = $request->directory();

        // Controller
        $controller = $request->controller();

        // Build the namespaced controller class name
        $path = '';

        if ($directory) {
            // Convert the directory name to a namespace segment
            $path = implode('\\', array_map('ucfirst', explode('_', str_replace(['\\', '/'], '_', trim($directory, '\\/'))))) . '\\';
        }

        $name = implode('\\', array_map('ucfirst', explode('_', $controller)));

        $controller_class = Kohana::find_class('Controller\\' . $path . $name);

        if (Kohana::$profiling) {
            // Set the benchmark name
            $benchmark = '"' . $request->uri() . '"';

            if ($request !== Request::$initial && Request::$current) {
                // Add the parent request URI
                $benchmark .= ' « "' . Request::$current->uri() . '"';
            }

            // Start benchmarking
            $benchmark = Profiler::start('Requests', $benchmark);
        }

        // Store the currently active request
        $previous = Request::$current;

        // Change the current request to this request
        Request::$current = $request;

        try {
            if (!class_exists($controller_class)) {
                throw HTTP_Exception::factory(404, 'The requested URL :uri was not found on this server.', [':uri' => $request->uri()])->request($request);
            }

            // Load the controller using reflection
            $class = new ReflectionClass($controller_class);

            if ($class->isAbstract()) {
                throw new Kohana_Exception('Cannot create instances of abstract :controller', [':controller' => $controller_class]);
            }

            // Create a new instance of the controller
            $controller = $class->newInstance($request, $response);

            // Run the controller's execute() method
            $response = $class->getMethod('execute')->invoke($controller);

            if (!$response instanceof Response) {
                // Controller failed to return a Response.
                throw new Kohana_Exception('Controller failed to return a Response');
            }
        } catch (HTTP_Exception $e) {
            // Store the request context in the Exception
            if ($e->request() === null) {
                $e->request($request);
            }

            // Get the response via the Exception
            $response = $e->get_response();
        } catch (Exception $e) {
            // Generate an appropriate Response object
            $response = Kohana_Exception::_handler($e);
        }

        // Restore the previous request
        Request::$current = $previous;

        if (isset($benchmark)) {
            // Stop the benchmark
            Profiler::stop($benchmark);
        }

        // Return the response
        return $response;
    }

}
