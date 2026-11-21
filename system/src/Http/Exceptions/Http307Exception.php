<?php

namespace Kohana\Http\Exceptions;

class Http307Exception extends RedirectHttpException
{
    /**
     * @var int HTTP 307 Temporary Redirect
     */
    protected $code = 307;

}
