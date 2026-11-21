<?php

namespace Kohana\Http\Exceptions;

use Kohana\Http\Response;

/**
 * "Expected" HTTP exception class. Used for all [HTTP_Exception]'s where a standard
 * Kohana error page should never be shown.
 *
 * E.g. [HTTP_Exception_301], [HTTP_Exception_302].
 *
 * @package    Kohana
 * @category   Exceptions
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
abstract class ExpectedHttpException extends HttpException
{
    /**
     * @var  Response   Response Object
     */
    protected $response;

    /**
     * Creates a new translated exception.
     *
     *     throw new Kohana_Exception('Something went terrible wrong, :user', [
     *         ':user' => $user
     *     ]);
     *
     * @param string $message Status message, custom content to display with error
     * @param array|null $variables translation variables
     * @param \Exception|null $previous
     * @throws \Kohana\Foundation\KohanaException
     */
    public function __construct(string $message = '', ?array $variables = null, ?\Exception $previous = null)
    {
        parent::__construct($message, $variables, $previous);

        // Prepare our response object and set the correct status code.
        $this->response = Response::factory()
            ->status($this->code);
    }

    /**
     * Gets and sets headers to the [Response].
     *
     * @param   mixed   $key
     * @param   string|null $value
     * @return  mixed
     * @see     [Response::headers]
     */
    public function headers($key = null, ?string $value = null)
    {
        $result = $this->response->headers($key, $value);

        if (!$result instanceof Response) {
            return $result;
        }

        return $this;
    }

    /**
     * Validate this exception contains everything needed to continue.
     *
     * @return bool
     */
    public function check(): bool
    {
        return true;
    }

    /**
     * Generate a Response for the current Exception
     *
     * @return Response
     * @uses   \Kohana\Foundation\KohanaException::response()
     */
    public function getResponse(): Response
    {
        $this->check();

        return $this->response;
    }

}
