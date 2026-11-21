<?php

namespace Kohana\Orm;

use Kohana\Foundation\KohanaException;

/**
 * values() was called without a column list on a model that declares
 * neither $fillable nor $guarded.
 *
 * @package    Kohana/ORM
 */
class MassAssignmentException extends KohanaException
{
}
