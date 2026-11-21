<?php

namespace Kohana\Orm\Concerns;

use Kohana\Orm\Model;
use Kohana\Orm\ValidationException;
use Kohana\Support\Arr;
use Kohana\Validation\Validator;

/**
 * Validation rules, filters and labels.
 *
 * Part of Kohana\Orm\Model.
 *
 * @package    Kohana/ORM
 */
trait ValidatesAttributes
{
    /**
     * Initializes validation rules, and labels
     *
     * @return void
     */
    protected function initializeValidation()
    {
        // Build the validation object with its rules
        $this->validation = Validator::factory($this->object)
            ->bind(':model', $this)
            ->bind(':original_values', $this->originalValues)
            ->bind(':changes', $this->changes);

        // Use column names by default for labels
        $columns = [];

        foreach ($this->rules() as $field => $rules) {
            $columns[$field] = $field;
            $this->validation->rules($field, $rules);
        }

        // Merge user-defined labels
        $labels = array_merge($columns, $this->labels());

        $this->validation->labels($labels);
    }

    /**
     * Rule definitions for validation
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Filters a value for a specific column
     *
     * @param string $field The column name
     * @param mixed $value The value to filter
     * @return mixed
     * @throws \ReflectionException
     */
    protected function runFilter(string $field, $value)
    {
        $filters = $this->filters();

        // Get the filters for this column
        $wildcards = empty($filters[true]) ? [] : $filters[true];

        // Merge in the wildcards
        $filters = empty($filters[$field]) ? $wildcards : array_merge($wildcards, $filters[$field]);

        // Bind the field name and model, so they can be used in the filter method
        $_bound = [
            ':field' => $field,
            ':model' => $this,
        ];

        foreach ($filters as $array) {
            // Value needs to be bound inside the loop, so we are always using the
            // version that was modified by the filters that already ran
            $_bound[':value'] = $value;

            // Filters are defined as [$filter, $params]
            $filter = $array[0];
            $params = Arr::get($array, 1, [':value']);

            foreach ($params as $key => $param) {
                if (is_string($param) && array_key_exists($param, $_bound)) {
                    // Replace with bound value
                    $params[$key] = $_bound[$param];
                }
            }

            if (is_callable($filter)) {
                // This is either a callback as an array or a lambda
                $value = call_user_func_array($filter, $params);
            } else {
                // Call $function($this[$field], $param, ...) with Reflection
                $value = (new \ReflectionFunction($filter))->invokeArgs($params);
            }
        }

        return $value;
    }

    /**
     * Filter definitions for validation
     *
     * @return array
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Label definitions for validation
     *
     * @return array
     */
    public function labels(): array
    {
        return [];
    }

    /**
     * Validates the current model's data
     *
     * @param \Kohana\Validation\Validator|null $extra_validation Validation object
     * @return \Kohana\Orm\Model
     * @throws \Kohana\Orm\ValidationException
     * @throws \ReflectionException
     */
    public function check(?Validator $extra_validation = null): self
    {
        // Determine if any external validation failed
        $extra_errors = $extra_validation && !$extra_validation->check();

        // Always build a new validation object
        $this->initializeValidation();

        $array = $this->validation;

        if (($this->valid = $array->check()) === false || $extra_errors) {
            $exception = new ValidationException($this->errorsFilename(), $array);

            if ($extra_errors) {
                // Merge any possible errors from the external object
                $exception->addObject('external', $extra_validation);
            }
            throw $exception;
        }

        return $this;
    }

    public function validation(): ?Validator
    {
        if (!isset($this->validation)) {
            // Initialize the validation object
            $this->initializeValidation();
        }

        return $this->validation;
    }

    public function errorsFilename(): ?string
    {
        return $this->errorsFilename;
    }

    /**
     * Checks whether a column value is unique.
     * Excludes itself if loaded.
     *
     * @param string $field the field to check for uniqueness
     * @param mixed $value the value to check for uniqueness
     * @return  bool     whether the value is unique
     * @throws \Kohana\Foundation\KohanaException
     */
    public function unique(string $field, $value): bool
    {
        $model = static::factory(static::class)
            ->where($field, '=', $value)
            ->find();

        if ($this->loaded()) {
            return !($model->loaded() && $model->pk() !== $this->pk());
        }

        return !$model->loaded();
    }
}
