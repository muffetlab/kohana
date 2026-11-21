<?php

namespace Kohana\Database\Query;

use Kohana\Database\Connection;

/**
 * Database expressions can be used to add unescaped SQL fragments to a
 * [Database_Query_Builder] object.
 *
 * For example, you can use an expression to generate a column alias:
 *
 *     // SELECT CONCAT(first_name, last_name) AS full_name
 *     $query = DB::select([DB::expr('CONCAT(first_name, last_name)'), 'full_name']);
 *
 * More examples are available on the [Query Builder](database/query/builder#database-expressions) page
 *
 * @package    Kohana/Database
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2009 Kohana Team
 * @license    https://kohana.top/license
 */
class Expression
{
    // Unquoted parameters
    protected $parameters;
    // Raw expression string
    protected $value;

    /**
     * Sets the expression string.
     *
     *     $expression = new Database_Expression('COUNT(users.id)');
     *
     * @param string $value Raw SQL expression string
     * @param array $parameters Unquoted parameter values
     * @return  void
     */
    public function __construct(string $value, array $parameters = [])
    {
        // Set the expression string
        $this->value = $value;
        $this->parameters = $parameters;
    }

    /**
     * Bind a variable to a parameter.
     *
     * @param string $param Parameter key to replace
     * @param   mixed   $var    variable to use
     * @return  $this
     */
    public function bind(string $param, &$var): self
    {
        $this->parameters[$param] = &$var;

        return $this;
    }

    /**
     * Set the value of a parameter.
     *
     * @param string $param Parameter key to replace
     * @param   mixed   $value  value to use
     * @return  $this
     */
    public function param(string $param, $value): self
    {
        $this->parameters[$param] = $value;

        return $this;
    }

    /**
     * Add multiple parameter values.
     *
     * @param   array   $params list of parameter values
     * @return  $this
     */
    public function parameters(array $params): self
    {
        $this->parameters = $params + $this->parameters;

        return $this;
    }

    /**
     * Get the expression value as a string.
     *
     *     $sql = $expression->value();
     *
     * @return  string
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Return the value of the expression as a string.
     *
     *     echo $expression;
     *
     * @return  string
     * @uses    \Kohana\Database\Query\Expression::value
     */
    public function __toString()
    {
        return $this->value();
    }

    /**
     * Compile the SQL expression and return it. Replaces any parameters with
     * their given values.
     *
     * @param mixed $db Database instance or name of instance
     * @return  string
     * @throws \Kohana\Foundation\KohanaException
     */
    public function compile($db = null): string
    {
        if (!is_object($db)) {
            // Get the database instance
            $db = Connection::instance($db);
        }

        $value = $this->value();

        if (!empty($this->parameters)) {
            // Quote all the parameter values
            $params = array_map([$db, 'quote'], $this->parameters);

            // Replace the values in the expression
            $value = strtr($value, $params);
        }

        return $value;
    }

}
