<?php

namespace Kohana\Http\Exceptions;

class Http408Exception extends HttpException
{
    /**
     * @var int HTTP 408 Request Timeout
     */
    protected $code = 408;

}
