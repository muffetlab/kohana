<?php

/**
 * @package    Kohana/Database
 * @group      kohana
 * @group      kohana.database
 * @group      kohana.database.query
 * @group      kohana.database.query.insert
 * @category   Test
 * @author     Loong <loong2460@gmail.com>
 * @copyright  (c) 2025 Kohana Group
 * @license    https://kohana.top/license
 */
class Kohana_Database_Query_Builder_InsertTest extends Kohana_Unittest_Database_TestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $this->getConnection();
        } catch (Exception $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Provides test data for test_insert_values().
     *
     * @return array[]
     */
    public function provider_insert_values(): array
    {
        return [
            ['users', ['username', 'password'], ['fred', 'p@5sW0Rd'], 'INSERT INTO users (username, password) VALUES (\'fred\', \'p@5sW0Rd\')'],
        ];
    }

    /**
     * Tests Database_Query_Builder_Insert::values().
     *
     * @test
     * @dataProvider provider_insert_values
     * @param string $table
     * @param array $columns
     * @param array $values
     * @param string $expected
     * @throws Database_Exception
     * @throws Kohana_Exception
     */
    public function test_insert_values(string $table, array $columns, array $values, string $expected)
    {
        $query = DB::insert($table, $columns)->values($values);

        $this->assertEquals($expected, $query->compile());
    }
}
