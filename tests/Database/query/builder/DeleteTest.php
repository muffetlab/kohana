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
#[Group('kohana.database.query.delete')]
class Kohana_Database_Query_Builder_DeleteTest extends DatabaseTestCase
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
     * Provides test data for test_delete_where().
     *
     * @return array[]
     */
    public static function provider_delete_where(): array
    {
        return [
            ['users', ['username', 'in', ['john', 'jane']], 'DELETE FROM users WHERE username IN (\'john\', \'jane\')'],
        ];
    }

    /**
     * Tests Database_Query_Builder_Delete::where().
     *
     * @param string $table
     * @param array $whereConditions
     * @param string $expected
     * @throws DatabaseException
     * @throws KohanaException
     */
    #[DataProvider('provider_delete_where')]
    #[Test]
    public function test_delete_where(string $table, array $whereConditions, string $expected)
    {
        $query = DB::delete($table)->where(...$whereConditions);

        $this->assertEquals($expected, $query->compile());
    }
}
