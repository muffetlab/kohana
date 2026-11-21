<?php

namespace Kohana\Database;

/**
 * PostgreSQL database connection (pdo_pgsql).
 *
 *     'pgsql' => [
 *         'type' => 'pgsql',
 *         'connection' => [
 *             'hostname' => '127.0.0.1',
 *             'port' => 5432,
 *             'database' => 'kohana',
 *             'username' => 'postgres',
 *             'password' => '',
 *             // or: 'dsn' => 'pgsql:host=127.0.0.1;port=5432;dbname=kohana',
 *         ],
 *         'charset' => 'UTF8',
 *         'schema' => 'public',
 *     ],
 *
 * @package    Kohana/Database
 * @category   Drivers
 */
class PgsqlConnection extends PdoConnection
{
    // PostgreSQL quotes identifiers with double quotes
    protected $identifier = '"';

    public function connect()
    {
        if ($this->connection) {
            return;
        }

        $connection = $this->config['connection'] ?? [];

        if (empty($connection['dsn'])) {
            $dsn = [];
            foreach (['hostname' => 'host', 'port' => 'port', 'database' => 'dbname', 'sslmode' => 'sslmode'] as $key => $name) {
                if (isset($connection[$key]) && $connection[$key] !== '') {
                    $dsn[] = $name . '=' . $connection[$key];
                }
            }
            $this->config['connection']['dsn'] = 'pgsql:' . implode(';', $dsn);
        }

        parent::connect();

        if (!empty($this->config['schema'])) {
            $this->connection->exec('SET search_path TO ' . $this->quoteIdentifier($this->config['schema']));
        }
    }

    public function setCharset(string $charset)
    {
        // Make sure the database is connected
        $this->connection or $this->connect();

        $this->connection->exec('SET client_encoding TO ' . $this->quote($charset));
    }

    /**
     * PostgreSQL has no last insert ID when the table has no sequence
     * (serial or identity column); return null instead of failing.
     *
     * @return string|false|null
     */
    protected function insertId()
    {
        try {
            return $this->connection->lastInsertId();
        } catch (\PDOException $e) {
            return null;
        }
    }

    public function datatype(string $type): array
    {
        static $types = [
            'bigserial' => ['type' => 'int', 'min' => '0', 'max' => '9223372036854775807'],
            'boolean' => ['type' => 'bool'],
            'bytea' => ['type' => 'string', 'binary' => true],
            'cidr' => ['type' => 'string'],
            'inet' => ['type' => 'string'],
            'json' => ['type' => 'string'],
            'jsonb' => ['type' => 'string'],
            'macaddr' => ['type' => 'string'],
            'money' => ['type' => 'float'],
            'serial' => ['type' => 'int', 'min' => '0', 'max' => '2147483647'],
            'smallserial' => ['type' => 'int', 'min' => '0', 'max' => '32767'],
            'text' => ['type' => 'string'],
            'time without time zone' => ['type' => 'string'],
            'timestamp without time zone' => ['type' => 'string'],
            'tsvector' => ['type' => 'string'],
            'uuid' => ['type' => 'string'],
            'xml' => ['type' => 'string'],
        ];

        return $types[$type] ?? parent::datatype($type);
    }

    public function listTables(?string $like = null): array
    {
        $sql = 'SELECT table_name FROM information_schema.tables'
            . ' WHERE table_schema = current_schema() AND table_type = \'BASE TABLE\'';

        if (is_string($like)) {
            $sql .= ' AND table_name LIKE ' . $this->quote($like);
        }

        $tables = [];
        foreach ($this->query(Connection::SELECT, $sql . ' ORDER BY table_name') as $row) {
            $tables[] = $row['table_name'];
        }

        return $tables;
    }

    public function listColumns(string $table, ?string $like = null, bool $add_prefix = true): array
    {
        if ($add_prefix === true) {
            $table = $this->tablePrefix() . $table;
        }

        $sql = 'SELECT column_name, column_default, is_nullable, data_type, character_maximum_length,'
            . ' numeric_precision, numeric_scale, datetime_precision, collation_name, ordinal_position, is_identity'
            . ' FROM information_schema.columns'
            . ' WHERE table_schema = current_schema() AND table_name = ' . $this->quote($table);

        if (is_string($like)) {
            $sql .= ' AND column_name LIKE ' . $this->quote($like);
        }

        $columns = [];
        foreach ($this->query(Connection::SELECT, $sql . ' ORDER BY ordinal_position') as $row) {
            $column = $this->datatype($row['data_type']) + ['type' => 'string'];

            $column['column_name'] = $row['column_name'];
            $column['column_default'] = $row['column_default'];
            $column['data_type'] = $row['data_type'];
            $column['is_nullable'] = $row['is_nullable'] === 'YES';
            $column['ordinal_position'] = (int) $row['ordinal_position'];

            switch ($column['type']) {
                case 'float':
                    $column['numeric_precision'] = $row['numeric_precision'];
                    $column['numeric_scale'] = $row['numeric_scale'];
                    break;
                case 'string':
                    if ($row['character_maximum_length'] !== null) {
                        $column['character_maximum_length'] = $row['character_maximum_length'];
                    }
                    if ($row['collation_name'] !== null) {
                        $column['collation_name'] = $row['collation_name'];
                    }
                    break;
            }

            // PostgreSQL attributes
            $column['is_identity'] = $row['is_identity'] === 'YES';
            $column['extra'] = $column['is_identity'] || str_starts_with((string) $row['column_default'], 'nextval(') ? 'auto_increment' : '';

            $columns[$row['column_name']] = $column;
        }

        return $columns;
    }
}
