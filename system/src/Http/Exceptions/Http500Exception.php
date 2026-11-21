<?php

namespace Kohana\Http\Exceptions;

class Http500Exception extends HttpException
{
    /**
     * @var int HTTP 500 Internal Server Error
     */
    protected $_code = 500;

}
