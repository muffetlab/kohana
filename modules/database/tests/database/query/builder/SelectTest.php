<?php

/**
 * @package    Kohana/Database
 * @group      kohana
 * @group      kohana.database
 * @group      kohana.database.query
 * @group      kohana.database.query.select
 * @category   Test
 * @author     Loong <loong2460@gmail.com>
 * @copyright  (c) 2025 Kohana Group
 * @license    https://kohana.top/license
 */
class Kohana_Database_Query_Builder_SelectTest extends Kohana_Unittest_Database_TestCase
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
     * Provides test data for test_select().
     *
     * @return array[]
     */
    public function provider_select(): array
    {
        return [
            [[], null, 'SELECT *'],
            [['username', 'password'], null, 'SELECT username, password'],
            [['users.username', 'users.password'], null, 'SELECT users.username, users.password'],
            [[['username', 'u'], ['password', 'p']], null, 'SELECT username AS u, password AS p'],
            [['username'], ['distinct' => true], 'SELECT DISTINCT username'],
        ];
    }

    /**
     * Tests Database_Query_Builder_Select::select().
     *
     * @test
     * @dataProvider provider_select
     * @param array $columns
     * @param array|null $modifiers
     * @param string $expected
     * @throws Database_Exception
     * @throws Kohana_Exception
     */
    public function test_select(array $columns, ?array $modifiers, string $expected)
    {
        $query = DB::select(...$columns);

        if ($modifiers) {
            foreach ($modifiers as $key => $value) {
                $query->$key($value);
            }
        }

        $this->assertEquals($expected, $query->compile());
    }

    /**
     * Provides test data for test_select_from().
     *
     * @return array[]
     */
    public function provider_select_from(): array
    {
        return [
            [
                [],
                ['users'],
                'SELECT * FROM users'
            ],
            [
                ['username', 'password'],
                [['users', 'u']],
                'SELECT username, password FROM users AS u'
            ],
            [
                [['u.username', 'uu'], ['r.name', 'rn']],
                [['users', 'u'], ['roles', 'r']],
                'SELECT u.username AS uu, r.name AS rn FROM users AS u, roles AS r'
            ],
        ];
    }

    /**
     * Tests Database_Query_Builder_Select::from().
     *
     * @test
     * @dataProvider provider_select_from
     * @param array $columns
     * @param array $tables
     * @param string $expected
     * @throws Database_Exception
     * @throws Kohana_Exception
     */
    public function test_select_from(array $columns, array $tables, string $expected)
    {
        $query = DB::select(...$columns)->from(...$tables);

        $this->assertEquals($expected, $query->compile());
    }

    /**
     * Provides test data for test_select_from_where().
     *
     * @return array[]
     */
    public function provider_select_from_where(): array
    {
        return [
            [
                ['users'],
                [
                    ['where', ['username', '=', 'john']],
                ],
                [['limit', [10]], ['offset', [20]]],
                'SELECT * FROM users WHERE username = \'john\' LIMIT 10 OFFSET 20'
            ],
            [
                [['users', 'u']],
                [
                    ['where', ['u.logins', '<', 1]],
                    ['where', ['u.username', 'IN', ['john', 'mark', 'matt']]],
                ],
                [['limit', [10]], ['limit', [20]], ['offset', [20]], ['offset', [40]]],
                'SELECT * FROM users AS u WHERE u.logins < 1 AND u.username IN (\'john\', \'mark\', \'matt\') LIMIT 20 OFFSET 40'
            ],
            [
                ['users'],
                [
                    ['where_open'],
                    ['where', ['username', '=', 'john']],
                    ['or_where', ['username', '=', 'jane']],
                    ['where_close'],
                    ['and_where', ['logins', '>', 10]],
                ],
                [['order_by', ['logins', 'DESC']]],
                'SELECT * FROM users WHERE (username = \'john\' OR username = \'jane\') AND logins > 10 ORDER BY logins DESC'
            ],
        ];
    }

    /**
     * Tests Database_Query_Builder_Select::where().
     *
     * @test
     * @dataProvider provider_select_from_where
     * @param array $tables
     * @param array $whereConditions
     * @param array $clauses
     * @param string $expected
     * @throws Database_Exception
     * @throws Kohana_Exception
     */
    public function test_select_from_where(array $tables, array $whereConditions, array $clauses, string $expected)
    {
        $query = DB::select()->from(...$tables);

        foreach ($whereConditions as $where) {
            if (isset($where[1])) {
                $query->{$where[0]}(...$where[1]);
            } else {
                $query->{$where[0]}();
            }
        }

        foreach ($clauses as $clause) {
            $query->{$clause[0]}(...$clause[1]);
        }

        $this->assertEquals($expected, $query->compile());
    }

    /**
     * Provides test data for test_select_from_join().
     *
     * @return array[]
     */
    public function provider_select_from_join(): array
    {
        return [
            [
                [],
                ['foobar'],
                [
                    ['bam', [['baz', '=', 'bam']]],
                ],
                'SELECT * FROM foobar JOIN bam ON (baz = bam)'
            ],
            [
                ['col1', 'col2', 'col3'],
                [['foo', 'bar']],
                [
                    ['gah', [['bar.col1', '=', 'gah.col2']]]
                ],
                'SELECT col1, col2, col3 FROM foo AS bar JOIN gah ON (bar.col1 = gah.col2)'
            ],
            [
                [],
                ['gah'],
                [
                    ['foo', [['col1', '=', 'foo'], ['col2', '=', 5], ['col3', '=', 1]]],
                ],
                'SELECT * FROM gah JOIN foo ON (col1 = foo AND col2 = 5 AND col3 = 1)'
            ]

        ];
    }

    /**
     * Tests Database_Query_Builder_Select::join().
     *
     * @test
     * @dataProvider provider_select_from_join
     * @param array $columns
     * @param array $tables
     * @param array $joins
     * @param string $expected
     * @throws Database_Exception
     * @throws Kohana_Exception
     */
    public function test_select_from_join(array $columns, array $tables, array $joins, string $expected)
    {
        $query = DB::select(...$columns)->from(...$tables);

        foreach ($joins as $join) {
            $query->join($join[0]);
            foreach ($join[1] as $on) {
                $query->on(...$on);
            }
        }

        $this->assertEquals($expected, $query->compile());
    }
}
