<?php

namespace Kohana\Database\Schema;

/**
 * MySQL / MariaDB schema grammar.
 *
 * @package    Kohana/Database
 * @category   Schema
 */
class MySqlGrammar extends Grammar
{
    protected function type(ColumnDefinition $column): string
    {
        $p = $column->parameters;

        $type = match ($column->type) {
            'string' => 'VARCHAR(' . $p['length'] . ')',
            'char' => 'CHAR(' . $p['length'] . ')',
            'text' => 'TEXT',
            'longText' => 'LONGTEXT',
            'integer' => 'INT',
            'smallInteger' => 'SMALLINT',
            'bigInteger' => 'BIGINT',
            'boolean' => 'TINYINT(1)',
            'decimal' => 'DECIMAL(' . $p['precision'] . ', ' . $p['scale'] . ')',
            'float' => 'DOUBLE',
            'date' => 'DATE',
            'dateTime' => 'DATETIME',
            'time' => 'TIME',
            'timestamp' => 'TIMESTAMP',
            'json' => 'JSON',
            'binary' => 'BLOB',
            'uuid' => 'CHAR(36)',
            default => throw new \InvalidArgumentException("Unknown column type {$column->type}"),
        };

        if ($column->unsigned && in_array($column->type, ['integer', 'smallInteger', 'bigInteger', 'decimal', 'float'], true)) {
            $type .= ' UNSIGNED';
        }

        return $type;
    }

    protected function modifiers(ColumnDefinition $column): string
    {
        $sql = parent::modifiers($column);

        if ($column->autoIncrement) {
            $sql = str_replace(' PRIMARY KEY', '', $sql) . ' AUTO_INCREMENT' . ($column->primary ? ' PRIMARY KEY' : '');
        }

        if ($column->comment !== null) {
            $sql .= ' COMMENT ' . $this->db->quote($column->comment);
        }

        return $sql;
    }

    protected function compileDropIndex(Blueprint $blueprint, string $name): string
    {
        return 'DROP INDEX ' . $this->db->quoteIdentifier($name) . ' ON ' . $this->table($blueprint);
    }

    protected function compileDropForeign(Blueprint $blueprint, string $name): string
    {
        return 'ALTER TABLE ' . $this->table($blueprint) . ' DROP FOREIGN KEY ' . $this->db->quoteIdentifier($name);
    }

    protected function tableOptions(): string
    {
        return ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
    }
}
