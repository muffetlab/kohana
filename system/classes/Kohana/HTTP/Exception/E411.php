<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E411 extends HTTP_Exception
{
    /**
     * @var int HTTP 411 Length Required
     */
    protected $_code = 411;
}
