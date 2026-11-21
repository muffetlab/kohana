<?php

namespace Kohana\Routing;

use Kohana\Http\Exceptions\HttpException;
use Kohana\Http\Http;
use Kohana\Http\Request;
use Kohana\Http\Response;

/**
 * Abstract controller class. Controllers should only be created using a [Request].
 *
 * Controllers methods will be automatically called in the following order by
 * the request:
 *
 *     $controller = new Controller_Foo($request);
 *     $controller->before();
 *     $controller->bar();
 *     $controller->after();
 *
 * The controller action should add the output it creates to
 * `$this->response->body($output)`, typically in the form of a [View], during the
 * "action" part of execution.
 *
 * @package    Kohana
 * @category   Controller
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
abstract class Controller
{
    /**
     * @var  Request  Request that created the controller
     */
    public $request;

    /**
     * @var  Response The response that will be returned from controller
     */
    public $response;

    /**
     * Creates a new controller instance. Each controller must be constructed
     * with the request object that created it.
     *
     * @param   Request   $request  Request that created the controller
     * @param   Response  $response The request's response
     * @return  void
     */
    public function __construct(Request $request, Response $response)
    {
        // Assign the request to the controller
        $this->request = $request;

        // Assign a response to the controller
        $this->response = $response;
    }

    /**
     * Executes the given action and calls the [Controller::before] and [Controller::after] methods.
     *
     * Can also be used to catch exceptions from actions in a single place.
     *
     * 1. Before the controller action is called, the [Controller::before] method
     * will be called.
     * 2. Next the controller action will be called.
     * 3. After the controller action is called, the [Controller::after] method
     * will be called.
     *
     * @return  Response
     * @throws Kohana_HTTP_Exception
     */
    public function execute(): Response
    {
        // Execute the "before action" method
        $this->before();

        // Determine the action to use: edit_profile => editProfile()
        $action = lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', (string) $this->request->action()))));

        // If the action doesn't exist or is not routable, it's a 404
        if (!$this->isAction($action)) {
            throw HttpException::factory(404, 'The requested URL :uri was not found on this server.', [':uri' => $this->request->uri()])->request($this->request);
        }

        // Execute the action itself
        $this->{$action}();

        // Execute the "after action" method
        $this->after();

        // Return the response
        return $this->response;
    }

    /**
     * Automatically executed before the controller action. Can be used to set
     * class properties, do authorization checks, and execute other custom code.
     *
     * @return  void
     */
    public function before()
    {
        // Nothing by default
    }

    /**
     * Automatically executed after the controller action. Can be used to apply
     * transformation to the response, add extra output, and execute
     * other custom code.
     *
     * @return  void
     */
    public function after()
    {
        // Nothing by default
    }

    /**
     * Issues an HTTP redirect.
     *
     * Proxies to the [HTTP::redirect] method.
     *
     * @param string $uri URI to redirect to
     * @param int $code HTTP Status code to use for the redirect
     * @return mixed
     * @throws HTTP_Exception_Redirect
     * @throws Kohana_Exception
     */
    public static function redirect(string $uri = '', int $code = 302)
    {
        return Http::redirect($uri, $code);
    }

    /**
     * Checks the browser cache to see the response needs to be returned,
     * execution will halt and a 304 Not Modified will be sent if the
     * browser cache is up-to-date.
     *
     *     $this->check_cache(sha1($content));
     *
     * @param string|null $etag Resource Etag
     * @return Response
     * @throws Request_Exception
     */
    protected function checkCache(?string $etag = null): Response
    {
        return Http::checkCache($this->request, $this->response, $etag);
    }

    /**
     * Whether a method may be called as an action from a route: it must be
     * public, non-static and declared by the application controller, not by
     * Kohana's base controllers (so before(), execute(), etc. are never routable).
     *
     * @param string $action Method name
     * @return bool
     */
    protected function isAction(string $action): bool
    {
        if ($action === '' || str_starts_with($action, '__') || !method_exists($this, $action)) {
            return false;
        }

        $method = new \ReflectionMethod($this, $action);

        return $method->isPublic()
            && !$method->isStatic()
            && !str_starts_with($method->getDeclaringClass()->getName(), 'Kohana\\Routing\\');
    }
}
