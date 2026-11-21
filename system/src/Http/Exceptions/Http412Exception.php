<?php

namespace Kohana\Http\Exceptions;

class Http412Exception extends HttpException
{
    /**
     * @var int HTTP 412 Precondition Failed
     */
    protected $_code = 412;

}
