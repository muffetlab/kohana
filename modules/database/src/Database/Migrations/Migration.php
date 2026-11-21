<?php

namespace Kohana\Database\Migrations;

/**
 * Base class of migrations. A migration file returns an instance:
 *
 *     return new class extends Migration
 *     {
 *         public function up(): void
 *         {
 *             Schema::create('posts', function (Blueprint $table) {
 *                 $table->id();
 *                 $table->string('title');
 *             });
 *         }
 *
 *         public function down(): void
 *         {
 *             Schema::dropIfExists('posts');
 *         }
 *     };
 *
 * @package    Kohana/Database
 * @category   Migrations
 */
abstract class Migration
{
    /**
     * @var string|null Database group this migration runs on; null uses the migrator's connection
     */
    protected ?string $connection = null;

    /**
     * Applies the migration.
     */
    abstract public function up(): void;

    /**
     * Reverts the migration.
     */
    public function down(): void
    {
    }

    public function getConnection(): ?string
    {
        return $this->connection;
    }
}
