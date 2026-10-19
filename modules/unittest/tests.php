<?php

use Kohana\Unittest\Tests;

if (!class_exists('\Kohana\Kohana')) {
    die('Please include the kohana bootstrap file (see README.markdown)');
}

if (class_exists('\Kohana\Unittest\Tests')) {
    // PHPUnit requires a test suite class to be in this file,
    // so we create a faux one that uses the kohana base
    class TestSuite extends Tests
    {
        
    }

} else {
    die('Could not include the test suite');
}
