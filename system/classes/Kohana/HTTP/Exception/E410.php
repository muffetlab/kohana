<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E410 extends HTTP_Exception
{
    /**
     * @var int HTTP 410 Gone
     */
    protected $_code = 410;
}
