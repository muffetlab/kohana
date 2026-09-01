<?php

/**
 * Tests Minion_CLI::options().
 *
 * @package    Kohana/Minion
 * @group      kohana
 * @group      kohana.minion
 * @group      kohana.minion.cli
 * @author     Loong <loong2460@gmail.com>
 * @copyright  (c) 2026 Kohana Group
 * @license    https://kohana.top/license
 */
class Minion_CLI_OptionsTest extends Kohana_Unittest_TestCase
{
    /**
     * @var array Backup of $_SERVER['argv']
     */
    protected $argvBackup;

    /**
     * @var array Backup of $_SERVER['argc']
     */
    protected $argcBackup;

    /**
     * Back up CLI arguments before each test.
     */
    public function setUp()
    {
        parent::setUp();

        $this->argvBackup = $_SERVER['argv'] ?? null;
        $this->argcBackup = $_SERVER['argc'] ?? null;
    }

    /**
     * Restore CLI arguments after each test.
     */
    public function tearDown()
    {
        if ($this->argvBackup !== null) {
            $_SERVER['argv'] = $this->argvBackup;
        } else {
            unset($_SERVER['argv']);
        }

        if ($this->argcBackup !== null) {
            $_SERVER['argc'] = $this->argcBackup;
        } else {
            unset($_SERVER['argc']);
        }

        parent::tearDown();
    }

    /**
     * Helper: set fake CLI arguments.
     *
     * @param array $argv Argument list (without the leading file name, which is prepended automatically)
     */
    protected function setArgv(array $argv)
    {
        array_unshift($argv, 'index.php');
        $_SERVER['argv'] = $argv;
        $_SERVER['argc'] = count($argv);
    }

    /**
     * Provides test data for testNoArgumentsReturnsAllOptions().
     *
     * @return array
     */
    public function providerNoArgumentsReturnsAllOptions(): array
    {
        return [
            // single --key=value
            [
                ['--task=db:upgrade'],
                ['task' => 'db:upgrade'],
            ],
            // multiple --key=value
            [
                ['--task=db:upgrade', '--force=1'],
                ['task' => 'db:upgrade', 'force' => '1'],
            ],
            // flag (no value)
            [
                ['--verbose'],
                ['verbose' => null],
            ],
            // mixed flags and key=value
            [
                ['--task=db:upgrade', '--verbose', '--force=1'],
                ['task' => 'db:upgrade', 'verbose' => null, 'force' => '1'],
            ],
            // positional argument
            [
                ['migrate'],
                [0 => 'migrate'],
            ],
            // mixed positional and named
            [
                ['--task=db:upgrade', 'migrate', '--force=1'],
                ['task' => 'db:upgrade', 0 => 'migrate', 'force' => '1'],
            ],
            // value with spaces (quoted on the CLI)
            [
                ['--name=John Doe'],
                ['name' => 'John Doe'],
            ],
        ];
    }

    /**
     * When called with no arguments, all parsed options should be returned.
     *
     * This is the core bug fix: previously, the filtering loop ran unconditionally and stripped every parsed option,
     * returning [].
     *
     * @test
     * @covers       Minion_CLI::options
     * @dataProvider providerNoArgumentsReturnsAllOptions
     * @param array $argv CLI arguments (without file name)
     * @param array $expected Expected result
     */
    public function testNoArgumentsReturnsAllOptions(array $argv, array $expected)
    {
        $this->setArgv($argv);
        $this->assertSame($expected, Minion_CLI::options());
    }

    /**
     * When called with no arguments and no CLI args, an empty array is returned.
     *
     * @test
     * @covers Minion_CLI::options
     */
    public function testNoArgumentsNoArgsReturnsEmptyArray()
    {
        $this->setArgv([]);
        $this->assertSame([], Minion_CLI::options());
    }

    /**
     * The first argument (file name) is always skipped.
     *
     * @test
     * @covers Minion_CLI::options
     */
    public function testFirstArgIsSkipped()
    {
        $this->setArgv([]);
        // argv is ['index.php'], loop starts at i=1, so nothing is parsed
        $this->assertSame([], Minion_CLI::options());
    }

    /**
     * Provides test data for testSingleRequestedOption().
     *
     * @return array
     */
    public function providerSingleRequestedOption(): array
    {
        return [
            // existing option — returns the value (string, not array)
            [
                ['--task=db:upgrade', '--force=1'],
                'force',
                '1',
            ],
            // flag option — returns null
            [
                ['--verbose', '--task=db:upgrade'],
                'verbose',
                null,
            ],
            // non-existent option — returns null
            [
                ['--task=db:upgrade'],
                'missing',
                null,
            ],
        ];
    }

    /**
     * When a single option is requested, its value is returned directly (not wrapped in an array).
     *
     * @test
     * @covers       Minion_CLI::options
     * @dataProvider providerSingleRequestedOption
     * @param array $argv CLI arguments (without file name)
     * @param string $requested Option name to request
     * @param string|null $expected Expected value
     */
    public function testSingleRequestedOption(array $argv, string $requested, ?string $expected)
    {
        $this->setArgv($argv);
        $this->assertSame($expected, Minion_CLI::options($requested));
    }

    /**
     * When multiple options are requested, only those are returned as an array.
     *
     * @test
     * @covers Minion_CLI::options
     */
    public function testMultipleRequestedOptions()
    {
        $this->setArgv(['--task=db:upgrade', '--force=1', '--verbose']);
        $this->assertSame(['force' => '1', 'verbose' => null], Minion_CLI::options('force', 'verbose'));
    }

    /**
     * Requested options that don't exist are omitted from the result.
     *
     * @test
     * @covers Minion_CLI::options
     */
    public function testUnrequestedOptionsFilteredOut()
    {
        $this->setArgv(['--task=db:upgrade', '--force=1', 'positional']);
        $result = Minion_CLI::options('task', 'missing');
        $this->assertSame(['task' => 'db:upgrade'], $result);
    }

    /**
     * When no options are requested, positional args are included in the result.
     *
     * @test
     * @covers Minion_CLI::options
     */
    public function testNoArgumentsIncludesPositionalArgs()
    {
        $this->setArgv(['migrate', '--task=db:upgrade']);
        $this->assertSame([0 => 'migrate', 'task' => 'db:upgrade'], Minion_CLI::options());
    }

    /**
     * When a single option is requested, positional args do not interfere.
     *
     * @test
     * @covers Minion_CLI::options
     */
    public function testRequestedOptionsExcludesPositionalArgs()
    {
        $this->setArgv(['migrate', '--task=db:upgrade']);
        $this->assertSame('db:upgrade', Minion_CLI::options('task'));
    }
}
