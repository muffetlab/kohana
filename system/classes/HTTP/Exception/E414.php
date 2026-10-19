<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E414 extends HTTP_Exception
{
    /**
     * @var int HTTP 414 Request-URI Too Long
     */
    protected $_code = 414;
}
