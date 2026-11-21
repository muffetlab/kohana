<?php

namespace Kohana\Http\Contracts;

/**
 * An HTTP Request specific interface that adds the methods required
 * by HTTP requests. Over and above [Kohana_HTTP_Interaction], this
 * interface provides method, uri, get and post methods.
 *
 * @package    Kohana
 * @category   HTTP
 * @author     Kohana Team
 * @since      3.1.0
 * @copyright  (c) 2008-2014 Kohana Team
 * @license    https://kohana.top/license
 */
interface Request extends Message
{
    // HTTP Methods
    public const GET = 'GET';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const DELETE = 'DELETE';
    public const HEAD = 'HEAD';
    public const OPTIONS = 'OPTIONS';
    public const TRACE = 'TRACE';
    public const CONNECT = 'CONNECT';

    /**
     * Gets or sets the HTTP method. Usually GET, POST, PUT or DELETE in
     * traditional CRUD applications.
     *
     * @param string|null $method Method to use for this request
     * @return  mixed
     */
    public function method(?string $method = null);

    /**
     * Gets the URI of this request.
     *
     * @return string|HTTP_Request
     */
    public function uri();
    /**
     * Gets or sets HTTP query string.
     *
     * @param   mixed   $key    Key or key value pairs to set
     * @param string|null $value Value to set to a key
     * @return  mixed
     */
    public function query($key = null, ?string $value = null);
    /**
     * Gets or sets HTTP POST parameters to the request.
     *
     * @param   mixed   $key   Key or key value pairs to set
     * @param string|null $value Value to set to a key
     * @return  mixed
     */
    public function post($key = null, ?string $value = null);
}
