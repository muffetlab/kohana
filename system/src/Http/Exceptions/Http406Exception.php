<?php

namespace Kohana\Http\Exceptions;

class Http406Exception extends HttpException
{
    /**
     * @var int HTTP 406 Not Acceptable
     */
    protected $code = 406;

}
