<?php

namespace Kohana\Http\Exceptions;

class Http411Exception extends HttpException
{
    /**
     * @var int HTTP 411 Length Required
     */
    protected $_code = 411;

}
