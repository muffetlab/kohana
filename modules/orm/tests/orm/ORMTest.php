<?php

/**
 * Smoke tests for ORM against a real database.
 *
 * @group kohana
 * @group kohana.orm
 */
class Kohana_ORMTest extends Unittest_TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        try {
            $db = Database::instance();
            $db->connect();
        } catch (Exception $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }

        $db->query(Database::UPDATE, 'DROP TABLE IF EXISTS orm_posts');
        $db->query(Database::UPDATE, 'DROP TABLE IF EXISTS orm_authors');
        $db->query(Database::UPDATE, 'CREATE TABLE orm_authors (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL)');
        $db->query(Database::UPDATE, 'CREATE TABLE orm_posts (id INT AUTO_INCREMENT PRIMARY KEY, orm_author_id INT NOT NULL, title VARCHAR(100) NOT NULL)');
    }

    public function test_create_find_update_delete()
    {
        $author = ORM::factory('Orm_Author');
        $author->name = 'Ayşe';
        $author->save();
        $this->assertTrue($author->loaded());

        $found = ORM::factory('Orm_Author', $author->pk());
        $this->assertSame('Ayşe', $found->name);

        $found->name = 'Fatma';
        $this->assertTrue($found->changed('name'));
        $found->save();
        $this->assertSame('Fatma', ORM::factory('Orm_Author', $author->pk())->name);

        $found->delete();
        $this->assertFalse(ORM::factory('Orm_Author', $author->pk())->loaded());
    }

    public function test_relationships_and_find_all()
    {
        $author = ORM::factory('Orm_Author')->values(['name' => 'Ali'], ['name'])->create();

        foreach (['Bir', 'İki'] as $title) {
            ORM::factory('Orm_Post')->values(['orm_author_id' => $author->pk(), 'title' => $title], ['orm_author_id', 'title'])->create();
        }

        $this->assertSame(2, $author->posts->count_all());
        $this->assertSame(['Bir', 'İki'], array_values($author->posts->order_by('id')->find_all()->as_array('id', 'title')));
        $this->assertSame('Ali', ORM::factory('Orm_Post')->find()->author->name);
        $this->assertSame('Ali', $author->as_array()['name']);
    }

    public function test_validation()
    {
        $this->expectException(ORM_Validation_Exception::class);

        ORM::factory('Orm_Author')->values(['name' => ''], ['name'])->save();
    }
}

class Model_Orm_Author extends ORM
{
    protected $_has_many = ['posts' => ['model' => 'Orm_Post']];

    public function rules(): array
    {
        return ['name' => [['not_empty']]];
    }
}

class Model_Orm_Post extends ORM
{
    protected $_belongs_to = ['author' => ['model' => 'Orm_Author', 'foreign_key' => 'orm_author_id']];
}
