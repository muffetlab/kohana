<?php

use Kohana\Testing\TestCase;
use Kohana\Database\Connection;
use Kohana\Database\DB;

/**
 * Runs real queries against both drivers (PDO and MySQLi).
 *
 * @group kohana
 * @group kohana.database
 * @group kohana.database.driver
 */
class Kohana_Database_DriverTest extends TestCase
{
    public function provider_connections(): array
    {
        return [['default'], ['mysqli']];
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
        $db->query(Connection::UPDATE, 'CREATE TABLE driver_test (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL, score INT NULL)');

        return $db;
    }

    /**
     * @dataProvider provider_connections
     */
    public function test_crud_and_result_iteration($name)
    {
        $db = $this->db($name);

        [$id] = DB::insert('driver_test', ['name', 'score'])->values(['ali', 10])->values(['veli', null])->execute($db);
        $this->assertGreaterThan(0, $id);

        $result = DB::select()->from('driver_test')->order_by('id')->execute($db);
        $this->assertCount(2, $result);
        $this->assertSame('ali', $result->get('name'));
        $this->assertSame(['ali', 'veli'], array_values($result->as_array(null, 'name')));
        $this->assertSame('veli', $result[1]['name']);
        $this->assertTrue(isset($result[1]));
        $this->assertFalse(isset($result[2]));

        $names = [];
        foreach ($result as $i => $row) {
            $names[$i] = $row['name'];
        }
        $this->assertSame([0 => 'ali', 1 => 'veli'], $names);

        $objects = DB::select()->from('driver_test')->as_object()->execute($db)->as_array();
        $this->assertSame('ali', $objects[0]->name);

        $cached = $result->cached();
        $this->assertCount(2, $cached);
        $this->assertSame('veli', $cached[1]['name']);

        $this->assertSame(1, DB::update('driver_test')->set(['score' => 5])->where('name', '=', 'veli')->execute($db));
        $this->assertSame('5', (string) DB::select('score')->from('driver_test')->where('name', '=', 'veli')->execute($db)->get('score'));

        $this->assertSame(1, DB::delete('driver_test')->where('name', '=', 'ali')->execute($db));
        $this->assertSame(1, (int) DB::select([DB::expr('COUNT(*)'), 'c'])->from('driver_test')->execute($db)->get('c'));
    }

    /**
     * @dataProvider provider_connections
     */
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

        if ($name === 'mysqli') {
            // The PDO driver does not implement schema introspection
            $this->assertContains('driver_test', $db->list_tables());
            $this->assertArrayHasKey('score', $db->list_columns('driver_test'));
        }
    }
}
