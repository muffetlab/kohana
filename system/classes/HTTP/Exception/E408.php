<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E408 extends HTTP_Exception
{
    /**
     * @var int HTTP 408 Request Timeout
     */
    protected $_code = 408;
}
