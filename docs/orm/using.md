# Basic Usage

## Load a new model instance

To create a new `User` instance, you can do one of two things:

    $user = Model::factory('User');
    // Or
    $user = new User();

## Inserting

To insert a new record into the database, create a new instance of the model:

    $user = Model::factory('User');

Then, assign values for each of the properties;

    $user->first_name = 'Trent';
    $user->last_name = 'Reznor';
    $user->city = 'Mercer';
    $user->state = 'PA';

Insert the new record into the database by running [ORM::save]:

    $user->save();

`Model::save()` checks to see if a value is set for the primary key (`id` by default). If the primary key is set, then ORM will execute an `UPDATE` otherwise it will execute an `INSERT`.


## Finding an object

To find an object you can call the `Model::find()` method or pass the id into the ORM constructor:

    // Find user with ID 20
    $user = Model::factory('User')
        ->where('id', '=', 20)
        ->find();
    // Or
    $user = Model::factory('User', 20);

## Check that ORM loaded a record

Use the `Model::loaded()` method to check that ORM successfully loaded a record.

    if ($user->loaded()) {
        // Load was successful
    } else {
        // Error
    }

## Updating and Saving

Once an ORM model has been loaded, you can modify a model's properties like this:

    $user->first_name = "Trent";
    $user->last_name = "Reznor";

And if you want to save the changes you just made back to the database, just run a `save()` call like this:

    $user->save();



## Deleting


To delete an object, you can call the `Model::delete()` method on a loaded ORM model.

    $user = Model::factory('User', 20);
    $user->delete();


## Mass assignment


To set multiple values at once, use `Model::values()`

    try {
        $user = Model::factory('user')
            ->values($this->request->post(), ['username', 'password'])
            ->create();
    } catch (ValidationException $e) {
        // Handle validation errors ...
    }
