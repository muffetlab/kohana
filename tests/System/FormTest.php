<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Kohana\Testing\TestCase;
use Kohana\Html\Form;
use Kohana\Foundation\KohanaException;

/**
 * Tests Kohana Form helper
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
#[Group('kohana.core.form')]
class Kohana_FormTest extends TestCase
{
    /**
     * Defaults for this test
     * @var array
     */
    // @codingStandardsIgnoreStart
    protected $environmentDefault = [
        'Kohana\Foundation\Application::$baseUrl' => '/',
        'HTTP_HOST' => 'kohanaframework.org',
        'Kohana\Foundation\Application::$indexFile' => '',
    ];

    // @codingStandardsIgnoreEnd
    /**
     * Provides test data for test_open()
     *
     * @return array
     */
    public static function provider_open(): array
    {
        return [
            [
                ['', null],
                ['action' => '']
            ],
            [
                [null, null],
                ['action' => '']
            ],
            [
                ['foo', null],
                ['action' => '/foo']
            ],
            [
                ['foo', ['method' => 'get']],
                ['action' => '/foo', 'method' => 'get']
            ],
        ];
    }

    /**
     * Tests Form::open()
     *
     * @param array $input Input for Form::open
     * @param array $expected Output for Form::open
     * @throws KohanaException
     */
    #[DataProvider('provider_open')]
    #[Test]
    public function test_open(array $input, array $expected)
    {
        list($action, $attributes) = $input;

        $tag = Form::open($action, $attributes);

        $matcher = [
            'method' => 'post',
            'accept-charset' => 'utf-8',
        ];
        $matcher = $expected + $matcher;

        $selector = 'form';
        foreach ($matcher as $attr => $val) {
            $selector .= '[' . $attr . ($val !== null ? '="' . $val . '"' : '') . ']';
        }

        $this->assertSelectEquals($selector, null, true, $tag);
    }

    /**
     * Tests Form::close()
     */
    #[Test]
    public function test_close()
    {
        $this->assertSame('</form>', Form::close());
    }

    /**
     * Provides test data for test_input()
     *
     * @return array
     */
    public static function provider_input(): array
    {
        return [
            ['input', 'foo', 'bar', null],
            ['input', 'foo', null, null],
            ['hidden', 'foo', 'bar', null],
            ['password', 'foo', 'bar', null],
        ];
    }

    /**
     * Tests Form::input()
     *
     * @param string $type
     * @param string $name
     * @param string|null $value
     * @param array|null $attributes
     */
    #[DataProvider('provider_input')]
    #[Test]
    public function test_input(string $type, string $name, ?string $value, ?array $attributes)
    {
        $matcher = ['name' => $name, 'type' => $type];

        // Form::input creates a text input
        if ($type === 'input') {
            $matcher['type'] = 'text';
        }

        // null just means no value
        if ($value !== null) {
            $matcher['value'] = $value;
        }

        // Add on any attributes
        if (is_array($attributes)) {
            $matcher = $attributes + $matcher;
        }

        $selector = 'input';
        foreach ($matcher as $attr => $val) {
            $selector .= '[' . $attr . ($val !== null ? '="' . $val . '"' : '') . ']';
        }

        $tag = Form::$type($name, $value, $attributes);

        $this->assertSelectEquals($selector,null, true, $tag);
    }

    /**
     * Provides test data for test_file()
     *
     * @return array
     */
    public static function provider_file(): array
    {
        return [
            // $value, $result
            ['foo', null, '<input type="file" name="foo" />'],
        ];
    }

    /**
     * Tests Form::file()
     *
     * @param string $name
     * @param array|null $attributes
     * @param string $expected Output for Form::file
     */
    #[DataProvider('provider_file')]
    #[Test]
    public function test_file(string $name, ?array $attributes, string $expected)
    {
        $this->assertSame($expected, Form::file($name, $attributes));
    }

    /**
     * Provides test data for test_check()
     *
     * @return array
     */
    public static function provider_check(): array
    {
        return [
            // $value, $result
            ['checkbox', 'foo', null, false, null],
            ['checkbox', 'foo', null, true, null],
            ['checkbox', 'foo', 'bar', true, null],
            ['radio', 'foo', null, false, null],
            ['radio', 'foo', null, true, null],
            ['radio', 'foo', 'bar', true, null],
        ];
    }

    /**
     * Tests Form::check()
     *
     * @param string $type
     * @param string $name
     * @param string|null $value
     * @param bool $checked
     * @param array|null $attributes
     */
    #[DataProvider('provider_check')]
    #[Test]
    public function test_check(string $type, string $name, ?string $value, bool $checked, ?array $attributes)
    {
        $matcher = ['name' => $name, 'type' => $type];

        if ($value !== null) {
            $matcher['value'] = $value;
        }

        if (is_array($attributes)) {
            $matcher = $attributes + $matcher;
        }

        if ($checked === true) {
            $matcher['checked'] = 'checked';
        }

        $selector = 'input';
        foreach ($matcher as $attr => $val) {
            $selector .= '[' . $attr . ($val !== null ? '="' . $val . '"' : '') . ']';
        }

        $tag = Form::$type($name, $value, $checked, $attributes);

        $this->assertSelectEquals($selector,null, true, $tag);
    }

    /**
     * Provides test data for test_text()
     *
     * @return array
     */
    public static function provider_text(): array
    {
        return [
            ['textarea', 'foo', 'bar', null],
            ['textarea', 'foo', 'bar', ['rows' => 20, 'cols' => 20]],
            ['button', 'foo', 'bar', null],
            ['label', 'foo', 'bar', null],
            ['label', 'foo', null, null],
        ];
    }

    /**
     * Tests Form::textarea()
     *
     * @param string $type
     * @param string $name
     * @param string|null $body
     * @param array|null $attributes
     */
    #[DataProvider('provider_text')]
    #[Test]
    public function test_text(string $type, string $name, ?string $body, ?array $attributes)
    {
        $matcher = $type !== 'label' ? ['name' => $name] : ['for' => $name];

        if (is_array($attributes)) {
            $matcher = $attributes + $matcher;
        }

        $selector = $type;
        foreach ($matcher as $attr => $val) {
            $selector .= '[' . $attr . ($val !== null ? '="' . $val . '"' : '') . ']';
        }

        $tag = Form::$type($name, $body, $attributes);

        $this->assertSelectEquals($selector, $body, true, $tag);
    }

    /**
     * Provides test data for test_select()
     *
     * @return array
     */
    public static function provider_select(): array
    {
        return [
            // $value, $result
            [
                'foo',
                null,
                null,
                "<select name=\"foo\"></select>"
            ],
            [
                'foo',
                ['bar' => 'bar'],
                null,
                "<select name=\"foo\">\n<option value=\"bar\">bar</option>\n</select>"
            ],
            [
                'foo',
                ['bar' => 'bar'],
                'bar',
                "<select name=\"foo\">\n<option value=\"bar\" selected=\"selected\">bar</option>\n</select>"
            ],
            [
                'foo',
                ['bar' => ['foo' => 'bar']],
                null,
                "<select name=\"foo\">\n<optgroup label=\"bar\">\n<option value=\"foo\">bar</option>\n</optgroup>\n</select>"
            ],
            [
                'foo',
                ['bar' => ['foo' => 'bar']],
                'foo',
                "<select name=\"foo\">\n<optgroup label=\"bar\">\n<option value=\"foo\" selected=\"selected\">bar</option>\n</optgroup>\n</select>"
            ],
            // #2286
            [
                'foo',
                ['bar' => 'bar', 'unit' => 'test', 'foo' => 'foo'],
                ['bar', 'foo'],
                "<select name=\"foo\" multiple=\"multiple\">\n<option value=\"bar\" selected=\"selected\">bar</option>\n<option value=\"unit\">test</option>\n<option value=\"foo\" selected=\"selected\">foo</option>\n</select>"
            ],
        ];
    }

    /**
     * Tests Form::select()
     *
     * @param string $name
     * @param array|null $options
     * @param mixed $selected
     * @param string $expected Output for Form::select
     */
    #[DataProvider('provider_select')]
    #[Test]
    public function test_select(string $name, ?array $options, $selected, string $expected)
    {
        // Much more efficient just to assertSame() rather than assertTag() on each element
        $this->assertSame($expected, Form::select($name, $options, $selected));
    }

    /**
     * Provides test data for test_submit()
     *
     * @return array
     */
    public static function provider_submit(): array
    {
        return [
            ['foo', 'Foobar!'],
        ];
    }

    /**
     * Tests Form::submit()
     *
     * @param string $name
     * @param string $value
     */
    #[DataProvider('provider_submit')]
    #[Test]
    public function test_submit(string $name, string $value)
    {
        $matcher = [
            'name' => $name,
            'type' => 'submit',
            'value' => $value
        ];

        $selector = 'input';
        foreach ($matcher as $attr => $val) {
            $selector .= '[' . $attr . ($val !== null ? '="' . $val . '"' : '') . ']';
        }

        $this->assertSelectEquals($selector, null, true, Form::submit($name, $value));
    }

    /**
     * Provides test data for test_image()
     *
     * @return array
     */
    public static function provider_image(): array
    {
        return [
            // $value, $result
            [
                'foo',
                'bar',
                ['src' => 'media/img/login.png'],
                '<input type="image" name="foo" value="bar" src="/media/img/login.png" />'
            ],
        ];
    }

    /**
     * Tests Form::image()
     *
     * @param string $name The name attribute for the image input.
     * @param string $value The value attribute for the image input.
     * @param array $attributes Additional HTML attributes for the image input.
     * @param string $expected The expected output from Form::image().
     * @throws KohanaException
     */
    #[DataProvider('provider_image')]
    #[Test]
    public function test_image(string $name, string $value, array $attributes, string $expected)
    {
        $this->assertSame($expected, Form::image($name, $value, $attributes));
    }

    /**
     * Provides test data for test_label()
     *
     * @return array
     */
    static function provider_label(): array
    {
        return [
            // $value, $result
            // Single for provided
            [
                'email',
                null,
                null,
                '<label for="email">Email</label>'
            ],
            [
                'email_address',
                null,
                null,
                '<label for="email_address">Email Address</label>'
            ],
            [
                'email-address',
                null,
                null,
                '<label for="email-address">Email Address</label>'
            ],
            // For and text values provided
            [
                'name',
                'First name',
                null,
                '<label for="name">First name</label>'
            ],
            // with attributes
            [
                'lastname',
                'Last name',
                ['class' => 'text'],
                '<label class="text" for="lastname">Last name</label>'
            ],
            [
                'lastname',
                'Last name',
                ['class' => 'text', 'id' => 'txt_lastname'],
                '<label id="txt_lastname" class="text" for="lastname">Last name</label>'
            ],
        ];
    }

    /**
     * Tests Form::label()
     *
     * @param string $for The "for" attribute of the label.
     * @param string|null $text The text content of the label.
     * @param array|null $attributes Additional HTML attributes for the label.
     * @param string $expected The expected output from Form::label().
     */
    #[DataProvider('provider_label')]
    #[Test]
    function test_label(string $for, ?string $text, ?array $attributes, string $expected)
    {
        $this->assertSame($expected, Form::label($for, $text, $attributes));
    }

}
