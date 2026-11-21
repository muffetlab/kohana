<?php

namespace Kohana\Http\Exceptions;

class Http503Exception extends HttpException
{
    /**
     * @var int HTTP 503 Service Unavailable
     */
    protected $_code = 503;

}
