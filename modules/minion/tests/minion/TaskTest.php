<?php

use Kohana\Minion\Task;
use Kohana\Unittest\TestCase;

/**
 * Test case for Minion_Util
 *
 * @package    Kohana/Minion
 * @group      kohana
 * @group      kohana.minion
 * @category   Test
 * @author     Kohana Team
 * @copyright  (c) 2009-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class Minion_TaskTest extends TestCase
{
    /**
     * Provides test data for test_convert_task_to_class_name()
     *
     * @return array
     */
    public function provider_convert_task_to_class_name(): array
    {
        return [
            ['Task\Db\Migrate', 'db:migrate'],
            ['Task\Db\Status', 'db:status'],
            ['', ''],
        ];
    }

    /**
     * Tests that a task can be converted to a class name
     *
     * @test
     * @covers Minion_Task::convert_task_to_class_name
     * @dataProvider provider_convert_task_to_class_name
     * @param string $expected Expected class name
     * @param string $task_name Input task name
     */
    public function test_convert_task_to_class_name(string $expected, string $task_name)
    {
        $this->assertSame($expected, Task::convert_task_to_class_name($task_name));
    }

    /**
     * Provides test data for test_convert_class_to_task()
     *
     * @return array
     */
    public function provider_convert_class_to_task(): array
    {
        return [
            ['db:migrate', '\Kohana\Task\Db\Migrate'],
        ];
    }

    /**
     * Tests that the task name can be found from a class name / object
     *
     * @test
     * @covers Minion_Task::convert_class_to_task
     * @dataProvider provider_convert_class_to_task
     * @param string $expected Expected task name
     * @param mixed $class Input class
     */
    public function test_convert_class_to_task(string $expected, $class)
    {
        $this->assertSame($expected, Task::convert_class_to_task($class));
    }

}
