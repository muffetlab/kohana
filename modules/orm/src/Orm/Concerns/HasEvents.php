<?php

namespace Kohana\Orm\Concerns;

/**
 * Model events: saving, saved, creating, created, updating, updated,
 * deleting, deleted, restoring and restored. A listener that returns false
 * for an "-ing" event cancels the operation.
 *
 *     Article::listen('creating', fn (Article $article) => $article->slug = Text::slug($article->title));
 *
 * Models can register their listeners in booted(), which runs once per class.
 *
 * Part of Kohana\Orm\Model.
 *
 * @package    Kohana/ORM
 */
trait HasEvents
{
    /**
     * @var array<class-string, array<string, array<int, callable>>>
     */
    protected static $listeners = [];

    /**
     * @var array<class-string, true> Classes whose booted() has run
     */
    protected static $booted = [];

    /**
     * Registers listeners for a model class. Override it in a model.
     */
    protected static function booted(): void
    {
    }

    /**
     * Runs booted() the first time a model of this class is created.
     */
    protected function bootIfNotBooted(): void
    {
        if (!isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;
            static::booted();
        }
    }

    /**
     * Listens to an event of this model class.
     *
     * @param callable(static): mixed $listener
     */
    public static function listen(string $event, callable $listener): void
    {
        static::$listeners[static::class][$event][] = $listener;
    }

    /**
     * Removes the listeners of this model class, or of every class.
     */
    public static function flushEventListeners(bool $all = false): void
    {
        if ($all) {
            static::$listeners = static::$booted = [];
        } else {
            unset(static::$listeners[static::class], static::$booted[static::class]);
        }
    }

    /**
     * Calls the listeners of an event.
     *
     * @return bool False when a listener cancelled the event
     */
    protected function fireEvent(string $event): bool
    {
        foreach (static::$listeners[static::class][$event] ?? [] as $listener) {
            if ($listener($this) === false && str_ends_with($event, 'ing')) {
                return false;
            }
        }

        return true;
    }
}
