<?php

namespace Kohana\Orm;

use Kohana\Foundation\KohanaException;
use Kohana\Validation\Validator;

/**
 * ORM Validation exceptions.
 *
 * @package    Kohana/ORM
 * @author     Kohana Team
 * @copyright  (c) 2007-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class ValidationException extends KohanaException
{
    /**
     * Array of validation objects
     * @var array
     */
    protected $objects = [];

    /**
     * The alias of the main ORM model this exception was created for
     * @var string
     */
    protected $alias = null;

    /**
     * Constructs a new exception for the specified model
     *
     * @param string $alias The alias to use when looking for error messages
     * @param Validation $object The Validation object of the model
     * @param string $message The error message
     * @param array|null $values The array of values for the error message
     * @param int $code The error code for the exception
     * @param Exception|null $previous
     */
    public function __construct($alias, Validator $object, $message = 'Failed to validate array', ?array $values = null, int $code = 0, ?\Exception $previous = null)
    {
        $this->alias = $alias;
        $this->objects['object'] = $object;
        $this->objects['hasMany'] = false;

        parent::__construct($message, $values, $code, $previous);
    }

    /**
     * Adds a Validation object to this exception
     *
     *     // The following will add a validation object for a profile model
     *     // inside the exception for a user model.
     *     $e->add_object('profile', $validation);
     *     // The errors array will now look something like this
     *     // [
     *     //     'username' => 'This field is required'
     *     //     'profile' => [
     *     //         'first_name' => 'This field is required'
     *     //     ]
     *     // ];
     *
     * @param string $alias The relationship alias from the model
     * @param  Validation $object   The Validation object to merge
     * @param  mixed      $has_many The array key to use if this exception can be merged multiple times
     * @return Kohana_ORM_Validation_Exception
     */
    public function addObject(string $alias, Validator $object, $has_many = false): self
    {
        // We will need this when generating errors
        $this->objects[$alias]['hasMany'] = $has_many !== false;

        if ($has_many === true) {
            // This is most likely a has_many relationship
            $this->objects[$alias][]['object'] = $object;
        } elseif ($has_many) {
            // This is most likely a has_many relationship
            $this->objects[$alias][$has_many]['object'] = $object;
        } else {
            $this->objects[$alias]['object'] = $object;
        }

        return $this;
    }

    /**
     * Merges an ORM_Validation_Exception object into the current exception
     * Useful when you want to combine errors into one array
     *
     * @param  ORM_Validation_Exception $object   The exception to merge
     * @param  mixed                    $has_many The array key to use if this exception can be merged multiple times
     * @return Kohana_ORM_Validation_Exception
     */
    public function merge(self $object, $has_many = false): self
    {
        $alias = $object->alias();

        // We will need this when generating errors
        $this->objects[$alias]['hasMany'] = $has_many !== false;

        if ($has_many === true) {
            // This is most likely a has_many relationship
            $this->objects[$alias][] = $object->objects();
        } elseif ($has_many) {
            // This is most likely a has_many relationship
            $this->objects[$alias][$has_many] = $object->objects();
        } else {
            $this->objects[$alias] = $object->objects();
        }

        return $this;
    }

    /**
     * Returns a merged array of the errors from all the Validation objects in this exception
     *
     *     // Will load Model_User errors from messages/orm-validation/user.php
     *     $e->errors('orm-validation');
     *
     * @param string|null $directory Directory to load error messages from
     * @param   mixed   $translate Translate the message
     * @return  array
     * @see generate_errors()
     */
    public function errors(?string $directory = null, $translate = true): array
    {
        return $this->generateErrors($this->alias, $this->objects, $directory, $translate);
    }

    /**
     * Recursive method to fetch all the errors in this exception
     *
     * @param string $alias Alias to use for messages file
     * @param  array  $array     Array of Validation objects to get errors from
     * @param string $directory Directory to load error messages from
     * @param  mixed  $translate Translate the message
     * @return array
     */
    protected function generateErrors(string $alias, array $array, string $directory, $translate): array
    {
        $errors = [];

        foreach ($array as $key => $object) {
            if (is_array($object)) {
                $errors[$key] = $key === 'external'
                    // Search for errors in $alias/_external.php
                    ? $this->generateErrors($alias . '/' . $key, $object, $directory, $translate)
                    // Regular models get their own file not nested within $alias
                    : $this->generateErrors($key, $object, $directory, $translate);
            } elseif ($object instanceof Validator) {
                if ($directory === null) {
                    // Return the raw errors
                    $file = null;
                } else {
                    $file = trim($directory . '/' . $alias, '/');
                }

                // Merge in this array of errors
                $errors += $object->errors($file, $translate);
            }
        }

        return $errors;
    }

    /**
     * Returns the protected _objects property from this exception
     *
     * @return array
     */
    public function objects(): array
    {
        return $this->objects;
    }

    /**
     * Returns the protected _alias property from this exception
     *
     * @return string
     */
    public function alias(): ?string
    {
        return $this->alias;
    }

}
