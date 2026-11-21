<?php

use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/OrmAuthTest.php';

#[Group('kohana')]
#[Group('kohana.auth')]
#[Group('kohana.database.pgsql')]
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class Kohana_PgsqlOrmAuthTest extends Kohana_OrmAuthTest
{
    protected static $connection = 'pgsql';
}
