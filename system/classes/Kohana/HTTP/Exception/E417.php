<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E417 extends HTTP_Exception
{
    /**
     * @var int HTTP 417 Expectation Failed
     */
    protected $_code = 417;
}
