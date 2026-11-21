<?php

namespace Kohana\Foundation;

/**
 * Model base class. All models should extend this class.
 *
 * @package    Kohana
 * @category   Models
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
abstract class Model
{
    /**
     * Create a new model instance.
     *
     *     $model = Model::factory($name);
     *
     * @param string $name Model name
     * @return  Model
     */
    public static function factory(string $name): \Kohana\Foundation\Model
    {
        // Add the model prefix
        $class = str_contains($name, '\\') ? $name : 'App\\Models\\' . str_replace('_', '', ucwords($name, '_'));

        return new $class;
    }

}
