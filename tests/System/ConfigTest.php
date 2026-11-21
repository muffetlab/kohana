<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Kohana\Config\Contracts\Reader;
use Kohana\Config\Contracts\Writer;
use Kohana\Testing\TestCase;
use Kohana\Config\Repository;
use Kohana\Foundation\KohanaException;

/**
 * Tests the Config lib that's shipped with kohana
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @author     Jeremy Bush <contractfrombelow@gmail.com>
 * @author     Matt Button <matthew@sigswitch.com>
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
#[Group('kohana.core.config')]
class Kohana_ConfigTest extends TestCase
{
    /**
     * When a config object is initially created there should be
     * no readers attached
     */
    #[Test]
    public function test_initially_there_are_no_sources()
    {
        $this->expectException(KohanaException::class);
        $this->expectExceptionMessageIsOrContains('No configuration sources attached');

        $config = new Repository;
        $config->load('');
    }

    /**
     * Test that calling attach() on a kohana config object
     * adds the specified reader to the config object
     */
    #[Test]
    public function test_attach_adds_reader_and_returns_this()
    {
        $config = new Repository;
        $reader = $this->createStub(Reader::class);

        $this->assertSame($config, $config->attach($reader));

        $reflection = new ReflectionClass($config);
        $property = $reflection->getProperty('sources');

        $this->assertContains($reader, $property->getValue($config));
    }

    /**
     * By default, or by passing `true` as the second parameter, the config object
     * should add the reader to the front of the queue of readers.
     */
    #[Test]
    public function test_attach_adds_reader_to_front_of_queue()
    {
        $config = new Repository;

        $reader1 = $this->createStub(Reader::class);
        $reader2 = $this->createStub(Reader::class);

        $config->attach($reader1);
        $config->attach($reader2);

        // Rather than do two assertContains we'll do an assertSame to assert
        // the order of the readers
        $reflection = new ReflectionClass($config);
        $property = $reflection->getProperty('sources');

        $this->assertSame([$reader2, $reader1], $property->getValue($config));
    }

    /**
     * Test that attaching a new reader (and passing false as second param) causes
     * phpunit to append the reader rather than prepend
     */
    #[Test]
    public function test_attach_can_add_reader_to_end_of_queue()
    {
        $config = new Repository;
        $reader1 = $this->createStub(Reader::class);
        $reader2 = $this->createStub(Reader::class);

        $config->attach($reader1);
        $config->attach($reader2, false);

        $reflection = new ReflectionClass($config);
        $property = $reflection->getProperty('sources');

        $this->assertSame([$reader1, $reader2], $property->getValue($config));
    }

    /**
     * Calling detach() on a config object should remove it from the queue of readers
     */
    #[Test]
    public function test_detach_removes_reader_and_returns_this()
    {
        $config = new Repository;

        // Due to the way phpunit mock generator works if you try and mock a class
        // that has already been used then it just re-uses the first's name
        // To get around this we have to specify a totally random name for the second mock object
        $reader1 = $this->createStub(Reader::class);
        $reader2 = $this->getMockBuilder(Reader::class)
            ->setMockClassName('MY_AWESOME_READER')
            ->getMock();

        $config->attach($reader1);
        $config->attach($reader2);

        $this->assertSame($config, $config->detach($reader1));

        $reflection = new ReflectionClass($config);
        $property = $reflection->getProperty('sources');

        $this->assertNotContains($reader1, $property->getValue($config));
        $this->assertContains($reader2, $property->getValue($config));

        $this->assertSame($config, $config->detach($reader2));

        $this->assertNotContains($reader2, $property->getValue($config));
    }

    /**
     * detach() should return $this even if the specified reader does not exist
     */
    #[Test]
    public function test_detach_returns_this_even_when_reader_dnx()
    {
        $config = new Repository;
        $reader = $this->createStub(Reader::class);

        $this->assertSame($config, $config->detach($reader));
    }

    /**
     * If we request a config variable with a dot path then
     * Config::load() should load the group and return the requested variable
     */
    #[Test]
    public function test_load_can_get_var_from_dot_path()
    {
        $config = new Repository;

        $reader = $this->createMock(Reader::class);

        $reader
            ->expects($this->once())
            ->method('load')
            ->with('beer')
            ->willReturn(['stout' => 'Guinness']);

        $config->attach($reader);

        $this->assertSame('Guinness', $config->load('beer.stout'));
    }

    /**
     * If we've already loaded a config group then the correct variable
     * should be returned if we use the dot path notation to request
     * a var
     */
    #[Test]
    public function test_load_can_get_var_from_dot_path_for_loaded_group()
    {
        $config = new Repository;

        $reader = $this->createMock(Reader::class);

        $reader->expects($this->once())
            ->method('load')
            ->with('beer')
            ->willReturn(['stout' => 'Guinness']);

        $config->attach($reader);

        $config->load('beer');

        $this->assertSame('Guinness', $config->load('beer.stout'));
    }

    /**
     * If load() is called and there are no readers present then it should throw
     * a kohana exception
     */
    #[Test]
    public function test_load_throws_exception_if_there_are_no_sources()
    {
        $this->expectException(KohanaException::class);

        // The following code should throw an exception and phpunit will catch / handle it
        $config = new Repository;

        $config->load('random');
    }

    /**
     * If an empty group name is specified then an exception should be thrown.
     */
    #[Test]
    public function test_load_throws_exception_if_empty_group()
    {
        $this->expectException(KohanaException::class);

        $config = new Repository;

        $reader = $this->createStub(Reader::class);

        $config->attach($reader);

        $config->load('');
    }

    /**
     * Make sure that _write_config() passes the changed configuration to all
     * writers in the queue
     */
    #[Test]
    public function test_write_config_passes_changed_config_to_all_writers()
    {
        $config = new Repository;

        $reader1 = $this->createStub(Reader::class);
        $writer1 = $this->createMock(Writer::class);
        $writer2 = $this->createMock(Writer::class);

        $writer1->expects($this->once())
            ->method('write')
            ->with('some_group', 'key', 'value');

        $writer2->expects($this->once())
            ->method('write')
            ->with('some_group', 'key', 'value');

        $config->attach($reader1)->attach($writer1)->attach($writer2);

        $config->writeConfig('some_group', 'key', 'value');
    }

    /**
     * Config sources are stored in a stack, make sure that config at the bottom
     * of the stack is overridden by config at the top
     */
    #[Test]
    public function test_config_is_loaded_from_top_to_bottom_of_stack()
    {
        $group_name = 'lolumns';

        $reader1 = $this->getMockBuilder(Reader::class)
            ->setMockClassName('Unittest_Config_Reader_1')
            ->getMock();
        $reader2 = $this->getMockBuilder(Reader::class)
            ->setMockClassName('Unittest_Config_Reader_2')
            ->getMock();

        $reader1->expects($this->once())
            ->method('load')
            ->with($group_name)
            ->willReturn([
                    'foo' => 'bar',
                    'kohana' => 'awesome',
                    'life' => ['normal', 'fated']
        ]);

        $reader2->expects($this->once())
            ->method('load')
            ->with($group_name)
            ->willReturn([
                    'kohana' => 'sweet',
                    'music' => 'tasteful',
                    'life' => ['extraordinary', 'destined']
        ]);

        $config = new Repository;

        // Attach $reader1 at the "top" and reader2 at the "bottom"
        $config->attach($reader1)->attach($reader2, false);

        $this->assertSame([
            'kohana' => 'awesome',
            'music' => 'tasteful',
            'life' => ['extraordinary', 'destined', 'normal', 'fated'],
            'foo' => 'bar',
            ], $config->load($group_name)->asArray());
    }

    /**
     * load() should keep a record of what config groups have been requested and if
     * a group is requested more than once the first instance should be returned
     */
    #[Test]
    public function test_load_reuses_config_groups()
    {
        $reader = $this->createMock(Reader::class);

        $reader->expects($this->once())
            ->method('load')
            ->with('something')
            ->willReturn([]);

        $config = new Repository;

        $config->attach($reader);

        $group = $config->load('something');

        $this->assertSame($group, $config->load('something'));
    }

    /**
     * When we call copy() we expect it to copy the merged config to all writers
     *
     * @TODO This test sucks due to limitations in the phpunit mock generator.  MAKE THIS AWESOME AGAIN!
     */
    #[Test]
    public function test_copy_copies_merged_config_to_all_writers()
    {
        $config = new Repository;

        $reader1 = $this->createMock(Reader::class);
        $reader2 = $this->createMock(Reader::class);

        $reader1->expects($this->once())
            ->method('load')
            ->with('something')
            ->willReturn([
                    'pie' => 'good',
                    'kohana' => 'awesome'
        ]);

        $reader2->expects($this->once())
            ->method('load')
            ->with('something')
            ->willReturn(['kohana' => 'good']);

        $writer1 = $this->createMock(Writer::class);
        $writer2 = $this->createMock(Writer::class);

        // Due to crazy limitations in phpunit's mocking engine we have to be fairly
        // liberal here as to what order we receive the config items
        // Good news is that order shouldn't matter *yay*
        //
        // Now save your eyes and skip the next... 13 lines!
        $key = $this->logicalOr('pie', 'kohana');
        $val = $this->logicalOr('good', 'awesome');

        $writer1
            ->expects($this->exactly(2))
            ->method('write')
            ->with('something', clone $key, clone $val);

        $writer2
            ->expects($this->exactly(2))
            ->method('write')
            ->with('something', clone $key, clone $val);

        $config
            ->attach($reader1)->attach($reader2, false)
            ->attach($writer1)->attach($writer2);

        // Now let's get this thing going!
        $config->copy('something');
    }

}
