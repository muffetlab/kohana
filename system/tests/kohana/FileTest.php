<?php

use Kohana\Testing\TestCase;
use Kohana\Foundation\Application;
use Kohana\Filesystem\File;
use Kohana\Foundation\KohanaException;

/**
 * Tests Kohana File helper
 *
 * @group kohana
 * @group kohana.core
 * @group kohana.core.file
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @author     Jeremy Bush <contractfrombelow@gmail.com>
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class Kohana_FileTest extends TestCase
{
    /**
     * Provides test data for test_sanitize()
     *
     * @return array
     */
    public function provider_mime(): array
    {
        return [
            // $value, $result
            [
                Application::findFile('tests', 'test_data/github', 'png'),
                'image/png'
            ],
        ];
    }

    /**
     * Tests File::mime()
     *
     * @test
     * @dataProvider provider_mime
     * @param mixed $input Input for File::mime
     * @param string $expected Output for File::mime
     * @throws KohanaException
     */
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
    public function provider_split_join(): array
    {
        return [
            // $value, $result
            [Application::findFile('tests', 'test_data/github', 'png'), .01, 1],
        ];
    }

    /**
     * Tests File::mime()
     *
     * @test
     * @dataProvider provider_split_join
     * @param array|string $input Input for File::split
     * @param int $peices Input for File::split
     * @param int $expected Output for File::split
     */
    public function test_split_join($input, $peices, $expected)
    {
        $this->assertSame($expected, File::split($input, $peices));
        $this->assertSame($expected, File::join($input));

        foreach (glob(Application::findFile('tests', 'test_data/github', 'png') . '.*') as $file) {
            unlink($file);
        }
    }

}
