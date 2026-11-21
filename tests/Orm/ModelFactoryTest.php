<?php

use App\Models\Article;
use Database\Factories\ArticleFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/OrmTestCase.php';

#[Group('kohana')]
#[Group('kohana.orm')]
class Kohana_ModelFactoryTest extends Kohana_OrmTestCase
{
    public function test_make()
    {
        $article = ArticleFactory::new()->make(['views' => 3]);

        $this->assertInstanceOf(Article::class, $article);
        $this->assertFalse($article->loaded());
        $this->assertSame(3, $article->views);
        $this->assertSame('not fillable, still set', $article->secret);
        $this->assertMatchesRegularExpression('/^Article \d+$/', $article->title);
    }

    #[DataProvider('provider_connections')]
    public function test_create(string $name)
    {
        $this->useConnection($name);

        $articles = ArticleFactory::new()->published()->state(fn (array $values) => ['views' => strlen($values['title'])])->count(3)->create();

        $this->assertCount(3, $articles);
        $this->assertCount(3, Article::all());
        $this->assertCount(3, array_unique(array_map(fn ($a) => $a->title, $articles)));
        foreach ($articles as $article) {
            $article = $article->fresh();
            $this->assertTrue($article->published);
            $this->assertSame(strlen($article->title), $article->views);
        }
    }
}
