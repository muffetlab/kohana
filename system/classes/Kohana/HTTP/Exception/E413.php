<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E413 extends HTTP_Exception
{
    /**
     * @var int HTTP 413 Request Entity Too Large
     */
    protected $_code = 413;
}
