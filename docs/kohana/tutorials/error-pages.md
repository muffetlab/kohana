# Custom Error Pages

Custom error pages allow you to display a friendly error message to users, rather than the standard Kohana stack trace.

## Prerequisites

1. You will need `'errors' => true` passed to `Application::init()`. This will convert PHP-errors into exceptions which are easier to handle (The default value is `true`).
2. Custom error pages will only be used to handle throw `HttpException`'s. If you simply set a status of, for example, 404 via `Response::status()` the custom page will not be used.

## Extending the HTTP_Exception classes

Handling `HttpException`'s in Kohana has become easier with the changes introduced in 3.3.

For each `HttpException` class we can individually override the generation of the `Response` instance.

[!!] Note: We can also use HMVC to issue a sub-request to another page rather than generating the `Response` in the `HttpException` itself.

For example, to handle 404 pages we can do this in APPPATH/classes/HTTP/Exception/404.php:

    class Http404Exception extends Http404Exception
    {
        /**
         * Generate a Response for the 404 Exception.
         *
         * The user should be shown a nice 404 page.
         *
         * @return Response
         */
        public function getResponse()
        {
            $view = View::factory('errors/404');

            // Remembering that `$this` is an instance of Http404Exception
            $view->message = $this->getMessage();

            $response = Response::factory()
                ->status(404)
                ->body($view->render());

            return $response;
        }
    }

Another example, this time to handle 401 Unauthorized errors (aka "Not Logged In") we can do this in APPPATH/classes/HTTP/Exception/401.php:

    class Http401Exception extends Http401Exception
    {
        /**
         * Generate a Response for the 401 Exception.
         *
         * The user should be redirect to a login page.
         *
         * @return Response
         */
        public function getResponse()
        {
            $response = Response::factory()
                ->status(401)
                ->headers('Location', Url::site('account/login'));

            return $response;
        }
    }

Finally, to override the default `Response` for all `HttpException`'s without a more specific override we can do this in APPPATH/classes/HTTP/Exception.php:

    class HttpException extends HttpException
    {
        /**
         * Generate a Response for all Exceptions without a more specific override
         *
         * The user should see a nice error page, however, if we are in development
         * mode we should show the normal Kohana error page.
         *
         * @return Response
         */
        public function getResponse()
        {
            // Lets log the Exception, Just in case it's important!
            KohanaException::log($this);

            if (Application::$environment >= Application::DEVELOPMENT) {
                // Show the normal Kohana error page.
                return parent::getResponse();
            } else {
                // Generate a nicer looking "Oops" page.
                $view = View::factory('errors/default');

                $response = Response::factory()
                    ->status($this->getCode())
                    ->body($view->render());

                return $response;
            }
        }
    }