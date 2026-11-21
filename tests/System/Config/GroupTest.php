<?php

use Kohana\Config\Group;
use Kohana\Config\Repository;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests the Config group lib
 *
 * @group kohana
 * @group kohana.config
 *
 * @package    Unittest
 * @author     Kohana Team
 * @author     Jeremy Bush <contractfrombelow@gmail.com>
 * @author     Matt Button <matthew@sigswitch.com>
 * @copyright  (c) 2008-2014 Kohana Team
 * @license    https://kohana.top/license
 */
class Kohana_Config_GroupTest extends TestCase
{
    /**
     * Create a mock Kohana_Config instance
     *
     * @return Repository
     */
    public function get_mock_config(): Repository
    {
        return new Repository();
    }

    /**
     * Gets a fresh instance of Kohana_Config_Group
     *
     * @param string $group Config Group name
     * @param array $config Configuration
     * @param Repository|null $instance Instance of Kohana_Config
     * @return Group
     */
    public function get_mock_group(string $group, array $config = [], ?Repository $instance = null): Group
    {
        if ($instance === null) {
            $instance = $this->get_mock_config();
        }

        return new Group($instance, $group, $config);
    }

    /**
     * The group name and group's config values should be loaded into the object
     * by the constructor
     */
    #[Test]
    public function test_loads_group_name_and_values_in_constructor()
    {
        $group_name = 'information';
        $group_values = ['var' => 'value'];

        $group = $this->get_mock_group($group_name, $group_values);

        // Now usually we'd just use assertAttributeSame, but that tries to get at protected properties
        // by casting the object in question into an array.  This usually works fine, but as Kohana_Config_Group
        // is a subclass of ArrayObject, casting to an array returns the config items!
        // Therefore, we have to use this little workaround
        $this->assertSame($group_name, $group->groupName());
        $this->assertSame($group_values, $group->getArrayCopy());
    }

    /**
     * A config group may not exist (or may not have any values) when it is loaded.
     * The config group should allow for this situation and not complain
     */
    #[Test]
    public function test_allows_empty_group_values()
    {
        $group = $this->get_mock_group('informatica');

        $this->assertSame([], $group->getArrayCopy());
    }

    /**
     * When get() is called it should fetch the config value specified
     */
    #[Test]
    public function test_get_fetches_config_value()
    {
        $group = $this->get_mock_group('kohana', ['status' => 'awesome']);

        $this->assertSame('awesome', $group->get('status'));
    }

    /**
     * If a config option does not exist then get() should return the default value, which is
     * null by default
     */
    #[Test]
    public function test_get_returns_default_value_if_config_option_dnx()
    {
        $group = $this->get_mock_group('kohana');

        $this->assertSame(null, $group->get('problems'));
        $this->assertSame('nada', $group->get('problems', 'nada'));
    }

    /**
     * We should be able to modify existing configuration items using set()
     */
    #[Test]
    public function test_set_modifies_existing_config()
    {
        $group = $this->get_mock_group('kohana', ['status' => 'pre-awesome']);

        $group->set('status', 'awesome');

        $this->assertSame('awesome', $group->get('status'));
    }

    /**
     * If we modify the config via set() [$var] or ->$var then the change should be passed to
     * the parent config instance so that the config writers can be notified.
     *
     * The modification to the config should also stick
     */
    #[Test]
    public function test_writes_changes_to_config()
    {
        $mock = $this->createMock(Repository::class);

        $mock->expects($this->exactly(3))
            ->method('writeConfig')
            ->with('kohana', 'status', $this->LogicalOr('totally', 'maybe', 'not'));

        $group = $this->get_mock_group('kohana', ['status' => 'kool'], $mock);

        $group['status'] = 'totally';

        $group->status = 'maybe';

        $group->set('status', 'not');
    }

    /**
     * Calling as_array() should return the full array, inc. any modifications
     */
    #[Test]
    public function test_as_array_returns_full_array()
    {
        $config = $this->get_mock_group('something', ['var' => 'value']);

        $this->assertSame(['var' => 'value'], $config->asArray());

        // Now change some vars **ahem**
        $config->var = 'LOLCAT';
        $config->lolcat = 'IN UR CODE';

        $this->assertSame([
            'var' => 'LOLCAT',
            'lolcat' => 'IN UR CODE',
            ], $config->asArray());

        // And if we remove an item it should be removed from the exported array
        unset($config['lolcat']);
        $this->assertSame(['var' => 'LOLCAT'], $config->asArray());
    }

    /**
     * Casting the object to a string should serialize the output of as_array
     */
    #[Test]
    public function test_to_string_serializes_array_output()
    {
        $vars = ['kohana' => 'cool', 'unit_tests' => 'boring'];
        $config = $this->get_mock_group('hehehe', $vars);

        $this->assertSame(serialize($vars), (string) $config);
    }

}
