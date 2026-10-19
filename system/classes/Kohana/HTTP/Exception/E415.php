<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E415 extends HTTP_Exception
{
    /**
     * @var int HTTP 415 Unsupported Media Type
     */
    protected $_code = 415;
}
