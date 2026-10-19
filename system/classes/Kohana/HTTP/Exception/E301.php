<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception\Redirect as HTTP_Exception_Redirect;

class E301 extends HTTP_Exception_Redirect
{
    /**
     * @var int HTTP 301 Moved Permanently
     */
    protected $_code = 301;
}
