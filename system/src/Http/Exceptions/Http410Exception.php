<?php

namespace Kohana\Http\Exceptions;

class Http410Exception extends HttpException
{
    /**
     * @var int HTTP 410 Gone
     */
    protected $code = 410;

}
