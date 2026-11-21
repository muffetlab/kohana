# Test Doubles

Sometimes a test depends on an object you don't want to use for real. When testing a model, for example, you may want to check the query it builds without connecting to a database server. PHPUnit generates *test doubles* for that.

## Stubs and mocks

A **stub** only returns canned values. A **mock** also verifies how it was called. Use a stub unless the test is about the interaction.

    use Kohana\Cache\FileStore;

    // Stub: return values only
    $cache = $this->createStub(FileStore::class);
    $cache->method('get')->willReturn('cached value');

    // Mock: return values and expectations
    $cache = $this->createMock(FileStore::class);
    $cache->expects($this->once())
        ->method('set')
        ->with('key', 'value')
        ->willReturn(true);

`createStub()` and `createMock()` replace every method and skip the constructor. A mock that never gets an expectation triggers a PHPUnit notice; use a stub instead, or add `#[AllowMockObjectsWithoutExpectations]` to the test class.

## Customizing a double

`getMockBuilder()` gives you more control:

    $mock = $this->getMockBuilder(Validator::class)
        ->setConstructorArgs([$data])   // call the real constructor with these arguments
        ->onlyMethods(['check'])        // double only check(); other methods run for real
        ->getMock();

- `onlyMethods(array $methods)` doubles only the listed methods. `onlyMethods([])` doubles nothing, which gives you a real object whose expectations you can still inspect.
- `setConstructorArgs(array $args)` passes arguments to the real constructor.
- `disableOriginalConstructor()` skips the constructor.
- `disableOriginalClone()` skips `__clone()`.

### Abstract classes

PHPUnit removed `getMockForAbstractClass()`. `Kohana\Testing\TestCase` provides `mockAbstract()`, which doubles the abstract methods (plus any you list) and keeps the concrete ones:

    // Abstract methods doubled, constructor called with $config
    $session = $this->mockAbstract(Session::class, [$config]);

    // Constructor skipped, sendMessage() doubled too
    $client = $this->mockAbstract(ExternalClient::class, null, ['sendMessage']);

### Classes with a method named `method()`

PHPUnit cannot double a class that has a method called `method()`, such as `Kohana\Http\Request`. Use a real request instead; its setters make it easy to configure:

    $request = new Request('welcome/index');
    $request->controller('Welcome');
    $request->action('index');

## How many times should it be called?

Pass an invocation rule to `expects()`:

| Rule | Meaning |
|---|---|
| `$this->any()` | any number of times |
| `$this->never()` | never |
| `$this->once()` | exactly once |
| `$this->atLeastOnce()` | at least once |
| `$this->exactly($count)` | exactly `$count` times |
| `$this->atLeast($count)`, `$this->atMost($count)` | at least / at most `$count` times |

PHPUnit no longer supports `$this->at($index)`. To check the order or the arguments of several calls, record them with a callback:

    $calls = [];
    $cache->expects($this->exactly(2))
        ->method('set')
        ->willReturnCallback(function (...$args) use (&$calls) {
            $calls[] = $args;

            return true;
        });

    // ... run the code under test ...

    $this->assertSame('first', $calls[0][0]);
    $this->assertSame('second', $calls[1][0]);

## What should it be called with?

`with()` takes one constraint per argument. A plain value means "equal to":

    $mock->expects($this->once())
        ->method('rule')
        ->with('username', $this->stringContains('length'), $this->anything());

Common constraints: `$this->identicalTo()`, `$this->equalTo()`, `$this->anything()`, `$this->isInstanceOf()`, `$this->isType()`, `$this->stringContains()`, `$this->stringStartsWith()`, `$this->stringEndsWith()`, `$this->matchesRegularExpression()`, `$this->greaterThan()`, `$this->lessThan()`, `$this->arrayHasKey()`, `$this->callback(fn ($value) => ...)`, and `$this->logicalAnd()`, `logicalOr()`, `logicalNot()` to combine them.

## What should it return?

| Method | Returns |
|---|---|
| `willReturn($value)` | `$value` (several values: one per call) |
| `willReturnSelf()` | the double itself, for fluent interfaces |
| `willReturnArgument($index)` | the argument at `$index` |
| `willReturnMap([[...$args, $return], ...])` | the value matching the arguments |
| `willReturnCallback(callable $callback)` | whatever the callback returns |
| `willThrowException(Throwable $e)` | throws `$e` |

See the [PHPUnit manual](https://docs.phpunit.de/en/13.0/test-doubles.html) for the full API.
