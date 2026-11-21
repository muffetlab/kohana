<?php

namespace Kohana\Http\Client;

use Kohana\Foundation\Application;
use Kohana\Foundation\KohanaException;
use Kohana\Foundation\Profiler;
use Kohana\Http\Exceptions\HttpException;
use Kohana\Http\Request;
use Kohana\Http\Response;

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
class InternalClient extends Client
{
    /**
     * @var    array
     */
    protected $previousEnvironment;

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
    public function executeRequest(Request $request, Response $response): Response
    {
        // Directory
        $directory = $request->directory();

        // Controller
        $controller = $request->controller();

        if (str_contains((string) $controller, '\\')) {
            // Fully qualified controller class
            $prefix = '';
        } else {
            // App\Http\Controllers\[Directory\]NameController
            $prefix = 'App\\Http\\Controllers\\';

            if ($directory) {
                $prefix .= implode('\\', array_map('ucfirst', preg_split('#[\\\\/_]#', trim($directory, '/')))) . '\\';
            }

            $controller = ucfirst((string) $controller) . 'Controller';
        }

        if (Application::$profiling) {
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
            if (!class_exists($prefix . $controller)) {
                throw HttpException::factory(404, 'The requested URL :uri was not found on this server.', [':uri' => $request->uri()])->request($request);
            }

            // Load the controller using reflection
            $class = new \ReflectionClass($prefix . $controller);

            if ($class->isAbstract()) {
                throw new KohanaException('Cannot create instances of abstract :controller', [':controller' => $prefix . $controller]);
            }

            // Create a new instance of the controller
            $controller = $class->newInstance($request, $response);

            // Run the controller's execute() method
            $response = $class->getMethod('execute')->invoke($controller);

            if (!$response instanceof Response) {
                // Controller failed to return a Response.
                throw new KohanaException('Controller failed to return a Response');
            }
        } catch (HttpException $e) {
            // Store the request context in the Exception
            if ($e->request() === null) {
                $e->request($request);
            }

            // Get the response via the Exception
            $response = $e->getResponse();
        } catch (\Exception $e) {
            // Generate an appropriate Response object
            $response = KohanaException::render($e);
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
