<?php

namespace Kohana\Database;

use Kohana\Foundation\Application;
use Kohana\Foundation\KohanaException;
use Kohana\Foundation\Profiler;

/**
 * PDO database connection.
 *
 * @package    Kohana/Database
 * @category   Drivers
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    https://kohana.top/license
 */
class PdoConnection extends Connection
{
    // PDO uses no quoting for identifiers
    protected $identifier = '';

    public function __construct($name, array $config)
    {
        parent::__construct($name, $config);

        if (isset($this->config['identifier'])) {
            // Allow the identifier to be overloaded per-connection
            $this->identifier = (string) $this->config['identifier'];
        }
    }

    public function connect()
    {
        if ($this->connection) {
            return;
        }

        // Extract the connection parameters, adding required variables
        extract($this->config['connection'] + [
            'dsn' => '',
            'username' => null,
            'password' => null,
            'persistent' => false,
        ]);

        // Clear the connection parameters for security
        unset($this->config['connection']);

        // Force PDO to use exceptions for all errors
        $options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;

        if (!empty($persistent)) {
            // Make the connection persistent
            $options[\PDO::ATTR_PERSISTENT] = true;
        }

        try {
            // Create a new PDO connection
            $this->connection = new \PDO($dsn, $username, $password, $options);
        } catch (\PDOException $e) {
            throw new DatabaseException(':error', [':error' => $e->getMessage()], $e->getCode());
        }

        if (!empty($this->config['charset'])) {
            // Set the character set
            $this->setCharset($this->config['charset']);
        }
    }

    /**
     * Create or redefine a SQL aggregate function.
     *
     * [!!] Works only with SQLite
     *
     * @link https://www.php.net/manual/en/pdo.sqlitecreateaggregate.php
     *
     * @param string $name Name of the SQL function to be created or redefined
     * @param callable $step Called for each row of a result set
     * @param callable $final Called after all rows of a result set have been processed
     * @param int $arguments Number of arguments that the SQL function takes
     *
     * @return bool
     * @throws Database_Exception
     */
    public function createAggregate(string $name, callable $step, callable $final, int $arguments = -1): bool
    {
        $this->connection or $this->connect();

        return $this->connection->sqliteCreateAggregate(
            $name,
            $step,
            $final,
            $arguments
        );
    }

    /**
     * Create or redefine a SQL function.
     *
     * [!!] Works only with SQLite
     *
     * @link https://www.php.net/manual/en/pdo.sqlitecreatefunction.php
     *
     * @param string $name Name of the SQL function to be created or redefined
     * @param callable $callback Callback which implements the SQL function
     * @param int $arguments Number of arguments that the SQL function takes
     *
     * @return bool
     * @throws Database_Exception
     */
    public function createFunction(string $name, callable $callback, int $arguments = -1): bool
    {
        $this->connection or $this->connect();

        return $this->connection->sqliteCreateFunction(
            $name,
            $callback,
            $arguments
        );
    }

    public function disconnect(): bool
    {
        // Destroy the PDO object
        $this->connection = null;

        return parent::disconnect();
    }

    public function setCharset(string $charset)
    {
        // Make sure the database is connected
        $this->connection or $this->connect();

        // This SQL-92 syntax is not supported by all drivers
        $this->connection->exec('SET NAMES ' . $this->quote($charset));
    }

    /**
     * Perform an SQL query of the given type.
     *
     * @param int $type Database::SELECT, Database::INSERT, etc.
     * @param string $sql SQL query
     * @param mixed $as_object result object class string, true for stdClass, false for assoc array
     * @param array|null $params object construct parameters for result class
     * @return Database_Result|array|int Database_Result for SELECT queries, list (insert ID, row count) for INSERT queries, or number of affected rows for all other queries
     * @throws Database_Exception
     */
    public function query(int $type, string $sql, $as_object = false, ?array $params = null)
    {
        // Make sure the database is connected
        $this->connection or $this->connect();

        if (Application::$profiling) {
            // Benchmark this query for the current instance
            $benchmark = Profiler::start("Database ($this->instance)", $sql);
        }

        try {
            $result = $this->connection->query($sql);
        } catch (\Exception $e) {
            if (isset($benchmark)) {
                // This benchmark is worthless
                Profiler::delete($benchmark);
            }

            // Convert the exception in a database exception
            throw new DatabaseException(':error [ :query ]', [
            ':error' => $e->getMessage(),
            ':query' => $sql,
            ], $e->getCode());
        }

        if (isset($benchmark)) {
            Profiler::stop($benchmark);
        }

        // Set the last query
        $this->lastQuery = $sql;

        if ($type === Connection::SELECT) {
            // Convert the result into an array, as PDOStatement::rowCount is not reliable
            if ($as_object === false) {
                $result->setFetchMode(\PDO::FETCH_ASSOC);
            } elseif (is_string($as_object)) {
                $result->setFetchMode(\PDO::FETCH_CLASS, $as_object, $params);
            } else {
                $result->setFetchMode(\PDO::FETCH_CLASS, 'stdClass');
            }

            $result = $result->fetchAll();

            // Return an iterator of results
            return new CachedResult($result, $sql, $as_object);
        } elseif ($type === Connection::INSERT) {
            // Return a list of insert id and rows created
            return [
                $this->insertId(),
                $result->rowCount(),
            ];
        } else {
            // Return the number of rows affected
            return $result->rowCount();
        }
    }

    /**
     * Returns the ID of the last inserted row.
     *
     * @return string|false|null
     */
    protected function insertId()
    {
        return $this->connection->lastInsertId();
    }

    public function begin(?string $mode = null): bool
    {
        // Make sure the database is connected
        $this->connection or $this->connect();

        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        // Make sure the database is connected
        $this->connection or $this->connect();

        return $this->connection->commit();
    }

    public function rollback(): bool
    {
        // Make sure the database is connected
        $this->connection or $this->connect();

        return $this->connection->rollBack();
    }

    public function listTables(?string $like = null): array
    {
        throw new KohanaException('Database method :method is not supported by :class', [':method' => __FUNCTION__, ':class' => __CLASS__]);
    }

    public function listColumns(string $table, ?string $like = null, bool $add_prefix = true): array
    {
        throw new KohanaException('Database method :method is not supported by :class', [':method' => __FUNCTION__, ':class' => __CLASS__]);
    }

    public function escape(string $value): string
    {
        // Make sure the database is connected
        $this->connection or $this->connect();

        return $this->connection->quote($value);
    }

}
