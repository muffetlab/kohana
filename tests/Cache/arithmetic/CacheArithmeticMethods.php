<?php

use Kohana\Cache\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

include_once __DIR__ . '/../CacheBasicMethods.php';

/**
 * @package    Kohana/Cache
 * @category   Test
 * @author     Kohana Team
 * @copyright  (c) 2009-2012 Kohana Team
 * @license    https://kohana.top/license
 */
#[Group('kohana')]
#[Group('kohana.cache')]
abstract class Kohana_CacheArithmeticMethodsTest extends Kohana_CacheBasicMethodsTest
{
    public function tearDown(): void
    {
        parent::tearDown();

        // Cleanup
        $cache = $this->cache();

        if ($cache instanceof Cache) {
            $cache->deleteAll();
        }
    }

    /**
     * Provider for test_increment
     *
     * @return  array
     */
    public static function provider_increment(): array
    {
        return [
            [
                0,
                [
                    'id' => 'increment_test_1',
                    'step' => 1,
                ],
                1,
            ],
            [
                1,
                [
                    'id' => 'increment_test_2',
                    'step' => 1,
                ],
                2,
            ],
            [
                5,
                [
                    'id' => 'increment_test_3',
                    'step' => 5,
                ],
                10,
            ],
            [
                null,
                [
                    'id' => 'increment_test_4',
                    'step' => 1,
                ],
                false,
            ],
        ];
    }

    /**
     * Test for [Cache_Arithmetic::increment()]
     *
     *
     * @param int|null $start_state Start state
     * @param array $inc_args increment arguments
     * @return  void
     */
    #[DataProvider('provider_increment')]
    public function test_increment(?int $start_state, array $inc_args, $expected)
    {
        $cache = $this->cache();

        if ($start_state !== null) {
            $cache->set($inc_args['id'], $start_state, 0);
        }

        $this->assertSame(
            $expected,
            $cache->increment(
                $inc_args['id'],
                $inc_args['step']
            )
        );
    }

    /**
     * Provider for test_decrement
     *
     * @return  array
     */
    public static function provider_decrement(): array
    {
        return [
            [
                10,
                [
                    'id' => 'decrement_test_1',
                    'step' => 1,
                ],
                9,
            ],
            [
                10,
                [
                    'id' => 'decrement_test_2',
                    'step' => 2,
                ],
                8,
            ],
            [
                50,
                [
                    'id' => 'decrement_test_3',
                    'step' => 5,
                ],
                45,
            ],
            [
                null,
                [
                    'id' => 'decrement_test_4',
                    'step' => 1,
                ],
                false,
            ],
        ];
    }

    /**
     * Test for [Cache_Arithmetic::decrement()]
     *
     *
     * @param int|null $start_state Start state
     * @param array $dec_args decrement arguments
     * @param mixed $expected Expected result
     * @return  void
     */
    #[DataProvider('provider_decrement')]
    public function test_decrement(?int $start_state, array $dec_args, $expected)
    {
        $cache = $this->cache();

        if ($start_state !== null) {
            $cache->set($dec_args['id'], $start_state, 0);
        }

        $this->assertSame(
            $expected,
            $cache->decrement(
                $dec_args['id'],
                $dec_args['step']
            )
        );
    }

}
