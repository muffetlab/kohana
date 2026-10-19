<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E504 extends HTTP_Exception
{
    /**
     * @var int HTTP 504 Gateway Timeout
     */
    protected $_code = 504;
}
