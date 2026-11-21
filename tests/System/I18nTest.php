<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Kohana\Testing\TestCase;
use Kohana\Translation\Translator;

/**
 * Tests Kohana i18n class
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
#[Group('kohana.core.i18n')]
class Kohana_I18nTest extends TestCase
{
    /**
     * Default values for the environment, see setEnvironment
     * @var array
     */
    // @codingStandardsIgnoreStart
    protected $environmentDefault = [
        'Kohana\Translation\Translator::$lang' => 'en-us',
    ];

    // @codingStandardsIgnoreEnd
    /**
     * Provides test data for test_lang()
     *
     * @return array
     */
    public static function provider_lang(): array
    {
        return [
            // $input, $expected_result
            [null, 'en-us'],
            ['es-es', 'es-es'],
        ];
    }

    /**
     * Tests I18n::lang()
     *
     * @param string|null $input Input for I18n::lang
     * @param string $expected Output for I18n::lang
     */
    #[DataProvider('provider_lang')]
    #[Test]
    public function test_lang(?string $input, string $expected)
    {
        $this->assertSame($expected, Translator::lang($input));
        $this->assertSame($expected, Translator::lang());
    }

    /**
     * Provides test data for test_get()
     *
     * @return array
     */
    public static function provider_get(): array
    {
        return [
            // $value, $result
            ['en-us', 'Hello, world!', 'Hello, world!'],
            ['es-es', 'Hello, world!', '¡Hola, mundo!'],
            ['fr-fr', 'Hello, world!', 'Bonjour, monde!'],
        ];
    }

    /**
     * Tests \Kohana\Translation\Translator::get()
     *
     * @param string $lang Language code to set
     * @param string $input Input for I18n::get
     * @param string $expected Output for I18n::get
     */
    #[DataProvider('provider_get')]
    #[Test]
    public function test_get(string $lang, string $input, string $expected)
    {
        Translator::lang($lang);
        $this->assertSame($expected, Translator::get($input));

        // Test immediate translation, issue #3085
        Translator::lang('en-us');
        $this->assertSame($expected, Translator::get($input, $lang));
    }

}
