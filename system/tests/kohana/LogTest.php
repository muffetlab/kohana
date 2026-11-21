<?php

use Kohana\Log\Writers\Writer;
use Kohana\Testing\TestCase;
use Kohana\Log\Logger;

/**
 * Tests Kohana Logging API
 *
 * @group kohana
 * @group kohana.core
 * @group kohana.core.logging
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @author     Matt Button <matthew@sigswitch.com>
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class Kohana_LogTest extends TestCase
{
    /**
     * Tests that when a new logger is created the list of messages is initially
     * empty
     *
     * @test
     * @covers Log
     */
    public function test_messages_is_initially_empty()
    {
        $logger = new Logger;

        $reflection = new ReflectionClass($logger);
        $property = $reflection->getProperty('_messages');

        $this->assertSame([], $property->getValue($logger));
    }

    /**
     * Tests that when a new logger is created the list of writers is initially
     * empty
     *
     * @test
     * @covers Log
     */
    public function test_writers_is_initially_empty()
    {
        $logger = new Logger;

        $reflection = new ReflectionClass($logger);
        $property = $reflection->getProperty('_writers');

        $this->assertSame([], $property->getValue($logger));
    }

    /**
     * Test that attaching a log writer using an array of levels adds it to the array of log writers
     *
     * @TODO Is this test too specific?
     *
     * @test
     * @covers Log::attach
     */
    public function test_attach_attaches_log_writer_and_returns_this()
    {
        $logger = new Logger;
        $writer = $this->getMockForAbstractClass(Writer::class);

        $this->assertSame($logger, $logger->attach($writer));

        $reflection = new ReflectionClass($logger);
        $property = $reflection->getProperty('_writers');

        $this->assertSame([
            spl_object_hash($writer) => ['object' => $writer, 'levels' => []]
        ], $property->getValue($logger));
    }

    /**
     * Test that attaching a log writer using a min/max level adds it to the array of log writers
     *
     * @TODO Is this test too specific?
     *
     * @test
     * @requires OS Linux
     * @covers Log::attach
     */
    public function test_attach_attaches_log_writer_min_max_and_returns_this()
    {
        $logger = new Logger;
        $writer = $this->getMockForAbstractClass(Writer::class);

        $this->assertSame($logger, $logger->attach($writer, Logger::NOTICE, Logger::CRITICAL));


        $reflection = new ReflectionClass($logger);
        $property = $reflection->getProperty('_writers');

        $this->assertSame([
            spl_object_hash($writer) => [
                'object' => $writer,
                'levels' => [Logger::CRITICAL, Logger::ERROR, Logger::WARNING, Logger::NOTICE]
            ]
        ], $property->getValue($logger));
    }

    /**
     * When we call detach() we expect the specified log writer to be removed
     *
     * @test
     * @covers Log::detach
     */
    public function test_detach_removes_log_writer_and_returns_this()
    {
        $logger = new Logger;
        $writer = $this->getMockForAbstractClass(Writer::class);

        $logger->attach($writer);

        $this->assertSame($logger, $logger->detach($writer));

        $reflection = new ReflectionClass($logger);
        $property = $reflection->getProperty('_writers');

        $this->assertSame([], $property->getValue($logger));
    }

}
