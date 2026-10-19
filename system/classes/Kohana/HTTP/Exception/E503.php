<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E503 extends HTTP_Exception
{
    /**
     * @var int HTTP 503 Service Unavailable
     */
    protected $_code = 503;
}
