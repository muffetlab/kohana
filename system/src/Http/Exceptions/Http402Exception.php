<?php

namespace Kohana\Http\Exceptions;

class Http402Exception extends HttpException
{
    /**
     * @var int HTTP 402 Payment Required
     */
    protected $code = 402;

}
