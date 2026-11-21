<?php

use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/ORMTest.php';

/**
 * Runs the ORM tests against PostgreSQL.
 */
#[Group('kohana')]
#[Group('kohana.orm')]
#[Group('kohana.database.pgsql')]
class Kohana_PgsqlORMTest extends Kohana_ORMTest
{
    protected static $connection = 'pgsql';
}
