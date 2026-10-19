<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E402 extends HTTP_Exception
{
    /**
     * @var int HTTP 402 Payment Required
     */
    protected $_code = 402;
}
