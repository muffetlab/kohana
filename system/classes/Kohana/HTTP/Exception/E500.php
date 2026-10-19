<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E500 extends HTTP_Exception
{
    /**
     * @var int HTTP 500 Internal Server Error
     */
    protected $_code = 500;
}
