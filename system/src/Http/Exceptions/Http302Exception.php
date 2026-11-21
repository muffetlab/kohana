<?php

namespace Kohana\Http\Exceptions;

class Http302Exception extends RedirectHttpException
{
    /**
     * @var int HTTP 302 Found
     */
    protected $_code = 302;

}
