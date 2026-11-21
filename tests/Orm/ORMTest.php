<?php

use PHPUnit\Framework\Attributes\Group;
use Kohana\Testing\TestCase;
use Kohana\Database\Connection;
use Kohana\Database\PgsqlConnection;
use Kohana\Orm\Model;
use Kohana\Orm\ValidationException;

/**
 * Smoke tests for ORM against a real database.
 */
#[Group('kohana')]
#[Group('kohana.orm')]
class Kohana_ORMTest extends TestCase
{
    /**
     * @var string Database group the models use
     */
    protected static $connection = 'default';

    private string $previousDefault;

    public function setUp(): void
    {
        parent::setUp();

        try {
            $db = Connection::instance(static::$connection);
            $db->connect();
        } catch (Exception $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }

        // Models without a $dbGroup use the default connection
        // ORM caches each model's connection and columns; start clean for this connection
        (new ReflectionProperty(\Kohana\Orm\Model::class, 'initCache'))->setValue(null, []);
        (new ReflectionProperty(\Kohana\Orm\Model::class, 'columnCache'))->setValue(null, []);
        $this->previousDefault = Connection::$default;
        Connection::$default = static::$connection;

        $id = $db instanceof PgsqlConnection ? 'id SERIAL PRIMARY KEY' : 'id INT AUTO_INCREMENT PRIMARY KEY';

        $db->query(Connection::UPDATE, 'DROP TABLE IF EXISTS orm_posts');
        $db->query(Connection::UPDATE, 'DROP TABLE IF EXISTS orm_authors');
        $db->query(Connection::UPDATE, "CREATE TABLE orm_authors ($id, name VARCHAR(50) NOT NULL)");
        $db->query(Connection::UPDATE, "CREATE TABLE orm_posts ($id, orm_author_id INT NOT NULL, title VARCHAR(100) NOT NULL)");
    }

    public function tearDown(): void
    {
        if (isset($this->previousDefault)) {
            Connection::$default = $this->previousDefault;
        }

        parent::tearDown();
    }

    public function test_create_find_update_delete()
    {
        $author = Model::factory('OrmAuthor');
        $author->name = 'Ayşe';
        $author->save();
        $this->assertTrue($author->loaded());

        $found = Model::factory('OrmAuthor', $author->pk());
        $this->assertSame(get_class(Connection::instance(static::$connection)), get_class((fn () => $this->db)->call($found)));
        $this->assertSame('Ayşe', $found->name);

        $found->name = 'Fatma';
        $this->assertTrue($found->changed('name'));
        $found->save();
        $this->assertSame('Fatma', Model::factory('OrmAuthor', $author->pk())->name);

        $found->delete();
        $this->assertFalse(Model::factory('OrmAuthor', $author->pk())->loaded());
    }

    public function test_relationships_and_find_all()
    {
        $author = Model::factory('OrmAuthor')->values(['name' => 'Ali'], ['name'])->create();

        foreach (['Bir', 'İki'] as $title) {
            Model::factory('OrmPost')->values(['orm_author_id' => $author->pk(), 'title' => $title], ['orm_author_id', 'title'])->create();
        }

        $this->assertSame(2, $author->posts->countAll());
        $this->assertSame(['Bir', 'İki'], array_values($author->posts->orderBy('id')->findAll()->asArray('id', 'title')));
        $this->assertSame('Ali', Model::factory('OrmPost')->find()->author->name);
        $this->assertSame('Ali', $author->asArray()['name']);
    }

    public function test_validation()
    {
        $this->expectException(ValidationException::class);

        Model::factory('OrmAuthor')->values(['name' => ''], ['name'])->save();
    }
}
