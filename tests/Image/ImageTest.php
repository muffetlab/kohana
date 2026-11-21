<?php

use Kohana\Foundation\Application;
use Kohana\Foundation\KohanaException;
use Kohana\Image\Image;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @package    Kohana/Image
 * @category   Test
 * @author     Kohana Team
 * @copyright  (c) 2009-2012 Kohana Team
 * @license    http://https://kohana.top/license
 */
#[Group('kohana')]
#[Group('kohana.image')]
class Kohana_ImageTest extends TestCase
{
    protected function setUp(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is not available.');
        }
    }

    /**
     * Tests the Image::save() method for files that don't have extensions
     *
     * @return  void
     * @throws KohanaException
     */
    public function test_save_without_extension()
    {
        $image = Image::factory(KOHANA_FIXTURES . '/test_image');
        $this->assertTrue($image->save(Application::$cacheDir . '/test_image'));

        unlink(Application::$cacheDir . '/test_image');
    }

}
