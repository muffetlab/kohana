<?php

namespace Kohana\Http\Exceptions;

class Http415Exception extends HttpException
{
    /**
     * @var int HTTP 415 Unsupported Media Type
     */
    protected $_code = 415;

}
