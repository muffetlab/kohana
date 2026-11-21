<?php

namespace Kohana\Http\Exceptions;

class Http414Exception extends HttpException
{
    /**
     * @var int HTTP 414 Request-URI Too Long
     */
    protected $code = 414;

}
