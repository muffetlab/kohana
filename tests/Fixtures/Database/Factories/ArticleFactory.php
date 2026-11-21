<?php

namespace Database\Factories;

use App\Models\Article;
use Kohana\Orm\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected string $model = Article::class;

    public function definition(): array
    {
        return [
            'title' => 'Article ' . $this->sequence(),
            'views' => 0,
            'secret' => 'not fillable, still set',
        ];
    }

    public function published(): static
    {
        return $this->state(['published' => true]);
    }
}
