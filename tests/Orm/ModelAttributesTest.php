<?php

use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\ArticleTag;
use Kohana\Database\DB;
use Kohana\Orm\MassAssignmentException;
use Kohana\Orm\ModelNotFoundException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/OrmTestCase.php';

/**
 * Mass assignment, casts, timestamps, static queries and array conversion.
 */
#[Group('kohana')]
#[Group('kohana.orm')]
class Kohana_ModelAttributesTest extends Kohana_OrmTestCase
{
    #[DataProvider('provider_connections')]
    public function test_mass_assignment(string $name)
    {
        $this->useConnection($name);

        // $fillable: only listed columns are set
        $article = Article::make(['title' => 'Hello', 'id' => 99, 'unknown' => 'x']);
        $this->assertSame('Hello', $article->title);
        $this->assertNull($article->id);
        $this->assertArrayNotHasKey('unknown', $article->changes());

        // $guarded: everything but the guarded columns
        $comment = (new ArticleComment())->fill(['body' => 'Nice', 'article_id' => 5, 'id' => 7]);
        $this->assertSame(['body' => 'Nice', 'article_id' => 5], $comment->changes());

        // An explicit column list still works like before
        $this->assertSame(['id' => 99], (new Article())->values(['id' => 99, 'title' => 'x'], ['id'])->changes());

        // A model without $fillable or $guarded refuses values() without a list
        $this->expectException(MassAssignmentException::class);
        (new App\Models\OrmAuthor())->values(['name' => 'Ali']);
    }

    #[DataProvider('provider_connections')]
    public function test_casts(string $name)
    {
        $this->useConnection($name);

        $when = new DateTimeImmutable('2026-11-21 10:30:00');
        $article = Article::make([
            'title' => 'Casts',
            'views' => '42',
            'rating' => '4.5',
            'published' => true,
            'meta' => ['tags' => ['php', 'kohana'], 'lang' => 'tr'],
            'published_at' => $when,
        ])->save();

        $article = new Article($article->pk());

        $this->assertSame(42, $article->views);
        $this->assertSame(4.5, $article->rating);
        $this->assertTrue($article->published);
        // MySQL may reorder JSON object keys
        $this->assertEquals(['tags' => ['php', 'kohana'], 'lang' => 'tr'], $article->meta);
        $this->assertInstanceOf(DateTimeImmutable::class, $article->published_at);
        $this->assertSame('2026-11-21 10:30:00', $article->published_at->format('Y-m-d H:i:s'));

        $article->published = false;
        $article->save();
        $this->assertFalse((new Article($article->pk()))->published);
    }

    #[DataProvider('provider_connections')]
    public function test_timestamps(string $name)
    {
        $this->useConnection($name);

        $article = Article::make(['title' => 'Time'])->save();
        $article = new Article($article->pk());

        $this->assertNotNull($article->created_at);
        $this->assertSame($article->created_at, $article->updated_at);

        DB::update('articles')->set(['updated_at' => '2000-01-01 00:00:00'])->execute();
        $article = new Article($article->pk());
        $article->title = 'Changed';
        $article->save();

        $article = new Article($article->pk());
        $this->assertNotSame('2000-01-01 00:00:00', $article->updated_at);
        $this->assertTrue($article->touch());
    }

    #[DataProvider('provider_connections')]
    public function test_static_queries(string $name)
    {
        $this->useConnection($name);

        $first = Article::make(['title' => 'First', 'views' => 1])->save();
        Article::make(['title' => 'Second', 'views' => 2])->save();

        $this->assertCount(2, Article::all());
        $this->assertSame(['First', 'Second'], array_values(Article::query()->orderBy('views')->findAll()->asArray('id', 'title')));
        $this->assertSame('First', Article::findOrFail($first->pk())->title);
        $this->assertSame('Second', Article::firstWhere(['title' => 'Second'])->title);
        $this->assertNull(Article::firstWhere(['title' => 'Nope']));

        // firstOrCreate finds, then creates
        $this->assertSame($first->pk(), Article::firstOrCreate(['title' => 'First'])->pk());
        $third = Article::firstOrCreate(['title' => 'Third'], ['views' => 3]);
        $this->assertTrue($third->loaded());
        $this->assertSame(3, $third->views);

        // updateOrCreate updates, then creates
        Article::updateOrCreate(['title' => 'Third'], ['views' => 30]);
        $this->assertSame(30, Article::firstWhere(['title' => 'Third'])->views);
        Article::updateOrCreate(['title' => 'Fourth'], ['views' => 4]);
        $this->assertCount(4, Article::all());

        $this->assertSame(2, Article::destroy($first->pk(), [$third->pk(), 999999]));
        $this->assertCount(2, Article::all());

        try {
            Article::findOrFail(999999);
            $this->fail('findOrFail should throw');
        } catch (ModelNotFoundException $e) {
            $this->assertSame(404, $e->getCode());
            $this->assertSame(Article::class, $e->getModel());
        }

        $this->expectException(ModelNotFoundException::class);
        Article::query()->where('title', '=', 'Nope')->firstOrFail();
    }

    #[DataProvider('provider_connections')]
    public function test_to_array_and_json(string $name)
    {
        $this->useConnection($name);

        $article = Article::make(['title' => 'JSON', 'views' => 5, 'secret' => 'hidden', 'published_at' => '2026-11-21 10:30:00'])->save();
        $article = new Article($article->pk());

        $array = $article->toArray();
        $this->assertArrayNotHasKey('secret', $array);
        $this->assertSame(5, $array['views']);
        $this->assertSame((new DateTimeImmutable('2026-11-21 10:30:00'))->format(DATE_ATOM), $array['published_at']);

        $json = json_decode(json_encode($article), true);
        $this->assertSame('JSON', $json['title']);
        $this->assertArrayNotHasKey('secret', $json);
        $this->assertSame($json, json_decode($article->toJson(), true));

        $this->assertSame('hidden', $article->makeVisible('secret')->toArray()['secret']);
        $this->assertArrayNotHasKey('views', $article->makeHidden('views')->toArray());

        // The old asArray() still returns every column
        $this->assertSame('hidden', (new Article($article->pk()))->asArray()['secret']);
    }

    public function test_model_cache_is_kept_per_connection()
    {
        $this->useConnection('mysqli');
        $this->assertInstanceOf(Kohana\Database\MySqliConnection::class, (fn () => $this->db)->call(new ArticleTag()));

        $this->useConnection('pgsql');
        $this->assertInstanceOf(Kohana\Database\PgsqlConnection::class, (fn () => $this->db)->call(new ArticleTag()));
    }
}
