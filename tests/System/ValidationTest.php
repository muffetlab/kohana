<?php

use Kohana\Foundation\KohanaException;
use Kohana\Testing\TestCase;
use Kohana\Translation\Translator;
use Kohana\Validation\Rules;
use Kohana\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Ticket;

/**
 * Tests the Validation lib that's shipped with Kohana
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
#[Group('kohana.core.validation')]
class Kohana_ValidationTest extends TestCase
{
    /**
     * Tests Validation::factory()
     *
     * Makes sure that the factory method returns an instance of Validation lib
     * and that it uses the variables passed
     */
    #[Test]
    public function test_factory_method_returns_instance_with_values()
    {
        $values = [
            'this' => 'something else',
            'writing tests' => 'sucks',
            'why the hell' => 'amIDoingThis',
        ];

        $instance = Validator::factory($values);

        $this->assertTrue($instance instanceof Validator);

        $this->assertSame(
            $values,
            $instance->data()
        );
    }

    /**
     * When we copy() a validation object, we should have a new validation object
     * with the exact same attributes, apart from the data, which should be the
     * same as the array we pass to copy()
     */
    #[Test]
    public function test_copy_copies_all_attributes_except_data()
    {
        $validation = new Validator([
            'foo' => 'bar',
            'fud' => 'fear, uncertainty, doubt',
            'num' => 9,
        ]);

        $validation->rule('num', 'is_int')->rule('foo', 'is_string');

        $copy_data = ['foo' => 'no', 'fud' => 'maybe', 'num' => 42];

        $copy = $validation->copy($copy_data);

        $this->assertNotSame($validation, $copy);

        foreach (['rules', 'bound', 'labels', 'emptyRules', 'errors'] as $attribute) {
            $reflection = new ReflectionClass($validation);
            $property = $reflection->getProperty($attribute);

            // This is just an easy way to check that the attributes are identical
            // Without hardcoding the expected values
            $this->assertSame($property->getValue($validation), $property->getValue($copy));
        }

        $this->assertSame($copy_data, $copy->data());
    }

    /**
     * When the validation object is initially created there should be no labels
     * specified
     */
    #[Test]
    public function test_initially_there_are_no_labels()
    {
        $validation = new Validator([]);

        $reflection = new ReflectionClass($validation);
        $property = $reflection->getProperty('labels');

        $this->assertSame([], $property->getValue($validation));
    }

    /**
     * Adding a label to a field should set it in the labels array
     * If the label already exists it should overwrite it
     *
     * In both cases the function should return a reference to $this
     */
    #[Test]
    public function test_label_adds_and_overwrites_label_and_returns_this()
    {
        $validation = new Validator([]);

        $this->assertSame($validation, $validation->label('email', 'Email Address'));

        $reflection = new ReflectionClass($validation);
        $property = $reflection->getProperty('labels');

        $this->assertSame(['email' => 'Email Address'], $property->getValue($validation));

        $this->assertSame($validation, $validation->label('email', 'Your Email'));

        $validation->label('name', 'Your Name');

        $this->assertSame(['email' => 'Your Email', 'name'  => 'Your Name'], $property->getValue($validation));
    }

    /**
     * Using labels() we should be able to add / overwrite multiple labels
     *
     * The function should also return $this for chaining purposes
     */
    #[Test]
    public function test_labels_adds_and_overwrites_multiple_labels_and_returns_this()
    {
        $validation = new Validator([]);
        $initial_data = ['kung fu' => 'fighting', 'fast' => 'cheetah'];

        $this->assertSame($validation, $validation->labels($initial_data));

        $reflection = new ReflectionClass($validation);
        $property = $reflection->getProperty('labels');

        $this->assertSame($initial_data, $property->getValue($validation));

        $this->assertSame($validation, $validation->labels([
                'fast' => 'lightning',
        ]));

        $this->assertSame(['fast' => 'lightning', 'kung fu' => 'fighting'], $property->getValue($validation));
    }

    /**
     * Using bind() we should be able to add / overwrite multiple bound variables
     *
     * The function should also return $this for chaining purposes
     */
    #[Test]
    public function test_bind_adds_and_overwrites_multiple_variables_and_returns_this()
    {
        $validation = new Validator([]);
        $bound = [':foo' => 'some value'];

        // Test binding an array of values
        $this->assertSame($validation, $validation->bind($bound));

        $reflection = new ReflectionClass($validation);
        $property = $reflection->getProperty('bound');

        $this->assertSame($bound, $property->getValue($validation));

        // Test binding one value
        $this->assertSame($validation, $validation->bind(':foo', 'some other value'));
        $this->assertSame([':foo' => 'some other value'], $property->getValue($validation));
    }

    /**
     * We should be able to used bound variables in callbacks
     */
    #[Test]
    public function test_bound_callback()
    {
        $data = [
            'kung fu' => 'fighting',
            'fast' => 'cheetah',
        ];
        $validation = new Validator($data);
        $validation->bind(':class', Rules::class)
            // Use the bound value in a callback
            ->rule('fast', [':class', 'maxLength'], [':value', 2]);

        // The rule should have run and check() should fail
        $this->assertFalse($validation->check());
    }

    /**
     * Provides test data for test_check
     *
     * @return array
     */
    public static function provider_check(): array
    {
        // $data_array, $rules, $labels, $first_expected, $expected_error
        return [
            [
                ['foo' => 'bar'],
                ['foo' => [['not_empty', null]]],
                [],
                true,
                [],
            ],
            [
                ['unit' => 'test'],
                [
                    'foo' => [['not_empty', null]],
                    'unit' => [['min_length', [':value', 6]]],
                ],
                [],
                false,
                [
                    'foo' => 'foo must not be empty',
                    'unit' => 'unit must be at least 6 characters long',
                ],
            ],
            [
                ['foo' => 'bar'],
                [
                    // Tests wildcard rules
                    true => [['min_length', [':value', 4]]],
                    'foo' => [
                        ['not_empty', null],
                        // Tests the array syntax for callbacks
                        [[Rules::class, 'exactLength'], [':value', 3]],
                        // Tests the Class::method syntax for callbacks
                        ['Kohana\Validation\Rules::exactLength', [':value', 3]],
                        // Tests the lambda function syntax for callbacks
                        [function ($value) {
                            return $value === 'bar';
                        }, [':value']],
                        // Tests using a function as a rule
                        ['is_string', [':value']],
                    ],
                ],
                [],
                false,
                ['foo' => 'foo must be at least 4 characters long'],
            ],
            // Switch things around and make :value an array
            [
                ['foo' => ['test', 'data']],
                ['foo' => [['in_array', ['kohana', ':value']]]],
                [],
                false,
                ['foo' => 'foo must be one of the available options'],
            ],
            // Test wildcard rules with no other rules
            [
                ['foo' => ['test']],
                [true => [['is_string', [':value']]]],
                ['foo' => 'foo'],
                false,
                ['foo' => '1.foo.is_string'],
            ],
            // Test array rules use method as error name
            [
                ['foo' => 'test'],
                ['foo' => [[[Rules::class, 'minLength'], [':value', 10]]]],
                [],
                false,
                ['foo' => 'foo must be at least 10 characters long'],
            ],
        ];
    }

    /**
     * Tests Validation::check()
     *
     * @param array $array The array of data
     * @param array $rules The array of rules
     * @param array $labels The array of labels
     * @param bool $expected Is it valid?
     * @param array $expected_errors Array of expected errors
     * @throws ReflectionException
     */
    #[DataProvider('provider_check')]
    #[Test]
    public function test_check(array $array, array $rules, array $labels, bool $expected, array $expected_errors)
    {
        $validation = new Validator($array);

        foreach ($labels as $field => $label) {
            $validation->label($field, $label);
        }

        foreach ($rules as $field => $field_rules) {
            foreach ($field_rules as $rule) {
                $validation->rule($field, $rule[0], $rule[1]);
            }
        }

        $status = $validation->check();
        $errors = $validation->errors(true);
        $this->assertSame($expected, $status);
        $this->assertSame($expected_errors, $errors);

        $validation = new Validator($array);
        foreach ($rules as $field => $fieldRules) {
            $validation->rules($field, $fieldRules);
        }
        $validation->labels($labels);

        $this->assertSame($expected, $validation->check());
    }

    /**
     * Tests Validation::check()
     */
    #[Test]
    public function test_check_stops_when_error_added_by_callback()
    {
        $validation = new Validator(['foo' => 'foo']);

        $validation
            ->rule('foo', [$this, '_validation_callback'], [':validation'])
            // This rule should never run
            ->rule('foo', 'min_length', [':value', 20]);

        $validation->check();
        $errors = $validation->errors();

        $expected = [
            'foo' => [0 => '_validation_callback', 1 => null],
        ];

        $this->assertSame($errors, $expected);
    }

    public function _validation_callback(Validator $object)
    {
        // Simply add the error
        $object->error('foo', '_validation_callback');
    }

    /**
     * Provides test data for test_errors()
     *
     * @return array
     */
    public static function provider_errors(): array
    {
        // [data, rules, expected], ...
        return [
            // No Error
            [
                ['username' => 'frank'],
                ['username' => [['not_empty', null]]],
                [],
            ],
            // Error from message file
            [
                ['username' => ''],
                ['username' => [['not_empty', null]]],
                ['username' => 'username must not be empty'],
            ],
            // No error message exists, display the path expected
            [
                ['username' => 'John'],
                ['username' => [['strpos', [':value', 'Kohana']]]],
                ['username' => 'Validation.username.strpos'],
            ],
        ];
    }

    /**
     * Tests Validation::errors()
     *
     * @param array $array The array of data
     * @param array $rules The array of rules
     * @param array $expected Array of expected errors
     * @throws ReflectionException
     */
    #[DataProvider('provider_errors')]
    #[Test]
    public function test_errors(array $array, array $rules, array $expected)
    {
        $validation = Validator::factory($array);

        foreach ($rules as $field => $field_rules) {
            $validation->rules($field, $field_rules);
        }

        $validation->check();

        $this->assertSame($expected, $validation->errors('Validation', false));
        // Should be able to get raw errors array
        $reflection = new ReflectionClass($validation);
        $property = $reflection->getProperty('errors');
        $this->assertSame($validation->errors(), $property->getValue($validation));
    }

    /**
     * Provides test data for test_translated_errors()
     *
     * @return array
     */
    public static function provider_translated_errors(): array
    {
        // [data, rules, expected], ...
        return [
            [
                ['Spanish' => ''],
                ['Spanish' => [['not_empty', null]]],
                // Errors are not translated yet so only the label will translate
                ['Spanish' => 'Español must not be empty'],
                ['Spanish' => 'Spanish must not be empty'],
            ],
        ];
    }

    /**
     * Tests Validation::errors()
     *
     * @param array $data The array of data to test
     * @param array $rules The array of rules to add
     * @param array $translated_expected The array of expected errors when translated
     * @param array $untranslated_expected The array of expected errors when not translated
     * @throws ReflectionException
     */
    #[DataProvider('provider_translated_errors')]
    #[Test]
    public function test_translated_errors(array $data, array $rules, array $translated_expected, array $untranslated_expected)
    {
        $validation = Validator::factory($data);

        $current = Translator::lang();
        Translator::lang('es');

        foreach ($rules as $field => $field_rules) {
            $validation->rules($field, $field_rules);
        }

        $validation->check();

        $result_1 = $validation->errors('Validation');
        $result_2 = $validation->errors('Validation', 'en');
        $result_3 = $validation->errors('Validation', false);

        // Restore the current language
        Translator::lang($current);

        $this->assertSame($translated_expected, $result_1);
        $this->assertSame($translated_expected, $result_2);
        $this->assertSame($untranslated_expected, $result_3);
    }

    /**
     * Tests Validation::errors()
     */
    #[Test]
    public function test_parameter_labels()
    {
        $validation = Validator::factory(['foo' => 'bar'])
            ->rule('foo', 'equals', [':value', 'something'])
            ->label('something', 'Spanish');

        $current = Translator::lang();
        Translator::lang('es');

        $validation->check();

        $translated_expected = ['foo' => 'foo must equal Español'];
        $untranslated_expected = ['foo' => 'foo must equal Spanish'];

        $result_1 = $validation->errors('Validation');
        $result_2 = $validation->errors('Validation', 'en');
        $result_3 = $validation->errors('Validation', false);

        // Restore the current language
        Translator::lang($current);

        $this->assertSame($translated_expected, $result_1);
        $this->assertSame($translated_expected, $result_2);
        $this->assertSame($untranslated_expected, $result_3);
    }

    /**
     * Tests Validation::errors()
     */
    #[Test]
    public function test_arrays_in_parameters()
    {
        $validation = Validator::factory(['foo' => 'bar'])
            ->rule('foo', 'equals', [':value', ['one', 'two']]);

        $validation->check();

        $expected = ['foo' => 'foo must equal one, two'];

        $this->assertSame($expected, $validation->errors('Validation', false));
    }

    /**
     * Tests Validation::check()
     */
    #[Test]
    public function test_data_stays_unaltered()
    {
        $validation = Validator::factory(['foo' => 'bar'])
            ->rule('something', 'not_empty');

        $before = $validation->data();
        $validation->check();
        $after = $validation->data();

        $expected = ['foo' => 'bar'];

        $this->assertSame($expected, $before);
        $this->assertSame($expected, $after);
    }

    /**
     * Tests Validation::errors()
     */
    #[Test]
    public function test_object_parameters_not_in_messages()
    {
        $validation = Validator::factory(['foo' => 'foo'])
            ->rule('bar', 'matches', [':validation', ':field', 'foo']);

        $validation->check();
        $errors = $validation->errors('validation');
        $expected = ['bar' => 'bar must be the same as foo'];

        $this->assertSame($expected, $errors);
    }

    /**
     * Tests Validation::data()
     */
    #[Test]
    public function test_data_returns_original_array()
    {
        $data = ['one' => 'hello', 'two' => 'world', 'ten' => ''];

        $validation = Validator::factory($data);

        $this->assertSame($data, $validation->data());
    }

    // @codingStandardsIgnoreStart
    public function test_offsetExists()
    // @codingStandardsIgnoreEnd
    {
        $array = ['one' => 'Hello', 'two' => 'World', 'ten' => null];

        $validation = Validator::factory($array);

        $this->assertTrue(isset($validation['one']));
        $this->assertFalse(isset($validation['ten']));
        $this->assertFalse(isset($validation['five']));
    }

    // @codingStandardsIgnoreStart
    public function test_offsetSet_throws_exception()
    // @codingStandardsIgnoreEnd
    {
        $this->expectException(KohanaException::class);

        $validation = Validator::factory([]);

        // Validation is read-only
        $validation['field'] = 'something';
    }

    // @codingStandardsIgnoreStart
    public function test_offsetGet()
    // @codingStandardsIgnoreEnd
    {
        $array = ['one' => 'Hello', 'two' => 'World', 'ten' => null];

        $validation = Validator::factory($array);

        $this->assertSame($array['one'], $validation['one']);
        $this->assertSame($array['two'], $validation['two']);
        $this->assertSame($array['ten'], $validation['ten']);
    }

    // @codingStandardsIgnoreStart
    public function test_offsetUnset()
    // @codingStandardsIgnoreEnd
    {
        $this->expectException(KohanaException::class);

        $validation = Validator::factory(['one' => 'Hello, World!']);

        // Validation is read-only
        unset($validation['one']);
    }

    /**
     * http://dev.kohanaframework.org/issues/4365
     */
    #[Test]
    public function test_error_type_check()
    {
        $array = ['email' => 'not an email address'];

        $validation = Validator::factory($array)
            ->rule('email', 'not_empty')
            ->rule('email', 'email');

        $validation->check();

        $errors = $validation->errors('tests/validation/error_type_check');

        $this->assertSame($errors, $validation->errors('validation'));
    }

    /**
     * Provides test data for test_rule_label_regex
     *
     * @return array
     */
    public static function provider_rule_label_regex(): array
    {
        // $data, $field, $rules, $expected
        return [
            [
                ['email1' => ''],
                'email1',
                [['not_empty']],
                ['email1' => 'email1 must not be empty'],
            ],
        ];
    }

    /**
     * http://dev.kohanaframework.org/issues/4201
     */
    #[Ticket('4201')]
    #[DataProvider('provider_rule_label_regex')]
    #[Test]
    public function test_rule_label_regex($data, $field, $rules, $expected)
    {
        $validation = Validator::factory($data)->rules($field, $rules);

        $validation->check();

        $errors = $validation->errors('');

        $this->assertSame($errors, $expected);
    }

}
