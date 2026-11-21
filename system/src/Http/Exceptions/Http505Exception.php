<?php

namespace Kohana\Http\Exceptions;

class Http505Exception extends HttpException
{
    /**
     * @var int HTTP 505 HTTP Version Not Supported
     */
    protected $code = 505;

}
