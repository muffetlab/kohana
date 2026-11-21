<?php

namespace Kohana\Http\Exceptions;

class Http502Exception extends HttpException
{
    /**
     * @var int HTTP 502 Bad Gateway
     */
    protected $_code = 502;

}
