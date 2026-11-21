<?php

namespace Kohana\Http\Exceptions;

class Http504Exception extends HttpException
{
    /**
     * @var int HTTP 504 Gateway Timeout
     */
    protected $_code = 504;

}
