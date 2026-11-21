<?php

namespace Kohana\Http\Exceptions;

class Http505Exception extends HttpException
{
    /**
     * @var int HTTP 505 HTTP Version Not Supported
     */
    protected $_code = 505;

}
