<?php

namespace Kohana\Database\Schema;

/**
 * Describes a table to create or the changes to make to an existing table.
 *
 *     Schema::create('posts', function (Blueprint $table) {
 *         $table->id();
 *         $table->foreignId('user_id')->constrained()->cascadeOnDelete();
 *         $table->string('title');
 *         $table->text('body')->nullable();
 *         $table->boolean('published')->default(false);
 *         $table->timestamps();
 *
 *         $table->index(['user_id', 'published']);
 *     });
 *
 * @package    Kohana/Database
 * @category   Schema
 */
class Blueprint
{
    /**
     * @var ColumnDefinition[] Columns to add
     */
    protected array $columns = [];

    /**
     * @var array Other changes, each ['type' => ..., ...]
     */
    protected array $commands = [];

    /**
     * @param string $table Table name, without prefix
     * @param bool $creating Whether the table is being created (otherwise altered)
     */
    public function __construct(protected string $table, protected bool $creating = false)
    {
    }

    public function table(): string
    {
        return $this->table;
    }

    public function creating(): bool
    {
        return $this->creating;
    }

    /**
     * @return ColumnDefinition[]
     */
    public function columns(): array
    {
        return $this->columns;
    }

    public function commands(): array
    {
        return $this->commands;
    }

    public function addColumn(string $type, string $name, array $parameters = []): ColumnDefinition
    {
        return $this->columns[] = new ColumnDefinition($this, $type, $name, $parameters);
    }

    // -- Column types ---------------------------------------------------------

    /**
     * Auto-incrementing unsigned BIGINT primary key.
     */
    public function id(string $name = 'id'): ColumnDefinition
    {
        return $this->bigIncrements($name);
    }

    public function increments(string $name): ColumnDefinition
    {
        return $this->integer($name)->unsigned()->autoIncrement()->primary();
    }

    public function bigIncrements(string $name): ColumnDefinition
    {
        return $this->bigInteger($name)->unsigned()->autoIncrement()->primary();
    }

    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn('string', $name, ['length' => $length]);
    }

    public function char(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn('char', $name, ['length' => $length]);
    }

    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn('text', $name);
    }

    public function longText(string $name): ColumnDefinition
    {
        return $this->addColumn('longText', $name);
    }

    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn('integer', $name);
    }

    public function smallInteger(string $name): ColumnDefinition
    {
        return $this->addColumn('smallInteger', $name);
    }

    public function bigInteger(string $name): ColumnDefinition
    {
        return $this->addColumn('bigInteger', $name);
    }

    public function unsignedInteger(string $name): ColumnDefinition
    {
        return $this->integer($name)->unsigned();
    }

    public function unsignedBigInteger(string $name): ColumnDefinition
    {
        return $this->bigInteger($name)->unsigned();
    }

    /**
     * Unsigned BIGINT for a foreign key: $table->foreignId('user_id')->constrained().
     */
    public function foreignId(string $name): ColumnDefinition
    {
        return $this->unsignedBigInteger($name);
    }

    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn('boolean', $name);
    }

    public function decimal(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn('decimal', $name, ['precision' => $precision, 'scale' => $scale]);
    }

    public function float(string $name): ColumnDefinition
    {
        return $this->addColumn('float', $name);
    }

    public function date(string $name): ColumnDefinition
    {
        return $this->addColumn('date', $name);
    }

    public function dateTime(string $name): ColumnDefinition
    {
        return $this->addColumn('dateTime', $name);
    }

    public function time(string $name): ColumnDefinition
    {
        return $this->addColumn('time', $name);
    }

    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn('timestamp', $name);
    }

    /**
     * Nullable created_at and updated_at timestamps.
     */
    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    public function json(string $name): ColumnDefinition
    {
        return $this->addColumn('json', $name);
    }

    public function binary(string $name): ColumnDefinition
    {
        return $this->addColumn('binary', $name);
    }

    public function uuid(string $name = 'uuid'): ColumnDefinition
    {
        return $this->addColumn('uuid', $name);
    }

    // -- Indexes and keys -----------------------------------------------------

    public function primary(string|array $columns, ?string $name = null): static
    {
        return $this->addIndex('primary', $columns, $name);
    }

    public function unique(string|array $columns, ?string $name = null): static
    {
        return $this->addIndex('unique', $columns, $name);
    }

    public function index(string|array $columns, ?string $name = null): static
    {
        return $this->addIndex('index', $columns, $name);
    }

    public function foreign(string|array $columns, ?string $name = null): ForeignKeyDefinition
    {
        $columns = (array) $columns;
        $foreign = new ForeignKeyDefinition($columns, $name ?? $this->indexName('foreign', $columns));
        $this->commands[] = ['type' => 'foreign', 'foreign' => $foreign];

        return $foreign;
    }

    // -- Changes to existing tables ------------------------------------------

    public function dropColumn(string|array $columns): static
    {
        $this->commands[] = ['type' => 'dropColumn', 'columns' => (array) $columns];

        return $this;
    }

    public function renameColumn(string $from, string $to): static
    {
        $this->commands[] = ['type' => 'renameColumn', 'from' => $from, 'to' => $to];

        return $this;
    }

    /**
     * @param string|array $index Index name, or the columns it was created on
     */
    public function dropIndex(string|array $index): static
    {
        return $this->dropNamedIndex('dropIndex', 'index', $index);
    }

    public function dropUnique(string|array $index): static
    {
        return $this->dropNamedIndex('dropUnique', 'unique', $index);
    }

    public function dropForeign(string|array $index): static
    {
        return $this->dropNamedIndex('dropForeign', 'foreign', $index);
    }

    public function dropTimestamps(): static
    {
        return $this->dropColumn(['created_at', 'updated_at']);
    }

    // -- Helpers --------------------------------------------------------------

    protected function addIndex(string $type, string|array $columns, ?string $name): static
    {
        $columns = (array) $columns;
        $this->commands[] = ['type' => $type, 'columns' => $columns, 'name' => $name ?? $this->indexName($type, $columns)];

        return $this;
    }

    protected function dropNamedIndex(string $command, string $type, string|array $index): static
    {
        $name = is_array($index) ? $this->indexName($type, $index) : $index;
        $this->commands[] = ['type' => $command, 'name' => $name];

        return $this;
    }

    /**
     * Default index name: posts_user_id_published_index, users_email_unique, ...
     */
    public function indexName(string $type, array $columns): string
    {
        return strtolower(preg_replace('/[^A-Za-z0-9_]+/', '_', $this->table . '_' . implode('_', $columns) . '_' . $type));
    }
}
