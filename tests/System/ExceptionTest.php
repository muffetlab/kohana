<?php

use Kohana\Foundation\KohanaException;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests Kohana Exception Class
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
#[Group('kohana')]
#[Group('kohana.core')]
#[Group('kohana.core.exception')]
class Kohana_ExceptionTest extends TestCase
{
    /**
     * Provides test data for test_constructor()
     *
     * @return array
     */
    public static function provider_constructor(): array
    {
        return [
            [[''], '', 0],
            [[':a'], ':a', 0],
            [[':a', null], ':a', 0],
            [[':a', []], ':a', 0],
            [[':a', [':a' => 'b']], 'b', 0],
            [[':a :b', [':a' => 'c', ':b' => 'd']], 'c d', 0],
            [[':a', null, 5], ':a', 5],
            // #3358
            [[':a', null, '3F000'], ':a', '3F000'],
            // #3404
            [[':a', null, '42S22'], ':a', '42S22'],
            // #3927
            [[':a', null, 'b'], ':a', 'b'],
            // #4039
            [[':a', null, '25P01'], ':a', '25P01'],
        ];
    }

    /**
     * Tests Kohana_Kohana_Exception::__construct()
     *
     * @param array $arguments Arguments
     * @param string $expected_message Value from getMessage()
     * @param int|string $expected_code Value from getCode()
     */
    #[DataProvider('provider_constructor')]
    #[Test]
    public function test_constructor(array $arguments, string $expected_message, $expected_code)
    {
        switch (count($arguments)) {
            case 1:
                $exception = new KohanaException(reset($arguments));
                break;
            case 2:
                $exception = new KohanaException(reset($arguments), next($arguments));
                break;
            default:
                $exception = new KohanaException(reset($arguments), next($arguments), next($arguments));
        }

        $this->assertSame($expected_code, $exception->getCode());
        $this->assertSame($expected_message, $exception->getMessage());
    }

    /**
     * Provides test data for test_text()
     *
     * @return array
     */
    public static function provider_text(): array
    {
        return [
            [new KohanaException('foobar'), 'Kohana\Foundation\KohanaException [ 0 ]: foobar ~ ' . __FILE__ . ' [ ' . __LINE__ . ' ]'],
        ];
    }

    /**
     * Tests Kohana_Exception::text()
     *
     * @param Exception $exception Exception to test
     * @param string $expected  expected output
     */
    #[DataProvider('provider_text')]
    #[Test]
    public function test_text(Exception $exception, string $expected)
    {
        $this->assertEquals($expected, KohanaException::text($exception));
    }

}
