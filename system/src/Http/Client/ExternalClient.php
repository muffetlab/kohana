<?php

namespace Kohana\Http\Client;

use Kohana\Foundation\Application;
use Kohana\Foundation\Profiler;
use Kohana\Http\Exceptions\RequestException;
use Kohana\Http\Request;
use Kohana\Http\Response;
use Kohana\Support\Arr;

/**
 * [Request_Client_External] provides a wrapper for all external request
 * processing. This class should be extended by all drivers handling external
 * requests.
 *
 * Supported out of the box:
 *  - Curl (default)
 *  - PECL HTTP
 *  - Streams
 *
 * To select a specific external driver to use as the default driver, set the
 * following property within the Application bootstrap. Alternatively, the
 * client can be injected into the request object.
 *
 * @example
 *
 *       // In application bootstrap
 *       Request_Client_External::$client = 'Request_Client_Stream';
 *
 *       // Add client to request
 *       $request = Request::factory('http://some.host.tld/foo/bar')
 *           ->client(Request_Client_External::factory('Request_Client_HTTP'));
 *
 * @package    Kohana
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 * @uses       [PECL HTTP](https://wiki.php.net/rfc/pecl_http)
 */
abstract class ExternalClient extends Client
{
    /**
     * Use:
     *  - Request_Client_Curl (default)
     *  - Request_Client_HTTP
     *  - Request_Client_Stream
     *
     * @var     string    defines the external client to use by default
     */
    public static $client = CurlClient::class;

    /**
     * Factory method to create a new Request_Client_External object based on
     * the client name passed, or defaulting to Request_Client_External::$client
     * by default.
     *
     * Request_Client_External::$client can be set in the application bootstrap.
     *
     * @param   array   $params parameters to pass to the client
     * @param string|null $client External client to use
     * @return  \Kohana\Http\Client\ExternalClient
     * @throws  \Kohana\Http\Exceptions\RequestException
     */
    public static function factory(array $params = [], ?string $client = null): self
    {
        if ($client === null) {
            $client = static::$client;
        }

        $client = new $client($params);

        if (!$client instanceof self) {
            throw new RequestException('Selected client is not a Request_Client_External object.');
        }

        return $client;
    }

    /**
     * @var     array     curl options
     * @link    https://www.php.net/manual/en/function.curl-setopt
     */
    protected $options = [];

    /**
     * Processes the request, executing the controller action that handles this
     * request, determined by the [Route].
     *
     * 1. Before the controller action is called, the [Controller::before] method
     * will be called.
     * 2. Next the controller action will be called.
     * 3. After the controller action is called, the [Controller::after] method
     * will be called.
     *
     * By default, the output from the controller is captured and returned, and
     * no headers are sent.
     *
     *     $request->execute();
     *
     * @param   Request   $request   A request object
     * @param   Response  $response  A response object
     * @return  Response
     * @uses    [\Kohana\Foundation\Application::$profiling]
     * @uses    [Profiler]
     */
    public function executeRequest(Request $request, Response $response): Response
    {
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

        // Store the current active request and replace current with new request
        $previous = Request::$current;
        Request::$current = $request;

        // Resolve the POST fields
        if ($post = $request->post()) {
            $request->body(http_build_query($post, '', '&'))
                ->headers('content-type', 'application/x-www-form-urlencoded; charset=' . Application::$charset);
        }

        $request->headers('content-length', (string) $request->contentLength());

        // If Kohana expose, set the user-agent
        if (Application::$expose) {
            $request->headers('user-agent', Application::version());
        }

        try {
            $response = $this->sendMessage($request, $response);
        } catch (\Exception $e) {
            // Restore the previous request
            Request::$current = $previous;

            if (isset($benchmark)) {
                // Delete the benchmark, it is invalid
                Profiler::delete($benchmark);
            }

            // Re-throw the exception
            throw $e;
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

    /**
     * Set and get options for this request.
     *
     * @param   mixed    $key    Option name, or array of options
     * @param   mixed    $value  Option value
     * @return  mixed|array|\Kohana\Http\Client\ExternalClient
     */
    public function options($key = null, $value = null)
    {
        if ($key === null) {
            return $this->options;
        }

        if (is_array($key)) {
            $this->options = $key;
        } elseif ($value === null) {
            return Arr::get($this->options, $key);
        } else {
            $this->options[$key] = $value;
        }

        return $this;
    }

    /**
     * Sends the HTTP message [Request] to a remote server and processes
     * the response.
     *
     * @param   Request   $request    Request to send
     * @param   Response  $response   Response to send
     * @return  Response
     */
    abstract protected function sendMessage(Request $request, Response $response): Response;
}
