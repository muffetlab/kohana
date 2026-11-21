<?php

namespace Kohana\Database\Schema;

/**
 *     $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
 *
 * @package    Kohana/Database
 * @category   Schema
 */
class ForeignKeyDefinition
{
    public ?string $references = null;

    public ?string $on = null;

    public ?string $onDelete = null;

    public ?string $onUpdate = null;

    public function __construct(public array $columns, public string $name)
    {
    }

    public function references(string|array $columns): static
    {
        $this->references = implode(',', (array) $columns);

        return $this;
    }

    public function on(string $table): static
    {
        $this->on = $table;

        return $this;
    }

    /**
     * @param string $action cascade, restrict, set null, no action
     */
    public function onDelete(string $action): static
    {
        $this->onDelete = $action;

        return $this;
    }

    public function onUpdate(string $action): static
    {
        $this->onUpdate = $action;

        return $this;
    }

    public function cascadeOnDelete(): static
    {
        return $this->onDelete('cascade');
    }

    public function nullOnDelete(): static
    {
        return $this->onDelete('set null');
    }
}
