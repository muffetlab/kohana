<?php

use Kohana\Filesystem\File;
use Kohana\Foundation\KohanaException;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests Kohana File helper
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @author     Jeremy Bush <contractfrombelow@gmail.com>
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
#[Group('kohana')]
#[Group('kohana.core')]
#[Group('kohana.core.file')]
class Kohana_FileTest extends TestCase
{
    /**
     * Provides test data for test_sanitize()
     *
     * @return array
     */
    public static function provider_mime(): array
    {
        return [
            // $value, $result
            [
                KOHANA_FIXTURES . '/github.png',
                'image/png',
            ],
        ];
    }

    /**
     * Tests File::mime()
     *
     * @param mixed $input Input for File::mime
     * @param string $expected Output for File::mime
     * @throws KohanaException
     */
    #[DataProvider('provider_mime')]
    #[Test]
    public function test_mime($input, string $expected)
    {
        //@todo: File::mime coverage needs significant improvement or to be dropped for a composer package - it's a "horribly unreliable" method with very little testing
        $this->assertSame($expected, File::mime($input));
    }

    /**
     * Provides test data for test_split_join()
     *
     * @return array
     */
    public static function provider_split_join(): array
    {
        return [
            // $value, $result
            [KOHANA_FIXTURES . '/github.png', .01, 1],
        ];
    }

    /**
     * Tests File::mime()
     *
     * @param array|string $input Input for File::split
     * @param int $peices Input for File::split
     * @param int $expected Output for File::split
     */
    #[DataProvider('provider_split_join')]
    #[Test]
    public function test_split_join($input, $peices, $expected)
    {
        $this->assertSame($expected, File::split($input, $peices));
        $this->assertSame($expected, File::join($input));

        foreach (glob(KOHANA_FIXTURES . '/github.png' . '.*') as $file) {
            unlink($file);
        }
    }

}
