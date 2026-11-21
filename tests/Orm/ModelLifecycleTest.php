<?php

use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\ArticleTag;
use Kohana\Database\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/OrmTestCase.php';

/**
 * Soft deletes, events, pivot sync and record helpers.
 */
#[Group('kohana')]
#[Group('kohana.orm')]
class Kohana_ModelLifecycleTest extends Kohana_OrmTestCase
{
    public function tearDown(): void
    {
        Article::flushEventListeners(true);

        parent::tearDown();
    }

    #[DataProvider('provider_connections')]
    public function test_soft_deletes(string $name)
    {
        $this->useConnection($name);

        $kept = Article::make(['title' => 'Kept'])->save();
        $gone = Article::make(['title' => 'Gone'])->save();
        ArticleComment::make(['article_id' => $gone->pk(), 'body' => 'x'])->save();

        $gone->delete();
        $this->assertTrue($gone->loaded());
        $this->assertTrue($gone->trashed());

        // The row is still there, queries leave it out
        $this->assertSame(2, (int) DB::select([DB::expr('COUNT(*)'), 'total'])->from('articles')->execute()->get('total'));
        $this->assertSame(['Kept'], array_values(Article::all()->asArray('id', 'title')));
        $this->assertSame(1, Article::query()->countAll());
        $this->assertFalse((new Article($gone->pk()))->loaded());
        $this->assertNull(Article::firstWhere(['title' => 'Gone']));

        // An orWhere() cannot bring it back
        $this->assertCount(1, Article::query()->where('title', '=', 'Kept')->orWhere('title', '=', 'Gone')->findAll());

        $this->assertCount(2, Article::query()->withTrashed()->findAll());
        $this->assertSame(['Gone'], array_values(Article::query()->onlyTrashed()->findAll()->asArray('id', 'title')));
        // The scope only lasts for one query
        $query = Article::query()->withTrashed();
        $query->findAll();
        $this->assertCount(1, $query->findAll());

        // Has-many through a trashed parent's comment still works; restore brings it back
        $this->assertTrue($gone->restore());
        $this->assertFalse($gone->trashed());
        $this->assertCount(2, Article::all());

        $kept->forceDelete();
        $this->assertFalse($kept->loaded());
        $this->assertCount(1, Article::query()->withTrashed()->findAll());
    }

    #[DataProvider('provider_connections')]
    public function test_events(string $name)
    {
        $this->useConnection($name);

        $log = [];
        foreach (['saving', 'creating', 'created', 'updating', 'updated', 'saved', 'deleting', 'deleted'] as $event) {
            Article::listen($event, function (Article $article) use ($event, &$log) {
                $log[] = $event;
            });
        }
        Article::listen('creating', fn (Article $article) => $article->views = 10);

        $article = Article::make(['title' => 'Events'])->save();
        $this->assertSame(['saving', 'creating', 'created', 'saved'], $log);
        $this->assertSame(10, (new Article($article->pk()))->views);

        $log = [];
        $article->title = 'Changed';
        $article->save();
        $this->assertSame(['saving', 'updating', 'updated', 'saved'], $log);

        $log = [];
        $article->delete();
        $this->assertSame(['deleting', 'deleted'], $log);

        // A listener returning false cancels the operation
        Article::listen('creating', fn () => false);
        $this->assertFalse(Article::make(['title' => 'Cancelled'])->save()->loaded());
        $this->assertNull(Article::query()->withTrashed()->where('title', '=', 'Cancelled')->find()->pk());

        // Listeners belong to their class
        $this->assertTrue(ArticleTag::make(['name' => 'free'])->save()->loaded());
    }

    #[DataProvider('provider_connections')]
    public function test_sync_attach_detach(string $name)
    {
        $this->useConnection($name);

        $article = Article::make(['title' => 'Tags'])->save();
        [$a, $b, $c] = array_map(fn ($n) => ArticleTag::make(['name' => $n])->save(), ['a', 'b', 'c']);

        $article->attach('tags', [$a->pk(), $b->pk()]);
        $this->assertTrue($article->has('tags', [$a->pk(), $b->pk()]));

        $result = $article->sync('tags', [$b, $c->pk()]);
        $this->assertSame([$c->pk()], $result['attached']);
        $this->assertEquals([$a->pk()], $result['detached']);
        $this->assertEqualsCanonicalizing(['b', 'c'], array_values($article->tags->findAll()->asArray('id', 'name')));

        $article->detach('tags', $b);
        $this->assertSame(1, $article->countRelations('tags'));
        $article->detach('tags');
        $this->assertFalse($article->hasAny('tags'));
    }

    #[DataProvider('provider_connections')]
    public function test_record_helpers(string $name)
    {
        $this->useConnection($name);

        $article = Article::make(['title' => 'Count', 'views' => 5])->save();
        $this->assertTrue($article->exists());
        $this->assertFalse((new Article())->exists());

        // Someone else changes the row; increment adds to the stored value
        DB::update('articles')->set(['views' => 100])->execute();
        $article->increment('views', 2, ['title' => 'Counted']);
        // The in-memory value only knows its own change
        $this->assertSame(7, $article->views);
        $this->assertSame(102, $article->fresh()->views);
        $this->assertSame('Counted', $article->fresh()->title);
        $this->assertFalse($article->changed());

        $article->decrement('views', 3);
        $this->assertSame(99, $article->fresh()->views);

        $copy = $article->replicate(['views']);
        $this->assertFalse($copy->loaded());
        $this->assertSame('Counted', $copy->title);
        $copy->save();
        $this->assertNotSame($article->pk(), $copy->pk());
        $this->assertSame(0, $copy->fresh()->views);

        $article->delete();
        $this->assertTrue($article->fresh()->trashed());
        $article->forceDelete();
        $this->assertNull($article->fresh());
    }
}
