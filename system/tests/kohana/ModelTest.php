<?php

use Kohana\Model;
use Kohana\Unittest\TestCase;

/**
 * This test only really exists for code coverage.
 *
 * @group kohana
 * @group kohana.core
 * @group kohana.core.model
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @author     BRMatt <matthew@sigswitch.com>
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class Kohana_ModelTest extends TestCase
{
    /**
     * Test the model's factory.
     *
     * @test
     * @covers Model::factory
     */
    public function test_create()
    {
        $foobar = Model::factory('Foobar');

        $this->assertTrue($foobar instanceof Model);
    }

}

class Model_Foobar extends Model
{

}

// PSR-4 bridge so \Kohana\Model::factory('Foobar') can resolve this test class.
class_alias('Model_Foobar', 'Kohana\Model\Foobar');
