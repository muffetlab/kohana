<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Kohana\Testing\DatabaseTestCase;
use Kohana\Database\DB;
use Kohana\Database\DatabaseException;
use Kohana\Foundation\KohanaException;

/**
 * @package    Kohana/Database
 * @category   Test
 * @author     Loong <loong2460@gmail.com>
 * @copyright  (c) 2025 Kohana Group
 * @license    https://kohana.top/license
 */
#[Group('kohana')]
#[Group('kohana.database')]
#[Group('kohana.database.query')]
#[Group('kohana.database.query.update')]
class Kohana_Database_Query_Builder_UpdateTest extends DatabaseTestCase
{
    public function setUp(): void
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
    public static function provider_update_set(): array
    {
        return [
            ['users', ['username' => 'jane'], 'UPDATE users SET username = \'jane\''],
        ];
    }

    /**
     * Tests Database_Query_Builder_Update::set().
     *
     * @param string $table
     * @param array $assignments
     * @param string $expected
     * @throws DatabaseException
     * @throws KohanaException
     */
    #[DataProvider('provider_update_set')]
    #[Test]
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
    public static function provider_update_set_where(): array
    {
        return [
            ['users', ['username' => 'jane'], ['username', '=', 'john'], 'UPDATE users SET username = \'jane\' WHERE username = \'john\''],
        ];
    }

    /**
     * Tests Database_Query_Builder_Update::where().
     *
     * @param string $table
     * @param array $assignments
     * @param array $whereCondition
     * @param string $expected
     * @throws DatabaseException
     * @throws KohanaException
     */
    #[DataProvider('provider_update_set_where')]
    #[Test]
    public function test_update_set_where(string $table, array $assignments, array $whereCondition, string $expected)
    {
        $query = DB::update($table)->set($assignments)->where(...$whereCondition);

        $this->assertEquals($expected, $query->compile());
    }
}
