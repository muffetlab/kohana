# Model Features

These features are opt-in or additive; models written for Kohana 3 keep working.

## Mass assignment

`values()` and `fill()` without a column list only accept the columns a model allows:

    class Article extends Model
    {
        protected $fillable = ['title', 'body'];   // or: protected $guarded = ['id'];
    }

    $article = Article::make($_POST)->save();

A model without `$fillable` or `$guarded` throws `MassAssignmentException`. Passing the list yourself (`values($post, ['title'])`) works as before.

## Casts

    protected $casts = [
        'views' => 'int',
        'published' => 'bool',
        'meta' => 'array',          // json column
        'published_at' => 'datetime', // DateTimeImmutable
    ];

Available: `int`, `float`, `bool`, `string`, `array`/`json`, `object`, `datetime`, `date`, `timestamp`. The primary key is returned as an integer unless `$keyType = 'string'`.

## Timestamps

`protected $timestamps = true;` fills `created_at` and `updated_at` (`DATETIME`). Change the names with the `CREATED_AT` and `UPDATED_AT` constants; `touch()` updates `updated_at` only.

## Static queries

    Article::query()->where('views', '>', 10)->findAll();
    Article::all();
    Article::findOrFail($id);              // ModelNotFoundException (404)
    Article::firstWhere(['slug' => $slug]);
    Article::firstOrCreate(['slug' => $slug], ['title' => $title]);
    Article::updateOrCreate(['slug' => $slug], ['views' => 0]);
    Article::destroy(1, 2, 3);
    Article::query()->where(...)->firstOrFail();

## Arrays and JSON

`toArray()`, `toJson()` and `json_encode($model)` apply casts and leave out `$hidden` columns (or keep only `$visible`). `makeHidden()` and `makeVisible()` change this per instance. `asArray()` still returns every raw column.

## Eager loading

`with()` loads has_many relationships (also "through") with one extra query each, nested with dots:

    $articles = Article::query()->with('comments')->with('tags.articles')->findAll();

    foreach ($articles as $article) {
        $article->comments->findAll(); // no query
    }

Adding a condition (`$article->comments->where(...)->findAll()`) runs a real query. belongs_to and has_one keep using a JOIN (`with('author')`, `with('author:company')`).

## Pagination and chunks

    $page = Article::query()->orderBy('id', 'DESC')->paginate(15, $currentPage);
    $page->total(); $page->lastPage(); $page->hasMorePages(); foreach ($page as $article) { ... }

    Article::query()->chunk(500, function ($articles, int $page) {
        // return false to stop
    });

## Soft deletes

    use Kohana\Orm\SoftDeletes;

    class Article extends Model
    {
        use SoftDeletes; // needs a nullable deleted_at column ($table->softDeletes())
    }

`delete()` sets `deleted_at` and queries leave those rows out. `withTrashed()`, `onlyTrashed()`, `trashed()`, `restore()` and `forceDelete()` work as in Laravel.

## Events

    class Article extends Model
    {
        protected static function booted(): void
        {
            static::listen('creating', fn (Article $article) => $article->slug = Text::slug($article->title));
        }
    }

Events: `saving`, `saved`, `creating`, `created`, `updating`, `updated`, `deleting`, `deleted`, `restoring`, `restored`. A listener returning `false` for an "-ing" event cancels it.

## Many-to-many

    $article->attach('tags', [1, 2]);
    $article->detach('tags', 1);   // or detach('tags') for all
    $article->sync('tags', [2, 3]); // ['attached' => [3], 'detached' => [1]]

## Record helpers

`increment('views')`, `decrement('stock', 2)`, `fresh()`, `replicate()`, `exists()`.

## Factories

    php kohana make:factory Article

    class ArticleFactory extends Factory
    {
        protected string $model = Article::class;

        public function definition(): array
        {
            return ['title' => 'Article ' . $this->sequence()];
        }
    }

    ArticleFactory::new()->count(10)->create();
    ArticleFactory::new()->state(['published' => true])->make();

Factories live in `database/factories` (`Database\Factories`) and set every column they are given, ignoring `$fillable`.
