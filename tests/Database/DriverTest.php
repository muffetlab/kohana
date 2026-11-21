<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use Kohana\Testing\TestCase;
use Kohana\Database\Connection;
use Kohana\Database\DB;
use Kohana\Database\PgsqlConnection;

/**
 * Runs real queries against every driver (PDO MySQL, MySQLi, PostgreSQL).
 */
#[Group('kohana')]
#[Group('kohana.database')]
#[Group('kohana.database.driver')]
class Kohana_Database_DriverTest extends TestCase
{
    public static function provider_connections(): array
    {
        return [['default'], ['mysqli'], ['pgsql']];
    }

    protected function db(string $name): Connection
    {
        try {
            $db = Connection::instance($name);
            $db->connect();
        } catch (Exception $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }

        $db->query(Connection::UPDATE, 'DROP TABLE IF EXISTS driver_test');
        $id = $db instanceof PgsqlConnection ? 'id SERIAL PRIMARY KEY' : 'id INT AUTO_INCREMENT PRIMARY KEY';
        $db->query(Connection::UPDATE, "CREATE TABLE driver_test ($id, name VARCHAR(50) NOT NULL, score INT NULL)");

        return $db;
    }

    #[DataProvider('provider_connections')]
    public function test_crud_and_result_iteration($name)
    {
        $db = $this->db($name);

        [$id] = DB::insert('driver_test', ['name', 'score'])->values(['ali', 10])->values(['veli', null])->execute($db);
        $this->assertGreaterThan(0, $id);

        $result = DB::select()->from('driver_test')->orderBy('id')->execute($db);
        $this->assertCount(2, $result);
        $this->assertSame('ali', $result->get('name'));
        $this->assertSame(['ali', 'veli'], array_values($result->asArray(null, 'name')));
        $this->assertSame('veli', $result[1]['name']);
        $this->assertTrue(isset($result[1]));
        $this->assertFalse(isset($result[2]));

        $names = [];
        foreach ($result as $i => $row) {
            $names[$i] = $row['name'];
        }
        $this->assertSame([0 => 'ali', 1 => 'veli'], $names);

        $objects = DB::select()->from('driver_test')->asObject()->execute($db)->asArray();
        $this->assertSame('ali', $objects[0]->name);

        $cached = $result->cached();
        $this->assertCount(2, $cached);
        $this->assertSame('veli', $cached[1]['name']);

        $this->assertSame(1, DB::update('driver_test')->set(['score' => 5])->where('name', '=', 'veli')->execute($db));
        $this->assertSame('5', (string) DB::select('score')->from('driver_test')->where('name', '=', 'veli')->execute($db)->get('score'));

        $this->assertSame(1, DB::delete('driver_test')->where('name', '=', 'ali')->execute($db));
        $this->assertSame(1, (int) DB::select([DB::expr('COUNT(*)'), 'c'])->from('driver_test')->execute($db)->get('c'));
    }

    #[DataProvider('provider_connections')]
    public function test_transactions_and_escaping($name)
    {
        $db = $this->db($name);

        $db->begin();
        DB::insert('driver_test', ['name'])->values(["o'reilly"])->execute($db);
        $db->rollback();
        $this->assertSame(0, (int) DB::select([DB::expr('COUNT(*)'), 'c'])->from('driver_test')->execute($db)->get('c'));

        $db->begin();
        DB::insert('driver_test', ['name'])->values(["o'reilly"])->execute($db);
        $db->commit();
        $this->assertSame("o'reilly", DB::select('name')->from('driver_test')->execute($db)->get('name'));

        if ($name !== 'default') {
            // The generic PDO driver does not implement schema introspection
            $this->assertContains('driver_test', $db->listTables());
            $this->assertArrayHasKey('score', $db->listColumns('driver_test'));
        }
    }
}
