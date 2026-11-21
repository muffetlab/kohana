<?php

namespace Kohana\Http\Exceptions;

class Http400Exception extends HttpException
{
    /**
     * @var int HTTP 400 Bad Request
     */
    protected $_code = 400;

}
