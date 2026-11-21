<?php

namespace Kohana\Http\Exceptions;

class Http403Exception extends HttpException
{
    /**
     * @var int HTTP 403 Forbidden
     */
    protected $code = 403;

}
