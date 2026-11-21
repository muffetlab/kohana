<?php

namespace Kohana\Database\Schema;

use Kohana\Database\Connection;
use Kohana\Database\Query\Expression;

/**
 * Turns a Blueprint into SQL statements for one database system.
 *
 * @package    Kohana/Database
 * @category   Schema
 */
abstract class Grammar
{
    public function __construct(protected Connection $db)
    {
    }

    /**
     * SQL type for a column, e.g. "VARCHAR(255)".
     */
    abstract protected function type(ColumnDefinition $column): string;

    abstract protected function compileDropIndex(Blueprint $blueprint, string $name): string;

    abstract protected function compileDropForeign(Blueprint $blueprint, string $name): string;

    /**
     * Statements that create or alter the table.
     *
     * @return string[]
     */
    public function compile(Blueprint $blueprint): array
    {
        $statements = $blueprint->creating() ? $this->compileCreate($blueprint) : $this->compileAlter($blueprint);

        foreach ($blueprint->commands() as $command) {
            switch ($command['type']) {
                case 'index':
                case 'unique':
                    $statements[] = $this->compileIndex($blueprint, $command);
                    break;
                case 'primary':
                    if (!$blueprint->creating()) {
                        $statements[] = 'ALTER TABLE ' . $this->table($blueprint) . ' ADD PRIMARY KEY (' . $this->columnize($command['columns']) . ')';
                    }
                    break;
                case 'foreign':
                    $statements[] = $this->compileForeign($blueprint, $command['foreign']);
                    break;
                case 'dropColumn':
                    $statements[] = 'ALTER TABLE ' . $this->table($blueprint) . ' '
                        . implode(', ', array_map(fn ($column) => 'DROP COLUMN ' . $this->db->quoteColumn($column), $command['columns']));
                    break;
                case 'renameColumn':
                    $statements[] = 'ALTER TABLE ' . $this->table($blueprint) . ' RENAME COLUMN '
                        . $this->db->quoteColumn($command['from']) . ' TO ' . $this->db->quoteColumn($command['to']);
                    break;
                case 'dropIndex':
                case 'dropUnique':
                    $statements[] = $this->compileDropIndex($blueprint, $command['name']);
                    break;
                case 'dropForeign':
                    $statements[] = $this->compileDropForeign($blueprint, $command['name']);
                    break;
            }
        }

        return array_merge($statements, $this->compileComments($blueprint));
    }

    public function compileDrop(string $table, bool $ifExists = false): string
    {
        return 'DROP TABLE ' . ($ifExists ? 'IF EXISTS ' : '') . $this->db->quoteTable($table);
    }

    public function compileRename(string $from, string $to): string
    {
        return 'ALTER TABLE ' . $this->db->quoteTable($from) . ' RENAME TO ' . $this->db->quoteTable($to);
    }

    /**
     * @return string[]
     */
    protected function compileCreate(Blueprint $blueprint): array
    {
        $definitions = array_map([$this, 'columnDefinition'], $blueprint->columns());

        foreach ($blueprint->commands() as $command) {
            if ($command['type'] === 'primary') {
                $definitions[] = 'PRIMARY KEY (' . $this->columnize($command['columns']) . ')';
            }
        }

        return ['CREATE TABLE ' . $this->table($blueprint) . ' (' . implode(', ', $definitions) . ')' . $this->tableOptions()];
    }

    /**
     * @return string[]
     */
    protected function compileAlter(Blueprint $blueprint): array
    {
        if (!$blueprint->columns()) {
            return [];
        }

        return ['ALTER TABLE ' . $this->table($blueprint) . ' '
            . implode(', ', array_map(fn ($column) => 'ADD COLUMN ' . $this->columnDefinition($column), $blueprint->columns()))];
    }

    protected function compileIndex(Blueprint $blueprint, array $command): string
    {
        return 'CREATE ' . ($command['type'] === 'unique' ? 'UNIQUE ' : '') . 'INDEX ' . $this->db->quoteIdentifier($command['name'])
            . ' ON ' . $this->table($blueprint) . ' (' . $this->columnize($command['columns']) . ')';
    }

    protected function compileForeign(Blueprint $blueprint, ForeignKeyDefinition $foreign): string
    {
        $sql = 'ALTER TABLE ' . $this->table($blueprint) . ' ADD CONSTRAINT ' . $this->db->quoteIdentifier($foreign->name)
            . ' FOREIGN KEY (' . $this->columnize($foreign->columns) . ')'
            . ' REFERENCES ' . $this->db->quoteTable($foreign->on) . ' (' . $this->columnize(explode(',', (string) $foreign->references)) . ')';

        if ($foreign->onDelete) {
            $sql .= ' ON DELETE ' . strtoupper($foreign->onDelete);
        }

        if ($foreign->onUpdate) {
            $sql .= ' ON UPDATE ' . strtoupper($foreign->onUpdate);
        }

        return $sql;
    }

    /**
     * @return string[] Statements that set column comments (when not inline)
     */
    protected function compileComments(Blueprint $blueprint): array
    {
        return [];
    }

    protected function columnDefinition(ColumnDefinition $column): string
    {
        $sql = $this->db->quoteColumn($column->name) . ' ' . $this->type($column) . $this->modifiers($column);

        return $sql;
    }

    protected function modifiers(ColumnDefinition $column): string
    {
        $sql = $column->nullable ? ' NULL' : ' NOT NULL';

        if ($column->hasDefault) {
            $sql .= ' DEFAULT ' . $this->defaultValue($column->default);
        }

        if ($column->primary) {
            $sql .= ' PRIMARY KEY';
        }

        return $sql;
    }

    protected function defaultValue($value): string
    {
        if ($value instanceof Expression) {
            return $value->compile($this->db);
        }

        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $this->booleanValue($value);
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $this->db->quote((string) $value);
    }

    protected function booleanValue(bool $value): string
    {
        return $value ? '1' : '0';
    }

    protected function tableOptions(): string
    {
        return '';
    }

    protected function table(Blueprint $blueprint): string
    {
        return $this->db->quoteTable($blueprint->table());
    }

    protected function columnize(array $columns): string
    {
        return implode(', ', array_map(fn ($column) => $this->db->quoteColumn(trim($column)), $columns));
    }
}
