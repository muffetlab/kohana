<?php

namespace Kohana\Http\Exceptions;

class Http304Exception extends ExpectedHttpException
{
    /**
     * @var int HTTP 304 Not Modified
     */
    protected $code = 304;

}
