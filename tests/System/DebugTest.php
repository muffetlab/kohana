<?php

use Kohana\Foundation\Debug;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests Kohana Core
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @author     Jeremy Bush <contractfrombelow@gmail.com>
 * @copyright  (c) 2008-2014 Kohana Team
 * @license    https://kohana.top/license
 */
#[Group('kohana')]
#[Group('kohana.core')]
#[Group('kohana.core.debug')]
class Kohana_DebugTest extends TestCase
{
    /**
     * Provides test data for test_debug()
     *
     * @return array
     */
    public static function provider_vars(): array
    {
        return [
            // $thing, $expected
            [
                ['foobar'],
                "<pre class=\"debug\"><small>array</small><span>(1)</span> <span>(\n    0 => <small>string</small><span>(6)</span> \"foobar\"\n)</span></pre>",
            ],
        ];
    }

    /**
     * Tests Debug::vars()
     *
     * @param mixed $thing The thing to debug
     * @param string $expected Output for Debug::vars
     */
    #[DataProvider('provider_vars')]
    #[Test]
    public function test_var($thing, string $expected)
    {
        $this->assertEquals($expected, Debug::vars($thing));
    }

    /**
     * Provides test data for testDebugPath()
     *
     * @return array
     */
    public static function provider_debug_path(): array
    {
        return [
            [
                SYSPATH . 'classes' . DIRECTORY_SEPARATOR . 'Kohana.php',
                'SYSPATH' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Kohana.php',
            ],
            [
                MODPATH . static::dirSeparator('unittest/classes/kohana/unittest/runner.php'),
                static::dirSeparator('MODPATH/unittest/classes/kohana/unittest/runner.php'),
            ],
        ];
    }

    /**
     * Tests Debug::path()
     *
     * @param string $path Input for Debug::path
     * @param string $expected Output for Debug::path
     */
    #[DataProvider('provider_debug_path')]
    #[Test]
    public function test_debug_path(string $path, string $expected)
    {
        $this->assertEquals($expected, Debug::path($path));
    }

    /**
     * Provides test data for test_dump()
     *
     * @return array
     */
    public static function provider_dump(): array
    {
        return [
            [
                'foobar',
                128,
                10,
                '<small>string</small><span>(6)</span> "foobar"',
            ],
            [
                'foobar',
                2,
                10,
                '<small>string</small><span>(6)</span> "fo&nbsp;&hellip;"',
            ],
            [
                null,
                128,
                10,
                '<small>NULL</small>',
            ],
            [
                true,
                128,
                10,
                '<small>bool</small> TRUE',
            ],
            [
                ['foobar'],
                128,
                10,
                "<small>array</small><span>(1)</span> <span>(\n    0 => <small>string</small><span>(6)</span> \"foobar\"\n)</span>",
            ],
            [
                new StdClass(),
                128,
                10,
                "<small>object</small> <span>stdClass(0)</span> <code>{\n}</code>",
            ],
            [
                "fo\x6F\xFF\x00bar\x8F\xC2\xB110",
                128,
                10,
                '<small>string</small><span>(10)</span> "foobar±10"',
            ],
            [
                ['level1' => ['level2' => ['level3' => ['level4' => ['value' => 'something']]]]],
                128,
                4,
                '<small>array</small><span>(1)</span> <span>(
    "level1" => <small>array</small><span>(1)</span> <span>(
        "level2" => <small>array</small><span>(1)</span> <span>(
            "level3" => <small>array</small><span>(1)</span> <span>(
                "level4" => <small>array</small><span>(1)</span> (
                    ...
                )
            )</span>
        )</span>
    )</span>
)</span>',
            ],
        ];
    }

    /**
     * Tests Debug::dump()
     *
     * @param mixed $input
     * @param int $length
     * @param int $limit
     * @param string $expected expected output
     */
    #[DataProvider('provider_dump')]
    #[Test]
    public function test_dump($input, int $length, int $limit, string $expected)
    {
        $this->assertEquals($expected, Debug::dump($input, $length, $limit));
    }

}
