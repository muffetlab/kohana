<?php

namespace Kohana\Http\Exceptions;

class Http501Exception extends HttpException
{
    /**
     * @var int HTTP 501 Not Implemented
     */
    protected $code = 501;

}
