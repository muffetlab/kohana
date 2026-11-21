<?php

namespace Kohana\Http\Exceptions;

class Http300Exception extends RedirectHttpException
{
    /**
     * @var int HTTP 300 Multiple Choices
     */
    protected $_code = 300;

}
