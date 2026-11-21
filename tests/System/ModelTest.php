<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Kohana\Testing\TestCase;
use Kohana\Foundation\Model;

/**
 * This test only really exists for code coverage.
 *
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
#[Group('kohana.core.model')]
class Kohana_ModelTest extends TestCase
{
    /**
     * Test the model's factory.
     */
    #[Test]
    public function test_create()
    {
        $foobar = Model::factory('Foobar');

        $this->assertTrue($foobar instanceof Model);
    }

}
