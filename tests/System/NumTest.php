<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use Kohana\Testing\TestCase;
use Kohana\Support\Number;
use Kohana\Foundation\KohanaException;

/**
 * Tests Num
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @author     BRMatt <matthew@sigswitch.com>
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
#[Group('kohana')]
#[Group('kohana.core')]
#[Group('kohana.core.num')]
class Kohana_NumTest extends TestCase
{
    protected $default_locale;

    /**
     * SetUp test environment
     */
    // @codingStandardsIgnoreStart
    public function setUp(): void
    // @codingStandardsIgnoreEnd
    {
        parent::setUp();

        $this->default_locale = setlocale(LC_ALL, 0);
        setlocale(LC_ALL, 'en_US.utf8');
    }

    /**
     * Tear down environment
     */
    // @codingStandardsIgnoreStart
    public function tearDown(): void
    // @codingStandardsIgnoreEnd
    {
        parent::tearDown();

        setlocale(LC_ALL, $this->default_locale);
    }

    /**
     * Provides test data for test_bytes()
     *
     * @return array
     */
    public static function provider_bytes(): array
    {
        return [
            [204800.0, '200K'],
            [5242880.0, '5MiB'],
            [1000.0, 1000],
            [2684354560.0, '2.5GB'],
        ];
    }

    /**
     * Tests Num::bytes()
     *
     * @param float $expected Expected Value
     * @param string $size Input value
     * @throws KohanaException
     */
    #[DataProvider('provider_bytes')]
    #[Test]
    public function test_bytes($expected, $size)
    {
        $this->assertSame($expected, Number::bytes($size));
    }

    /**
     * Provides test data for test_ordinal()
     * @return array
     */
    public static function provider_ordinal(): array
    {
        return [
            [0, 'th'],
            [1, 'st'],
            [21, 'st'],
            [112, 'th'],
            [23, 'rd'],
            [42, 'nd'],
        ];
    }

    /**
     *
     * @param int $number
     * @param string $expected
     */
    #[DataProvider('provider_ordinal')]
    #[Test]
    public function test_ordinal(int $number, string $expected)
    {
        $this->assertSame($expected, Number::ordinal($number));
    }

    /**
     * Provides test data for test_format()
     * @return array
     */
    public static function provider_format(): array
    {
        return [
            // English
            [10000, 2, false, '10,000.00'],
            [10000, 2, true, '10,000.00'],
            // Additional dp's should be removed
            [123.456, 2, false, '123.46'],
            [123.456, 2, true, '123.46'],
        ];
    }

    /**
     * @param float $number
     * @param int $places
     * @param bool $monetary
     * @param string $expected
     * @todo test locales
     */
    #[DataProvider('provider_format')]
    #[RequiresOperatingSystem('Linux')]
    #[Test]
    public function test_format(float $number, int $places, bool $monetary, string $expected)
    {
        $this->assertSame($expected, Number::format($number, $places, $monetary));
    }

    /**
     * Provides data for test_round()
     * @return array
     */
    static function provider_round(): array
    {
        return [
            [5.5, 0, [6.0, 5.0, 6.0, 5.0]],
            [42.5, 0, [43.0, 42.0, 42.0, 43.0]],
            [10.4, 0, [10.0, 10.0, 10.0, 10.0]],
            [10.8, 0, [11.0, 11.0, 11.0, 11.0]],
            [-5.5, 0, [-6.0, -5.0, -6.0, -5.0]],
            [-10.5, 0, [-11.0, -10.0, -10.0, -11.0]],
            [26.12375, 4, [26.1238, 26.1237, 26.1238, 26.1237]],
            [26.12325, 4, [26.1233, 26.1232, 26.1232, 26.1233]],
        ];
    }

    /**
     * @param number $input
     * @param int $precision
     * @param number $expected
     */
    #[DataProvider('provider_round')]
    #[Test]
    function test_round($input, int $precision, $expected)
    {
        foreach ([Number::ROUND_HALF_UP, Number::ROUND_HALF_DOWN, Number::ROUND_HALF_EVEN, Number::ROUND_HALF_ODD] as $i => $mode) {
            $this->assertSame($expected[$i], Number::round($input, $precision, $mode, false));
        }
    }

}
