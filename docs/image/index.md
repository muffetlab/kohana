# Image

Kohana 3.x provides a simple yet powerful image manipulation module. The `Image` module provides features that allows your application to resize images, crop, rotate, flip and many more.

## Drivers

`Image` module ships with `GdImage` driver which requires `GD` extension enabled in your PHP installation, and
`ImagickImage` driver which requires the `imagick` PHP extension. Additional drivers can be created by extending
the `Image` class.

The `GdImage` driver is the default. You can change this by providing an `image.default_driver` configuration option
- for example:

~~~
// config/image.php
<?php
return [
    'default_driver' => 'Imagick'
];
~~~

## Getting Started

Before using the image module, we must enable it first on `APPPATH/bootstrap.php`:

~~~
Application::modules([
    // ...
    'image' => MODPATH . 'image', // Image manipulation
    // ...
]);
~~~

Next: [Using the image module](using).
