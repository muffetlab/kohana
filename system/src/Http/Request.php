<?php

namespace Kohana\Http;

use Kohana\Cookie\Cookie;
use Kohana\Foundation\KohanaException;
use Kohana\Foundation\Application;
use Kohana\Support\Str;
use Kohana\Support\Number;
use Kohana\Support\Arr;
use Kohana\Routing\Route;
use Kohana\Http\Client\InternalClient;
use Kohana\Http\Client\ExternalClient;
use Kohana\Routing\Url;
use Kohana\Http\Client\Client;
use Kohana\Http\Exceptions\HttpException;
use Kohana\Http\Exceptions\RequestException;

/**
 * Request. Uses the [Route] class to determine what
 * [Controller] to send the request to.
 *
 * @package    Kohana
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class Request implements \Kohana\Http\Contracts\Request
{
    /**
     * @var  string  client user agent
     */
    public static $userAgent = '';

    /**
     * @var  string  client IP address
     */
    public static $clientIp = '0.0.0.0';

    /**
     * @var  string  trusted proxy server IPs
     */
    public static $trustedProxies = ['127.0.0.1', 'localhost', 'localhost.localdomain'];

    /**
     * @var Request|null Main request instance
     */
    public static $initial;

    /**
     * @var  Request  currently executing request instance
     */
    public static $current;

    /**
     * Creates a new request object for the given URI. New requests should be
     * Created using the [Request::factory] method.
     *
     *     $request = Request::factory($uri);
     *
     * If $cache parameter is set, the response for the request will attempt to
     * be retrieved from the cache.
     *
     * @param string|null $uri URI of the request
     * @param array $client_params An array of params to pass to the request client
     * @param bool $allow_external Allow external requests? (deprecated in 3.3)
     * @param array $injected_routes An array of routes to use, for testing
     * @return Request
     * @throws Kohana_Exception
     * @throws Request_Exception
     * @uses    Route::all
     * @uses    Route::matches
     */
    public static function factory(?string $uri = null, array $client_params = [], bool $allow_external = true, array $injected_routes = []): self
    {
        // If this is the initial request
        if (!static::$initial) {
            $protocol = Http::$protocol;

            // Use the server request method, or default to GET.
            $method = $_SERVER['REQUEST_METHOD'] ?? \Kohana\Http\Contracts\Request::GET;

            if (
                !empty($_SERVER['HTTPS']) && filter_var($_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN)
                ||
                isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
                && in_array($_SERVER['REMOTE_ADDR'], static::$trustedProxies)
            ) {
                // This request is secure
                $secure = true;
            }

            if (isset($_SERVER['HTTP_REFERER'])) {
                // There is a referrer for this request
                $referrer = $_SERVER['HTTP_REFERER'];
            }

            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                // Browser type
                static::$userAgent = $_SERVER['HTTP_USER_AGENT'];
            }

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                // Typically used to denote AJAX requests
                $requested_with = $_SERVER['HTTP_X_REQUESTED_WITH'];
            }

            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
                && isset($_SERVER['REMOTE_ADDR'])
                && in_array($_SERVER['REMOTE_ADDR'], static::$trustedProxies)) {
                // Use the forwarded IP address, typically set when the
                // client is using a proxy server.
                // Format: "X-Forwarded-For: client1, proxy1, proxy2"
                $client_ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

                static::$clientIp = array_shift($client_ips);

                unset($client_ips);
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])
                && isset($_SERVER['REMOTE_ADDR'])
                && in_array($_SERVER['REMOTE_ADDR'], static::$trustedProxies)) {
                // Use the forwarded IP address, typically set when the
                // client is using a proxy server.
                $client_ips = explode(',', $_SERVER['HTTP_CLIENT_IP']);

                static::$clientIp = array_shift($client_ips);

                unset($client_ips);
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                // The remote IP address
                static::$clientIp = $_SERVER['REMOTE_ADDR'];
            }

            if ($method !== \Kohana\Http\Contracts\Request::GET) {
                // Ensure the raw body is saved for future use
                $body = file_get_contents('php://input');
            }

            if ($uri === null) {
                // Attempt to guess the proper URI
                $uri = static::detectUri();
            }

            $cookies = [];

            if ($cookie_keys = array_keys($_COOKIE)) {
                foreach ($cookie_keys as $key) {
                    $cookies[$key] = Cookie::get($key);
                }
            }

            // Create the instance singleton
            static::$initial = $request = new static($uri, $client_params, $allow_external, $injected_routes);

            // Store global GET and POST data in the initial request only
            $request->protocol($protocol)
                ->query($_GET)
                ->post($_POST);

            if (isset($secure)) {
                // Set the request security
                $request->secure($secure);
            }

            // Set the request method
            $request->method($method);

            if (isset($referrer)) {
                // Set the referrer
                $request->referrer($referrer);
            }

            if (isset($requested_with)) {
                // Apply the requested with variable
                $request->requestedWith($requested_with);
            }

            if (isset($body)) {
                // Set the request body (probably a PUT type)
                $request->body($body);
            }

            if (isset($cookies)) {
                $request->cookie($cookies);
            }
        } else {
            $request = new static($uri, $client_params, $allow_external, $injected_routes);
        }

        return $request;
    }

    /**
     * Automatically detects the URI of the main request using PATH_INFO,
     * REQUEST_URI, PHP_SELF or REDIRECT_URL.
     *
     *     $uri = Request::detect_uri();
     *
     * @return  string  URI of the main request
     * @throws  Kohana_Exception
     * @since   3.0.8
     */
    public static function detectUri(): string
    {
        if (!empty($_SERVER['PATH_INFO'])) {
            // PATH_INFO does not contain the docroot or index
            $uri = $_SERVER['PATH_INFO'];
        } else {
            // REQUEST_URI and PHP_SELF include the docroot and index

            if (isset($_SERVER['REQUEST_URI'])) {
                /**
                 * We use REQUEST_URI as the fallback value. The reason
                 * for this is we might have a malformed URL such as:
                 *
                 *  http://localhost/http://example.com/judge.php
                 *
                 * which parse_url can't handle. So rather than leave
                 * empty-handed, we'll use this.
                 */
                $uri = $_SERVER['REQUEST_URI'];

                if ($request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) {
                    // Valid URL path found, set it.
                    $uri = $request_uri;
                }

                // Decode the request URI
                $uri = rawurldecode($uri);
            } elseif (isset($_SERVER['PHP_SELF'])) {
                $uri = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['REDIRECT_URL'])) {
                $uri = $_SERVER['REDIRECT_URL'];
            } else {
                // If you ever see this error, please report an issue at http://dev.kohanaphp.com/projects/kohana3/issues
                // along with any relevant information about your web server setup. Thanks!
                throw new KohanaException('Unable to detect the URI using PATH_INFO, REQUEST_URI, PHP_SELF or REDIRECT_URL');
            }

            // Get the path from the base URL, including the index file
            $base_url = parse_url(Application::$baseUrl, PHP_URL_PATH);

            if (strpos($uri, $base_url) === 0) {
                // Remove the base URL from the URI
                $uri = (string) substr($uri, strlen($base_url));
            }

            if (Application::$indexFile && strpos($uri, Application::$indexFile) === 0) {
                // Remove the index file from the URI
                $uri = (string) substr($uri, strlen(Application::$indexFile));
            }
        }

        return $uri;
    }

    /**
     * Return the currently executing request. This is changed to the current
     * request when [Request::execute] is called and restored when the request
     * is completed.
     *
     *     $request = Request::current();
     *
     * @return  Request
     * @since   3.0.5
     */
    public static function current(): self
    {
        return static::$current;
    }

    /**
     * Returns the first request encountered by this framework. This will should
     * only be set once during the first [Request::factory] invocation.
     *
     *     // Get the first request
     *     $request = Request::initial();
     *
     *     // Test whether the current request is the first request
     *     if (Request::initial() === Request::current())
     *          // Do something useful
     *
     * @return  Request|null
     * @since   3.1.0
     */
    public static function initial(): ?self
    {
        return static::$initial;
    }

    /**
     * Returns information about the initial user agent.
     *
     * @param mixed $value array or string to return: browser, version, robot, mobile, platform
     * @return  mixed   requested information, false if nothing is found
     * @throws Kohana_Exception
     * @uses    Text::user_agent
     * @uses    Request::$user_agent
     */
    public static function userAgent($value)
    {
        return Str::userAgent(static::$userAgent, $value);
    }

    /**
     * Determines if a file larger than the post_max_size has been uploaded. PHP
     * does not handle this situation gracefully on its own, so this method
     * helps to solve that problem.
     *
     * @return bool
     * @throws Kohana_Exception
     * @uses    Arr::get
     * @uses    Num::bytes
     */
    public static function postMaxSizeExceeded(): bool
    {
        // Make sure the request method is POST
        if (static::$initial->method() !== \Kohana\Http\Contracts\Request::POST)
            return false;

        // Get the post_max_size in bytes
        $max_bytes = Number::bytes(ini_get('post_max_size'));

        // Error occurred if method is POST, and content length is too long
        return Arr::get($_SERVER, 'CONTENT_LENGTH') > $max_bytes;
    }

    /**
     * Process a request to find a matching route
     *
     * @param Request $request Request
     * @param array|null $routes Route
     * @return  array
     */
    public static function process(self $request, ?array $routes = null): ?array
    {
        // Load routes
        $routes = empty($routes) ? Route::all() : $routes;

        foreach ($routes as $route) {
            // Use external routes for reverse routing only
            if ($route->isExternal()) {
                continue;
            }

            // We found something suitable
            if ($params = $route->matches($request)) {
                return [
                    'params' => $params,
                    'route' => $route,
                ];
            }
        }

        return null;
    }

    /**
     * Parses an Accept header and returns an array (type => quality) of the
     * accepted types, ordered by quality.
     *
     *     $accept = Request::_parse_accept($header, $defaults);
     *
     * @param string $header Header to parse
     * @param array|null $accepts Default values
     * @return  array
     */
    protected static function parseAccept(string &$header, ?array $accepts = null): array
    {
        if (!empty($header)) {
            // Get all the types
            $types = explode(',', $header);

            foreach ($types as $type) {
                // Split the type into parts
                $parts = explode(';', $type);

                // Make the type only the MIME
                $type = trim(array_shift($parts));

                // Default quality is 1.0
                $quality = 1.0;

                foreach ($parts as $part) {
                    // Prevent undefined $value notice below
                    if (strpos($part, '=') === false)
                        continue;

                    // Separate the key and value
                    list ($key, $value) = explode('=', trim($part));

                    if ($key === 'q') {
                        // There is a quality for this type
                        $quality = (float) trim($value);
                    }
                }

                // Add the accept type and quality
                $accepts[$type] = $quality;
            }
        }

        // Make sure that accepts is an array
        $accepts = (array) $accepts;

        // Order by quality
        arsort($accepts);

        return $accepts;
    }

    /**
     * @var  string  the x-requested-with header which most likely
     *               will be xmlhttprequest
     */
    protected $requestedWith;

    /**
     * @var  string  method: GET, POST, PUT, DELETE, HEAD, etc.
     */
    protected $method = 'GET';

    /**
     * @var  string  protocol: HTTP/1.1, FTP, CLI, etc.
     */
    protected $protocol;

    /**
     * @var bool
     */
    protected $secure = false;

    /**
     * @var  string  referring URL
     */
    protected $referrer;

    /**
     * @var  Route       route matched for this request
     */
    protected $route;

    /**
     * @var  Route       array of routes to manually look at instead of the global namespace
     */
    protected $routes;

    /**
     * @var  Kohana_HTTP_Header  headers to sent as part of the request
     */
    protected $header;

    /**
     * @var  string the body
     */
    protected $body;

    /**
     * @var  string  controller directory
     */
    protected $directory = '';

    /**
     * @var  string  controller to be executed
     */
    protected $controller;

    /**
     * @var  string  action to be executed in the controller
     */
    protected $action;

    /**
     * @var  string  the URI of the request
     */
    protected $uri;

    /**
     * @var bool external request
     */
    protected $external = false;

    /**
     * @var  array   parameters from the route
     */
    protected $params = [];

    /**
     * @var array    query parameters
     */
    protected $get = [];

    /**
     * @var array    post parameters
     */
    protected $post = [];

    /**
     * @var array    cookies to send with the request
     */
    protected $cookies = [];

    /**
     * @var Kohana_Request_Client
     */
    protected $client;

    /**
     * Creates a new request object for the given URI. New requests should be
     * Created using the [Request::factory] method.
     *
     *     $request = new Request($uri);
     *
     * If $cache parameter is set, the response for the request will attempt to
     * be retrieved from the cache.
     *
     * @param string|null $uri URI of the request
     * @param array $client_params Array of params to pass to the request client
     * @param bool $allow_external Allow external requests? (deprecated in 3.3)
     * @param array $injected_routes An array of routes to use, for testing
     * @throws Request_Exception
     * @uses    Route::all
     * @uses    Route::matches
     */
    public function __construct(?string $uri = null, array $client_params = [], bool $allow_external = true, array $injected_routes = [])
    {
        $uri = $uri ?? '';

        // Initialise the header
        $this->header = new HeaderBag([]);

        // Assign injected routes
        $this->routes = $injected_routes;

        // Cleanse query parameters from URI (faster that parse_url())
        $split_uri = explode('?', $uri);
        $uri = array_shift($split_uri);

        if ($split_uri) {
            parse_str($split_uri[0], $this->get);
        }

        // Detect protocol (if present)
        // $allow_external = false prevents the default index.php from
        // being able to proxy external pages.
        if (!$allow_external || strpos($uri, '://') === false) {
            // Remove leading and trailing slashes from the URI
            $this->uri = trim($uri, '/');

            // Apply the client
            $this->client = new InternalClient($client_params);
        } else {
            // Create a route
            $this->route = new Route($uri);

            // Store the URI
            $this->uri = $uri;

            // Set the security setting if required
            if (strpos($uri, 'https://') === 0) {
                $this->secure(true);
            }

            // Set external state
            $this->external = true;

            // Set up the client
            $this->client = ExternalClient::factory($client_params);
        }
    }

    /**
     * Returns the response as the string representation of a request.
     *
     *     echo $request;
     *
     * @return  string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Sets and gets the URI from the request.
     *
     * @param string|null $uri
     * @return string|HTTP_Request
     */
    public function uri(?string $uri = null)
    {
        if ($uri === null) {
            // Act as a getter
            return $this->uri === '' ? '/' : $this->uri;
        }

        // Act as a setter
        $this->uri = $uri;

        return $this;
    }

    /**
     * Create a URL string from the current request. This is a shortcut for:
     *
     *     echo URL::site($this->request->uri(), $protocol);
     *
     * @param mixed $protocol protocol string or Request object
     * @return  string
     * @throws Kohana_Exception
     * @uses    URL::site
     * @since   3.0.7
     */
    public function url($protocol = null): string
    {
        if ($this->isExternal()) {
            // If it's an external request return the URI
            return $this->uri();
        }

        // Create a URI with the current route, convert to a URL and returns
        return Url::site($this->uri(), $protocol);
    }

    /**
     * Retrieves a value from the route parameters.
     *
     *     $id = $request->param('id');
     *
     * @param string|null $key Key of the value
     * @param   mixed    $default  Default value if the key is not set
     * @return  mixed
     */
    public function param(?string $key = null, $default = null)
    {
        if ($key === null) {
            // Return the full array
            return $this->params;
        }

        return $this->params[$key] ?? $default;
    }

    /**
     * Sets and gets the referrer from the request.
     *
     * @param string|null $referrer
     * @return  Kohana_Request|string
     */
    public function referrer(?string $referrer = null)
    {
        if ($referrer === null) {
            // Act as a getter
            return $this->referrer;
        }

        // Act as a setter
        $this->referrer = $referrer;

        return $this;
    }

    /**
     * Sets and gets the route from the request.
     *
     * @param Route|null $route
     * @return Kohana_Request|Route
     */
    public function route(?Route $route = null)
    {
        if ($route === null) {
            // Act as a getter
            return $this->route;
        }

        // Act as a setter
        $this->route = $route;

        return $this;
    }

    /**
     * Sets and gets the directory for the controller.
     *
     * @param string|null $directory Directory to execute the controller from
     * @return  Kohana_Request|string
     */
    public function directory(?string $directory = null)
    {
        if ($directory === null) {
            // Act as a getter
            return $this->directory;
        }

        // Act as a setter
        $this->directory = $directory;

        return $this;
    }

    /**
     * Sets and gets the controller for the matched route.
     *
     * @param string|null $controller Controller to execute the action
     * @return  Kohana_Request|string
     */
    public function controller(?string $controller = null)
    {
        if ($controller === null) {
            // Act as a getter
            return $this->controller;
        }

        // Act as a setter
        $this->controller = $controller;

        return $this;
    }

    /**
     * Sets and gets the action for the controller.
     *
     * @param string|null $action Action to execute the controller from
     * @return  Kohana_Request|string
     */
    public function action(?string $action = null)
    {
        if ($action === null) {
            // Act as a getter
            return $this->action;
        }

        // Act as a setter
        $this->action = $action;

        return $this;
    }

    /**
     * Provides access to the [Request_Client].
     *
     * @return Kohana_Request|Request_Client
     */
    public function client(?Client $client = null)
    {
        if ($client === null)
            return $this->client;
        else {
            $this->client = $client;
            return $this;
        }
    }

    /**
     * Gets and sets the requested with property, which should
     * be relative to the x-requested-with pseudo header.
     *
     * @param string|null $requested_with Requested with value
     * @return  Kohana_Request|string
     */
    public function requestedWith(?string $requested_with = null)
    {
        if ($requested_with === null) {
            // Act as a getter
            return $this->requestedWith;
        }

        // Act as a setter
        $this->requestedWith = strtolower($requested_with);

        return $this;
    }

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
     * @return  Response
     * @throws Kohana_Exception
     * @throws Request_Exception
     * @uses    [Kohana::$profiling]
     * @uses    [Profiler]
     */
    public function execute(): Response
    {
        if (!$this->external) {
            $processed = static::process($this, $this->routes);

            if ($processed) {
                // Store the matching route
                $this->route = $processed['route'];
                $params = $processed['params'];

                // Is this route external?
                $this->external = $this->route->isExternal();

                if (isset($params['directory'])) {
                    // Controllers are in a subdirectory
                    $this->directory = $params['directory'];
                }

                // Store the controller
                $this->controller = $params['controller'];

                // Store the action
                $this->action = $params['action'] ?? Route::$defaultAction;

                // These are accessible as public vars and can be overloaded
                unset($params['controller'], $params['action'], $params['directory']);

                // Params cannot be changed once matched
                $this->params = $params;
            }
        }

        if (!$this->route instanceof Route) {
            return HttpException::factory(404, 'Unable to find a route to match the URI: :uri', [':uri' => $this->uri])
                    ->request($this)
                    ->getResponse();
        }

        if (!$this->client instanceof Client) {
            throw new RequestException('Unable to execute :uri without a Kohana_Request_Client', [':uri' => $this->uri]);
        }

        return $this->client->execute($this);
    }

    /**
     * Returns whether this request is the initial request Kohana received.
     * Can be used to test for sub requests.
     *
     *     if (!$request->is_initial())
     *         // This is a sub request
     *
     * @return bool
     */
    public function isInitial(): bool
    {
        return $this === static::$initial;
    }

    /**
     * Readonly access to the [Request::$_external] property.
     *
     *     if (!$request->is_external())
     *          // This is an internal request
     *
     * @return bool
     */
    public function isExternal(): bool
    {
        return $this->external;
    }

    /**
     * Returns whether this is an ajax request (as used by JS frameworks)
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->requestedWith() === 'xmlhttprequest';
    }

    /**
     * Gets or sets the HTTP method. Usually GET, POST, PUT or DELETE in
     * traditional CRUD applications.
     *
     * @param string|null $method Method to use for this request
     * @return  Kohana_Request|string
     */
    public function method(?string $method = null)
    {
        if ($method === null) {
            // Act as a getter
            return $this->method;
        }

        // Act as a setter
        $this->method = strtoupper($method);

        return $this;
    }

    /**
     * Gets or sets the HTTP protocol. If there is no current protocol set,
     * it will use the default set in HTTP::$protocol
     *
     * @param string|null $protocol Protocol to set to the request
     * @return  Kohana_Request|string
     */
    public function protocol(?string $protocol = null)
    {
        if ($protocol === null) {
            if ($this->protocol)
                return $this->protocol;
            else
                return $this->protocol = Http::$protocol;
        }

        // Act as a setter
        $this->protocol = strtoupper($protocol);
        return $this;
    }

    /**
     * Getter/Setter to the security settings for this request. This
     * method should be treated as immutable.
     *
     * @param bool|null $secure Is this request secure?
     * @return  bool|Kohana_Request
     */
    public function secure(?bool $secure = null)
    {
        if ($secure === null)
            return $this->secure;

        // Act as a setter
        $this->secure = $secure;
        return $this;
    }

    /**
     * Gets or sets HTTP headers oo the request. All headers
     * are included immediately after the HTTP protocol definition during
     * transmission. This method provides a simple array or key/value
     * interface to the headers.
     *
     * @param   mixed   $key   Key or array of key/value pairs to set
     * @param string|null $value Value to set to the supplied key
     * @return  mixed
     */
    public function headers($key = null, ?string $value = null)
    {
        if ($key instanceof HeaderBag) {
            // Act a setter, replace all headers
            $this->header = $key;

            return $this;
        }

        if (is_array($key)) {
            // Act as a setter, replace all headers
            $this->header->exchangeArray($key);

            return $this;
        }

        if ($this->header->count() === 0 && $this->isInitial()) {
            // Lazy load the request headers
            $this->header = Http::requestHeaders();
        }

        if ($key === null) {
            // Act as a getter, return all headers
            return $this->header;
        } elseif ($value === null) {
            // Act as a getter, single header
            return $this->header->offsetExists($key) ? $this->header->offsetGet($key) : null;
        }

        // Act as a setter for a single header
        $this->header[$key] = $value;

        return $this;
    }

    /**
     * Set and get cookies values for this request.
     *
     * @param   mixed    $key    Cookie name, or array of cookie values
     * @param string|null $value Value to set to cookie
     * @return array|mixed|Kohana_Request
     */
    public function cookie($key = null, ?string $value = null)
    {
        if (is_array($key)) {
            // Act as a setter, replace all cookies
            $this->cookies = $key;
            return $this;
        } elseif ($key === null) {
            // Act as a getter, all cookies
            return $this->cookies;
        } elseif ($value === null) {
            // Act as a getting, single cookie
            return $this->cookies[$key] ?? null;
        }

        // Act as a setter for a single cookie
        $this->cookies[$key] = $value;

        return $this;
    }

    /**
     * Gets or sets the HTTP body of the request. The body is
     * included after the header, separated by a single empty new line.
     *
     * @param string|null $content Content to set to the object
     * @return  Kohana_Request|string
     */
    public function body(?string $content = null)
    {
        if ($content === null) {
            // Act as a getter
            return $this->body;
        }

        // Act as a setter
        $this->body = $content;

        return $this;
    }

    /**
     * Returns the length of the body for use with
     * content header
     *
     * @return int
     */
    public function contentLength(): int
    {
        return strlen($this->body());
    }

    /**
     * Renders the HTTP_Interaction to a string, producing
     *
     *  - Protocol
     *  - Headers
     *  - Body
     *
     *  If there are variables set to the `Kohana_Request::$_post`
     *  they will override any values set to body.
     *
     * @return  string
     */
    public function render(): string
    {
        if (!$post = $this->post()) {
            $body = $this->body();
        } else {
            $body = http_build_query($post, '', '&');
            $this->body($body)
                ->headers('content-type', 'application/x-www-form-urlencoded; charset=' . Application::$charset);
        }

        // Set the content length
        $this->headers('content-length', (string) $this->contentLength());

        // If Kohana expose, set the user-agent
        if (Application::$expose) {
            $this->headers('user-agent', Application::version());
        }

        // Prepare cookies
        if ($this->cookies) {
            $cookie_string = [];

            // Parse each
            foreach ($this->cookies as $key => $value) {
                $cookie_string[] = $key . '=' . $value;
            }

            // Create the cookie string
            $this->header['cookie'] = implode('; ', $cookie_string);
        }

        $output = $this->method() . ' ' . $this->uri() . ' ' . $this->protocol() . "\r\n";
        $output .= $this->header;
        $output .= $body;

        return $output;
    }

    /**
     * Gets or sets HTTP query string.
     *
     * @param   mixed   $key    Key or key value pairs to set
     * @param string|null $value Value to set to a key
     * @return  mixed
     * @uses    Arr::path
     */
    public function query($key = null, ?string $value = null)
    {
        if (is_array($key)) {
            // Act as a setter, replace all query strings
            $this->get = $key;

            return $this;
        }

        if ($key === null) {
            // Act as a getter, all query strings
            return $this->get;
        } elseif ($value === null) {
            // Act as a getter, single query string
            return Arr::path($this->get, $key);
        }

        // Act as a setter, single query string
        $this->get[$key] = $value;

        return $this;
    }

    /**
     * Gets or sets HTTP POST parameters to the request.
     *
     * @param   mixed  $key    Key or key value pairs to set
     * @param string|null $value Value to set to a key
     * @return  mixed
     * @uses    Arr::path
     */
    public function post($key = null, ?string $value = null)
    {
        if (is_array($key)) {
            // Act as a setter, replace all fields
            $this->post = $key;

            return $this;
        }

        if ($key === null) {
            // Act as a getter, all fields
            return $this->post;
        } elseif ($value === null) {
            // Act as a getter, single field
            return Arr::path($this->post, $key);
        }

        // Act as a setter, single field
        $this->post[$key] = $value;

        return $this;
    }

}
