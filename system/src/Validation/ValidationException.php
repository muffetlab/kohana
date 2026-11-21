<?php

namespace Kohana\Validation;

use Kohana\Foundation\KohanaException;

/**
 * @package    Kohana
 * @category   Exceptions
 * @author     Kohana Team
 * @copyright  (c) 2009-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class ValidationException extends KohanaException
{
    /**
     * @var  object  Validation instance
     */
    public $array;

    /**
     * @param \Kohana\Validation\Validator $array Validation object
     * @param string $message error message
     * @param array|null $values translation variables
     * @param int $code the exception code
     * @param \Exception|null $previous
     */
    public function __construct(Validator $array, $message = 'Failed to validate array', ?array $values = null, $code = 0, ?\Exception $previous = null)
    {
        $this->array = $array;

        parent::__construct($message, $values, $code, $previous);
    }

}
