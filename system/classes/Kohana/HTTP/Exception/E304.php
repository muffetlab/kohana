<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception\Expected as HTTP_Exception_Expected;

class E304 extends HTTP_Exception_Expected
{
    /**
     * @var int HTTP 304 Not Modified
     */
    protected $_code = 304;
}
