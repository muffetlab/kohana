# Validation

ORM models are tightly integrated with the `Validator` library and the module comes with a very flexible `ValidationException` that helps you quickly handle validation errors from basic CRUD operations.

## Defining Rules

Validation rules are defined in the `Model::rules()` method. This method returns the array of rules to be added to the `Validator` object like so:

    public function rules()
    {
        return [
            'username' => [
                // Uses Rules::notEmpty($value);
                ['not_empty'],
                // Calls Some_Class::some_method('param1', 'param2');
                ['Some_Class::some_method', ['param1', 'param2']],
                // Calls A_Class::a_method($value);
                [['A_Class', 'a_method']],
                // Calls the lambda function and passes the field value and the validation object
                [
                    function ($value, Validator $object) {
                        $object->error('some_field', 'some_error');
                    },
                    [':value', ':validation']
                ],
            ],
        ];
    }

### Bound Values

ORM will automatically bind the following values with `Validator::bind()`:

- **:field** - The name of the field the rule is being applied to.
- **:value** - The value of the field the rule is being applied to.
- **:model** - The instance of the model that is being validated.

## Automatic Validation

All models automatically validate their own data when `Model::save()`, `Model::update()`, or `Model::create()` is called. Because of this, you should always expect these methods to throw an `ValidationException` when the model's data is invalid.

    public function create()
    {
        try {
            $user = Model::factory('User');
            $user->username = 'invalid username';
            $user->save();
        } catch (ValidationException $e) {
            $errors = $e->errors();
        }
    }

## Handling Validation Exceptions

The `ValidationException` will give you access to the validation errors that were encountered while trying to save a model's information. The `ValidationException::errors()` method works very similarly to `Validator::errors()`. Not passing a first parameter will return the name of the rules that failed. But unlike `Validate::errors()`, the first parameter of `ValidationException::errors()` is a directory path. The model's ORM::$_object_name will be appended to the directory in order to form the message file for `Validator::errors()` to use. The second parameter is identical to that of `Validator::errors()`.

In the below example, the error messages will be defined in `resources/messages/models/user.php`

    public function create()
    {
        try {
            $user = Model::factory('User');
            $user->username = 'invalid username';
            $user->save();
        } catch (ValidationException $e) {
            $errors = $e->errors('models');
        }
    }

## External Validation

Certain forms contain information that should not be validated by the model, but by the controller. Information such as a [CSRF](https://en.wikipedia.org/wiki/Cross-site_request_forgery) token, password verification, or a [CAPTCHA](https://en.wikipedia.org/wiki/CAPTCHA) should never be validated by a model. However, validating information in multiple places and combining the errors to provide the user with a good experience is often quite tedious. For this reason, the `ValidationException` is built to handle multiple Validation objects and namespaces the array of errors automatically for you. `Model::save()`, `Model::update()`, and `Model::create()` all take an optional first parameter which is a `Validator` object to validate along with the model.

    public function create()
    {
        try {
            $user = Model::factory('User');
            $user->username = $_POST['username'];
            $user->password = $_POST['password'];

            $extra_rules = Validator::factory($_POST)
                ->rule('password_confirm', 'matches', [
                    ':validation',
                    ':field',
                    'password'
                ]);

            // Pass the extra rules to be validated with the model
            $user->save($extra_rules);
        } catch (ValidationException $e) {
            $errors = $e->errors('models');
        }
    }

Because the validation object was passed as a parameter to the model, any errors found in that check will be namespaced into a sub-array called `_external`. The array of errors would look something like this:

    [
        'username' => 'This field cannot be empty.',
        '_external' => [
            'password_confirm' => 'The values you entered in the password fields did not match.',
        ],
    ];

This ensures that errors from multiple validation objects and models will never overwrite each other.

[!!] The power of the `ValidationException` can be leveraged in many different ways to merge errors from related models. Take a look at the list of [Examples](examples) for some great use cases.