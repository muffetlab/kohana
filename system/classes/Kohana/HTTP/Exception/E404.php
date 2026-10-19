<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E404 extends HTTP_Exception
{
    /**
     * @var int HTTP 404 Not Found
     */
    protected $_code = 404;
}
