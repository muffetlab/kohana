<?php

/**
 * @package    Kohana/Database
 * @group      kohana
 * @group      kohana.database
 * @group      kohana.database.query
 * @group      kohana.database.query.update
 * @category   Test
 * @author     Loong <loong2460@gmail.com>
 * @copyright  (c) 2025 Kohana Group
 * @license    https://kohana.top/license
 */
class Kohana_Database_Query_Builder_UpdateTest extends Kohana_Unittest_Database_TestCase
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
     * Provides test data for test_update_set().
     *
     * @return array[]
     */
    public function provider_update_set(): array
    {
        return [
            ['users', ['username' => 'jane'], 'UPDATE users SET username = \'jane\''],
        ];
    }

    /**
     * Tests Database_Query_Builder_Update::set().
     *
     * @test
     * @dataProvider provider_update_set
     * @param string $table
     * @param array $assignments
     * @param string $expected
     * @throws Database_Exception
     * @throws Kohana_Exception
     */
    public function test_update_set(string $table, array $assignments, string $expected)
    {
        $query = DB::update($table)->set($assignments);

        $this->assertEquals($expected, $query->compile());
    }

    /**
     * Provides test data for test_update_set_where().
     *
     * @return array[]
     */
    public function provider_update_set_where(): array
    {
        return [
            ['users', ['username' => 'jane'], ['username', '=', 'john'], 'UPDATE users SET username = \'jane\' WHERE username = \'john\''],
        ];
    }

    /**
     * Tests Database_Query_Builder_Update::where().
     *
     * @test
     * @dataProvider provider_update_set_where
     * @param string $table
     * @param array $assignments
     * @param array $whereCondition
     * @param string $expected
     * @throws Database_Exception
     * @throws Kohana_Exception
     */
    public function test_update_set_where(string $table, array $assignments, array $whereCondition, string $expected)
    {
        $query = DB::update($table)->set($assignments)->where(...$whereCondition);

        $this->assertEquals($expected, $query->compile());
    }
}
