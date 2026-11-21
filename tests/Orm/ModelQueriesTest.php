<?php

use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\ArticleTag;
use Kohana\Database\DB;
use Kohana\Orm\Paginator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/OrmTestCase.php';

/**
 * Eager loading, pagination and chunking.
 */
#[Group('kohana')]
#[Group('kohana.orm')]
class Kohana_ModelQueriesTest extends Kohana_OrmTestCase
{
    /**
     * Two articles with comments and tags; the second tag is on both.
     *
     * @return array<int, Article>
     */
    protected function seed(): array
    {
        $first = Article::make(['title' => 'First'])->save();
        $second = Article::make(['title' => 'Second'])->save();
        Article::make(['title' => 'Empty'])->save();

        foreach ([[$first, 'a'], [$first, 'b'], [$second, 'c']] as [$article, $body]) {
            ArticleComment::make(['article_id' => $article->pk(), 'body' => $body])->save();
        }

        $php = ArticleTag::make(['name' => 'php'])->save();
        $orm = ArticleTag::make(['name' => 'orm'])->save();
        $first->add('tags', [$php, $orm]);
        $second->add('tags', $orm);

        return [$first, $second];
    }

    #[DataProvider('provider_connections')]
    public function test_eager_loading(string $name)
    {
        $this->useConnection($name);
        $this->seed();

        $articles = Article::query()->with('comments')->with('tags.articles')->orderBy('id')->findAll();

        // Everything is in memory: removing the rows does not change the result
        foreach (['article_tag', 'article_comments', 'article_tags'] as $table) {
            DB::delete($table)->execute();
        }

        $this->assertCount(3, $articles);
        [$first, $second, $empty] = iterator_to_array($articles, false);

        $this->assertSame(['a', 'b'], array_values($first->comments->findAll()->asArray('id', 'body')));
        $this->assertSame(['c'], array_values($second->comments->findAll()->asArray('id', 'body')));
        $this->assertCount(0, $empty->comments->findAll());
        $this->assertEqualsCanonicalizing(['php', 'orm'], array_values($first->tags->findAll()->asArray('id', 'name')));

        // Nested: every tag knows its articles
        $orm = $second->tags->findAll()->current();
        $this->assertSame('orm', $orm->name);
        $this->assertEqualsCanonicalizing(['First', 'Second'], array_values($orm->articles->findAll()->asArray('id', 'title')));

        // An extra condition runs a real query
        $this->assertCount(0, $first->comments->where('body', '=', 'a')->findAll());

        // toArray() includes the loaded relationships
        $this->assertSame(['a', 'b'], array_column($first->toArray()['comments'], 'body'));
    }

    #[DataProvider('provider_connections')]
    public function test_eager_loading_with_find(string $name)
    {
        $this->useConnection($name);
        [$first] = $this->seed();

        $article = Article::query()->with('comments')->where('id', '=', $first->pk())->find();
        DB::delete('article_comments')->execute();

        $this->assertCount(2, $article->comments->findAll());

        // Without with() the relationship is queried as before
        $this->assertCount(0, (new Article($first->pk()))->comments->findAll());
    }

    #[DataProvider('provider_connections')]
    public function test_paginate(string $name)
    {
        $this->useConnection($name);

        foreach (range(1, 7) as $i) {
            Article::make(['title' => "Article $i", 'views' => $i])->save();
        }

        $page = Article::query()->where('views', '>', 1)->orderBy('views', 'DESC')->paginate(4, 2);

        $this->assertInstanceOf(Paginator::class, $page);
        $this->assertSame(6, $page->total());
        $this->assertSame(2, $page->lastPage());
        $this->assertSame(2, $page->currentPage());
        $this->assertFalse($page->hasMorePages());
        $this->assertCount(2, $page);
        $this->assertSame([3, 2], array_map(fn ($a) => $a->views, iterator_to_array($page, false)));
        $this->assertSame(6, json_decode(json_encode($page), true)['total']);

        $this->assertTrue(Article::query()->paginate(3)->hasMorePages());
    }

    #[DataProvider('provider_connections')]
    public function test_chunk(string $name)
    {
        $this->useConnection($name);

        foreach (range(1, 5) as $i) {
            Article::make(['title' => "Article $i", 'views' => $i])->save();
        }

        $seen = [];
        $this->assertTrue(Article::query()->chunk(2, function ($articles, $page) use (&$seen) {
            foreach ($articles as $article) {
                $seen[$page][] = $article->views;
            }
        }));
        $this->assertSame([1 => [1, 2], 2 => [3, 4], 3 => [5]], $seen);

        $pages = 0;
        $this->assertFalse(Article::query()->where('views', '>', 1)->chunk(1, function () use (&$pages) {
            return ++$pages < 2;
        }));
        $this->assertSame(2, $pages);
    }
}
