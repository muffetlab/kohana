<?php

namespace Kohana\Http\Exceptions;

class Http409Exception extends HttpException
{
    /**
     * @var int HTTP 409 Conflict
     */
    protected $code = 409;

}
