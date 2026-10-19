<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E407 extends HTTP_Exception
{
    /**
     * @var int HTTP 407 Proxy Authentication Required
     */
    protected $_code = 407;
}
