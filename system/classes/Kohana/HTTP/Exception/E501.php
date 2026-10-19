<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E501 extends HTTP_Exception
{
    /**
     * @var int HTTP 501 Not Implemented
     */
    protected $_code = 501;
}
