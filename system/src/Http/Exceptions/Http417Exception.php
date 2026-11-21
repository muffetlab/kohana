<?php

namespace Kohana\Http\Exceptions;

class Http417Exception extends HttpException
{
    /**
     * @var int HTTP 417 Expectation Failed
     */
    protected $_code = 417;

}
