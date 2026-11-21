<?php

namespace Kohana\Http\Exceptions;

class Http303Exception extends RedirectHttpException
{
    /**
     * @var int HTTP 303 See Other
     */
    protected $code = 303;

}
