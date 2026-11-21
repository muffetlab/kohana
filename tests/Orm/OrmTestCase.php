<?php

use Kohana\Database\Connection;
use Kohana\Database\Schema\Blueprint;
use Kohana\Database\Schema\Schema;
use Kohana\Testing\TestCase;

/**
 * Creates the article tables on the connection a test runs on and points
 * Connection::$default at it.
 */
abstract class Kohana_OrmTestCase extends TestCase
{
    private ?string $previousDefault = null;

    public static function provider_connections(): array
    {
        return [['mysqli'], ['pgsql']];
    }

    protected function useConnection(string $name): Connection
    {
        try {
            $db = Connection::instance($name);
            $db->connect();
        } catch (Exception $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }

        $this->previousDefault ??= Connection::$default;
        Connection::$default = $name;

        $schema = Schema::connection($name);
        foreach (['article_tag', 'article_tags', 'article_comments', 'articles'] as $table) {
            $schema->dropIfExists($table);
        }

        $schema->create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('views')->default(0);
            $table->decimal('rating', 4, 2)->nullable();
            $table->boolean('published')->default(false);
            $table->json('meta')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->string('secret')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });
        $schema->create('article_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('article_id')->index();
            $table->string('body');
        });
        $schema->create('article_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        $schema->create('article_tag', function (Blueprint $table) {
            $table->unsignedBigInteger('article_id');
            $table->unsignedBigInteger('tag_id');
            $table->primary(['article_id', 'tag_id']);
        });

        return $db;
    }

    public function tearDown(): void
    {
        if ($this->previousDefault !== null) {
            Connection::$default = $this->previousDefault;
        }

        parent::tearDown();
    }
}
