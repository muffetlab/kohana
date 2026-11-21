<?php

namespace Kohana\Orm\Factories;

use Kohana\Orm\Model;

/**
 * Builds models with default values for tests and seeders.
 *
 *     class ArticleFactory extends Factory
 *     {
 *         protected string $model = Article::class;
 *
 *         public function definition(): array
 *         {
 *             return ['title' => 'Article ' . $this->sequence()];
 *         }
 *     }
 *
 *     ArticleFactory::new()->count(10)->create(['published' => true]);
 *
 * Values bypass $fillable: a factory sets every column it is given.
 *
 * @package    Kohana/ORM
 * @template TModel of Model
 * @phpstan-consistent-constructor
 */
abstract class Factory
{
    /**
     * @var class-string<TModel>
     */
    protected string $model;

    protected ?int $count = null;

    /**
     * @var array<int, array<string, mixed>|callable(array<string, mixed>): array<string, mixed>>
     */
    protected array $states = [];

    /**
     * @var array<class-string, int> Records built per factory, for sequence()
     */
    protected static array $sequences = [];

    /**
     * Default column values of one model.
     *
     * @return array<string, mixed>
     */
    abstract public function definition(): array;

    /**
     * @param array<string, mixed> $attributes
     */
    public static function new(array $attributes = []): static
    {
        return (new static())->state($attributes);
    }

    /**
     * Builds this many models; make() and create() then return an array.
     */
    public function count(int $count): static
    {
        $factory = clone $this;
        $factory->count = $count;

        return $factory;
    }

    /**
     * Overrides values, with an array or a callback receiving the values so far.
     *
     * @param array<string, mixed>|callable(array<string, mixed>): array<string, mixed> $state
     */
    public function state(array|callable $state): static
    {
        $factory = clone $this;
        $factory->states[] = $state;

        return $factory;
    }

    /**
     * A number that grows with every model this factory builds, from 1.
     */
    protected function sequence(): int
    {
        return static::$sequences[static::class] ?? 1;
    }

    /**
     * Builds unsaved models.
     *
     * @param array<string, mixed> $attributes
     * @return TModel|array<int, TModel>
     */
    public function make(array $attributes = []): Model|array
    {
        $models = [];
        foreach (range(1, $this->count ?? 1) as $_) {
            $models[] = $this->makeOne($attributes);
        }

        return $this->count === null ? $models[0] : $models;
    }

    /**
     * Builds and saves models.
     *
     * @param array<string, mixed> $attributes
     * @return TModel|array<int, TModel>
     */
    public function create(array $attributes = []): Model|array
    {
        $models = $this->make($attributes);

        foreach (is_array($models) ? $models : [$models] as $model) {
            $model->save();
        }

        return $models;
    }

    /**
     * @param array<string, mixed> $attributes
     * @return TModel
     */
    protected function makeOne(array $attributes): Model
    {
        $values = $this->definition();
        foreach ([...$this->states, $attributes] as $state) {
            $values = array_merge($values, is_callable($state) ? $state($values) : $state);
        }

        static::$sequences[static::class] = $this->sequence() + 1;

        $model = new ($this->model)();

        return $model->values($values, array_keys($values));
    }
}
