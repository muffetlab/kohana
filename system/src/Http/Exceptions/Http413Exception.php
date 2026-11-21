<?php

namespace Kohana\Http\Exceptions;

class Http413Exception extends HttpException
{
    /**
     * @var int HTTP 413 Request Entity Too Large
     */
    protected $_code = 413;

}
