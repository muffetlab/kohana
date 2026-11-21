<?php

use Kohana\Console\Command;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test case for Minion_Util
 *
 * @package    Kohana/Minion
 * @category   Test
 * @author     Kohana Team
 * @copyright  (c) 2009-2012 Kohana Team
 * @license    https://kohana.top/license
 */
#[Group('kohana')]
#[Group('kohana.minion')]
class Minion_TaskTest extends TestCase
{
    /**
     * Provides test data for test_convert_task_to_class_name()
     *
     * @return array
     */
    public static function provider_convert_task_to_class_name(): array
    {
        return [
            ['App\Console\Commands\Db\MigrateCommand', 'db:migrate'],
            ['App\Console\Commands\Db\StatusCommand', 'db:status'],
            ['', ''],
        ];
    }

    /**
     * Tests that a task can be converted to a class name
     *
     * @param string $expected Expected class name
     * @param string $task_name Input task name
     */
    #[DataProvider('provider_convert_task_to_class_name')]
    #[Test]
    public function test_convert_task_to_class_name(string $expected, string $task_name)
    {
        $this->assertSame($expected, Command::convertTaskToClassName($task_name));
    }

    /**
     * Provides test data for test_convert_class_to_task()
     *
     * @return array
     */
    public static function provider_convert_class_to_task(): array
    {
        return [
            ['db:migrate', 'App\Console\Commands\Db\MigrateCommand'],
        ];
    }

    /**
     * Tests that the task name can be found from a class name / object
     *
     * @param string $expected Expected task name
     * @param mixed $class Input class
     */
    #[DataProvider('provider_convert_class_to_task')]
    #[Test]
    public function test_convert_class_to_task(string $expected, $class)
    {
        $this->assertSame($expected, Command::convertClassToTask($class));
    }

}
