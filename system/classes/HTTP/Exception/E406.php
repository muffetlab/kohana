<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E406 extends HTTP_Exception
{
    /**
     * @var int HTTP 406 Not Acceptable
     */
    protected $_code = 406;
}
