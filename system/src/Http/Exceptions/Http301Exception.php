<?php

namespace Kohana\Http\Exceptions;

class Http301Exception extends RedirectHttpException
{
    /**
     * @var int HTTP 301 Moved Permanently
     */
    protected $_code = 301;

}
