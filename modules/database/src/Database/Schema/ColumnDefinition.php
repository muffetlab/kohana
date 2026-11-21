<?php

namespace Kohana\Database\Schema;

/**
 * A column in a Blueprint. Modifiers return the column so they can be chained:
 *
 *     $table->string('email')->unique();
 *     $table->integer('votes')->unsigned()->default(0);
 *     $table->text('bio')->nullable();
 *
 * @package    Kohana/Database
 * @category   Schema
 */
class ColumnDefinition
{
    public bool $nullable = false;

    public bool $hasDefault = false;

    public $default = null;

    public bool $unsigned = false;

    public bool $autoIncrement = false;

    public bool $primary = false;

    public ?string $comment = null;

    /**
     * @param Blueprint $blueprint Table the column belongs to
     * @param string $type Abstract type, e.g. "string", "bigInteger"
     * @param string $name Column name
     * @param array $parameters Type parameters, e.g. ['length' => 255]
     */
    public function __construct(
        protected Blueprint $blueprint,
        public string $type,
        public string $name,
        public array $parameters = [],
    ) {
    }

    public function nullable(bool $value = true): static
    {
        $this->nullable = $value;

        return $this;
    }

    /**
     * @param mixed $value Default value; use DB::expr() for SQL expressions such as CURRENT_TIMESTAMP
     */
    public function default($value): static
    {
        $this->hasDefault = true;
        $this->default = $value;

        return $this;
    }

    public function unsigned(): static
    {
        $this->unsigned = true;

        return $this;
    }

    public function autoIncrement(): static
    {
        $this->autoIncrement = true;

        return $this;
    }

    public function primary(): static
    {
        $this->primary = true;

        return $this;
    }

    public function comment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function unique(?string $name = null): static
    {
        $this->blueprint->unique($this->name, $name);

        return $this;
    }

    public function index(?string $name = null): static
    {
        $this->blueprint->index($this->name, $name);

        return $this;
    }

    /**
     * Adds a foreign key on this column; for foreignId('user_id')->constrained().
     *
     * @param string|null $table Referenced table; derived from the column name ("user_id" => "users") when null
     * @param string $column Referenced column
     */
    public function constrained(?string $table = null, string $column = 'id'): ForeignKeyDefinition
    {
        $table ??= \Kohana\Support\Inflector::plural(preg_replace('/_id$/', '', $this->name));

        return $this->blueprint->foreign($this->name)->references($column)->on($table);
    }
}
