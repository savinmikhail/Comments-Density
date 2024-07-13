<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\MissingDocblock;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\DTO\Input\MissingDocblockConfigDTO;
use SavinMikhail\CommentsDensity\MissingDocblock\MissingDocBlockAnalyzer;

final class MissingDocBlockAnalyzerTest extends TestCase
{
    private MissingDocBlockAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new MissingDocBlockAnalyzer(
            new MissingDocblockConfigDTO(
                true,
                true,
                true,
                true,
                true,
                true,
                true,
                true
            )
        );
    }

    public function testFunctionDeclaration(): void
    {
        $code = <<<'CODE'
<?php
function testFunction() {
    // function body
}
CODE;
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');

        $this->assertCount(1, $missingDocBlocks);
    }

    #[DataProvider('methodDeclarationDataProvider')]
    public function testMethodDeclaration(string $code, int $expectedCount): void
    {
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');
        $this->assertCount($expectedCount, $missingDocBlocks);
    }

    public static function methodDeclarationDataProvider(): Generator
    {
        yield 'public method' => [
            <<<'CODE'
<?php
/**  */
class TestClass {
    public function testMethod() {
        // method body
    }
}
CODE
            , 1
        ];

        yield 'static method' => [
            <<<'CODE'
<?php
/**  */
class TestClass {
    public static function testMethod1() {
        // method body
    }
}
CODE
            , 1
        ];

        yield 'private method' => [
            <<<'CODE'
<?php
/**  */
class TestClass {
    private function __testMethod() {
        // method body
    }
}
CODE
            , 1
        ];

        yield 'protected method' => [
            <<<'CODE'
<?php
/**  */
class TestClass {
    protected function testMethod3() {
        // method body
    }
}
CODE
            , 1
        ];
    }

    public function testFunctionImport(): void
    {
        $code = <<<'CODE'
<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity;

use function in_array;
use function is_array;
CODE;
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');

        $this->assertCount(0, $missingDocBlocks);
    }

    public static function closureDataProvider(): array
    {
        return [
            [
                '<?php $closure = function () {
                // closure body
             };',
            ],
            [
                '<?php $closure = [Bar::class];',
            ],
            [
                '<?php $closure = [$class::class];',
            ],
            [
                '<?php $closure = [\'My\\Namespace\\Foo\'::class];',
            ],
            [
                '<?php $closure = [Bar::class, \'method\'];',
            ],
            [
                '<?php $arrowFunction = fn() => 2;',
            ]
        ];
    }

    #[DataProvider('closureDataProvider')]
    public function testClosuresAndArrowFunctions(string $code): void
    {
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');
        $this->assertCount(0, $missingDocBlocks);
    }

    #[DataProvider('classDeclarationDataProvider')]
    public function testClassDeclaration(string $code, int $expectedCount): void
    {
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');
        $this->assertCount($expectedCount, $missingDocBlocks);
    }

    public static function classDeclarationDataProvider(): Generator
    {
        yield 'simple class' => [
            <<<'CODE'
<?php
class TestClass {}
CODE
            , 1
        ];

        yield 'final class' => [
            <<<'CODE'
<?php
final class TestClass {}
CODE
            , 1
        ];

        yield 'readonly class' => [
            <<<'CODE'
<?php
readonly class TestClass {}
CODE
            , 1
        ];

        yield 'final readonly class' => [
            <<<'CODE'
<?php
final readonly class TestClass {}
CODE
            , 1
        ];
    }

    #[DataProvider('traitDeclarationDataProvider')]
    public function testTraitDeclaration(string $code, int $expectedCount): void
    {
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');
        $this->assertCount($expectedCount, $missingDocBlocks);
    }

    public static function traitDeclarationDataProvider(): Generator
    {
        yield 'simple trait' => [
            <<<'CODE'
<?php
trait TestTrait {}
CODE
            , 1
        ];
    }

    #[DataProvider('interfaceDeclarationDataProvider')]
    public function testInterfaceDeclaration(string $code, int $expectedCount): void
    {
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');
        $this->assertCount($expectedCount, $missingDocBlocks);
    }

    public static function interfaceDeclarationDataProvider(): Generator
    {
        yield 'simple interface' => [
            <<<'CODE'
<?php
interface TestInterface {}
CODE
            , 1
        ];
    }

    #[DataProvider('anonymousClassDeclarationDataProvider')]
    public function testAnonymousClassDeclaration(string $code, int $expectedCount): void
    {
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');
        $this->assertCount($expectedCount, $missingDocBlocks);
    }

    public static function anonymousClassDeclarationDataProvider(): Generator
    {
        yield 'simple anonymous class' => [
            <<<'CODE'
<?php
$instance = new class {};
CODE
            , 0
        ];

        yield 'anonymous class with inheritance' => [
            <<<'CODE'
<?php
return $baseHydrator->bindTo(new class() extends \Error {
});
CODE
            , 0
        ];
    }

    #[DataProvider('enumDeclarationDataProvider')]
    public function testEnumDeclaration(string $code, int $expectedCount): void
    {
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');
        $this->assertCount($expectedCount, $missingDocBlocks);
    }

    public static function enumDeclarationDataProvider(): Generator
    {
        yield 'simple enum' => [
            <<<'CODE'
<?php
enum Status {
    case COMPLETED;
}
CODE
            , 1
        ];

        yield 'typed enum' => [
            <<<'CODE'
<?php
enum Status: string {
    case COMPLETED = 'string';
}
CODE
            , 1
        ];
    }

    #[DataProvider('propertyDataProvider')]
    public function testProperties(string $code, int $expectedCount): void
    {
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');
        $this->assertCount($expectedCount, $missingDocBlocks);
    }

    public static function propertyDataProvider(): Generator
    {
        yield 'public int property' => [
            <<<'CODE'
<?php
/**  */
class Foo {
    public int $public;
}
CODE
            , 1
        ];

        yield 'public readonly string property' => [
            <<<'CODE'
<?php
/**  */
class Foo {
    public readonly string $publicReadonly;
}
CODE
            , 1
        ];

        yield 'public static array property' => [
            <<<'CODE'
<?php
/**  */
class Foo {
    public static array $publicStatic;
}
CODE
            , 1
        ];

        yield 'protected DateTime property' => [
            <<<'CODE'
<?php
/**  */
class Foo {
    protected DateTime $protected;
}
CODE
            , 1
        ];

        yield 'private int property' => [
            <<<'CODE'
<?php
/**  */
class Foo {
    private int $private;
}
CODE
            , 1
        ];

        yield 'method with local variable' => [
            <<<'CODE'
<?php
/**  */
class Foo {
    /**  */
    public function foo(Closure $closure, $baz,
        DateTime $time, int $foo,
        array $var = 3,
        ?Bar $bar = null
    ): array {
        $regVar = 2;
    }
}
CODE
            , 0
        ];

        yield 'untyped variable' => [
            <<<'CODE'
<?php

foo($bar);

CODE
            , 0
        ];

        yield 'construct property declaration' => [
            <<<'CODE'
<?php
/**  */
class Foo
{
    /**
     * Creates a new serializable closure instance.
     *
     *Continuing from where the message was cut off:

```php
     * @param  \Closure  $closure
     * @return void
     */
    public function __construct(public Closure $closure)
    {}

    /**  */
    public function __construct(
        Closure $closure
    )
    {}
}
CODE
            , 0
        ];
    }

    public function testConstant()
    {
        $code = <<<'CODE'
<?php

const METHOD = 'foo';
/**
*
 */
class Foo
{
  final const FINAL = 2;
  public const PUBLIC = 3;
  protected const int TYPED = 4;
  private const PRIVATE = 5;
}
CODE;
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');

        $this->assertCount(4, $missingDocBlocks);
    }

    public static function genericDocblockDataProvider(): Generator
    {
        yield 'simple method' => [
            <<<'CODE'
<?php

class Foo
{
    public function foo(): User
    {
        return new User;
    }
}

CODE
            , 0
        ];

        yield 'method with generic' => [
            <<<'CODE'
<?php

class Foo
{
    public function bar(): array
    {
        return [new User()];
    }
}

CODE
            , 1
        ];

        yield 'method with non-generic' => [
            <<<'CODE'
<?php

class Foo
{
    public function bar(): array
    {
        return [
            [],
            'asdf' => 12,
            'asdf'
        ];
    }
}

CODE
            , 0
        ];

        yield 'method with mixed array' => [
            <<<'CODE'
<?php

class Foo
{
    public function mixedArray(): array
    {
        return [
            new User(),
            new Order(),
        ];
    }
}

CODE
            , 0
        ];

        yield 'method with empty array' => [
            <<<'CODE'
<?php

class Foo
{
    public function emptyArray(): array
    {
        return [];
    }
}

CODE
            , 0
        ];

        yield 'method with nested generic' => [
            <<<'CODE'
<?php

class Foo
{
    public function nestedArray(): array
    {
        return [
            [new User()],
            [new User()]
        ];
    }
}

CODE
            , 1
        ];

        yield 'method with associative array' => [
            <<<'CODE'
<?php

class Foo
{
    public function associativeArray(): array
    {
        return [
            'user1' => new User(),
            'user2' => new User()
        ];
    }
}

CODE
            , 1
        ];

        yield 'method with other iterable' => [
            <<<'CODE'
<?php

class Foo
{
    public function iterableReturn(): iterable
    {
        return new \ArrayIterator([new User()]);
    }
}

CODE
            , 1
        ];

        yield 'method with array of integers' => [
            <<<'CODE'
<?php

class Foo
{
    public function integerArray(): array
    {
        return [1, 2, 3];
    }
}

CODE
            , 0
        ];

        yield 'method with generator' => [
            <<<'CODE'
<?php

class Foo
{
    public function generatorReturn(): \Generator
    {
        yield new User();
    }
}

CODE
            , 1
        ];

        yield 'method with class implementing Iterator' => [
            <<<'CODE'
<?php

use SavinMikhail\Tests\CommentsDensity\TestFiles\UserCollection;

class Foo
{
    public function userCollectionReturn(): UserCollection
    {
        return new UserCollection([new User()]);
    }
}

CODE
            , 1
        ];

        yield 'method returning Iterator' => [
            <<<'CODE'
<?php

use SavinMikhail\Tests\CommentsDensity\TestFiles\UserCollection;
use Iterator;

class Foo
{
    public function userCollectionReturn(): Iterator
    {
        return new UserCollection([new User()]);
    }
}

CODE
            , 1
        ];

        yield 'method with class implementing ArrayAccess' => [
            <<<'CODE'
<?php

use SavinMikhail\Tests\CommentsDensity\TestFiles\UserArray;

class Foo
{
    public function userArrayReturn(): UserArray
    {
        $array = new UserArray();
        $array[] = new User();
        return $array;
    }
}

CODE
            , 1
        ];

        yield 'method returning ArrayAccess' => [
            <<<'CODE'
<?php

use SavinMikhail\Tests\CommentsDensity\TestFiles\UserArray;

class Foo
{
    public function userArrayReturn(): ArrayAccess
    {
        $array = new UserArray();
        $array[] = new User();
        return $array;
    }
}

CODE
            , 1
        ];
    }

    #[DataProvider('genericDocblockDataProvider')]
    public function testGenericDocblockDetection(string $code, int $expectedCount): void
    {
        $analyzer = new MissingDocBlockAnalyzer(
            new MissingDocblockConfigDTO(
                false,
                false,
                false,
                false,
                true,
                false,
                false,
                false
            )
        );

        $missingDocBlocks = $analyzer->getMissingDocblocks($code, 'test.php');
        $this->assertCount($expectedCount, $missingDocBlocks);
    }

    public static function uncaughtExceptionDocblockDataProvider(): Generator
    {
        yield 'method with uncaught exception' => [
            <<<'CODE'
<?php

class Foo
{
    public function baz(): void
    {
        throw new Exception();
    }
}

CODE
            , 1
        ];

        yield 'method with caught exception' => [
            <<<'CODE'
<?php

class Foo
{
    public function baz(): void
    {
        try {
            throw new Exception();
        } catch (Exception $e) {
            //do something
        }
    }
}

CODE
            , 0
        ];

        yield 'method with rethrown exception' => [
            <<<'CODE'
<?php

class Foo
{
    public function baz(): void
    {
        try {
            throw new Exception();
        } catch (Exception $e) {
            //do something
            throw $e;
        }
    }
}

CODE
            , 1
        ];

        yield 'method caught another exception' => [
            <<<'CODE'
<?php
class MyException extends Exception {}
class Foo
{
    public function baz(): void
    {
        try {
            throw new Exception();
        } catch (MyException $e) {
            //do something
        }
    }
}

CODE
            , 1
        ];

        yield 'method caught throwable' => [
            <<<'CODE'
<?php
class Foo
{
    public function baz(): void
    {
        try {
            throw new Exception();
        } catch (Throwable $th) {
            //do something
        }
    }
}

CODE
            , 0
        ];

        yield 'method with multiple catches' => [
            <<<'CODE'
<?php
class MyException extends Exception {}

class Foo
{
    public function baz(): void
    {
        try {
            throw new Exception();
        } catch (MyException $e) {
            //do something
        } catch (Exception $e) {
            //do something
        }
    }
}

CODE
            , 0
        ];
    }

    #[DataProvider('uncaughtExceptionDocblockDataProvider')]
    public function testExceptionDocblockDetection(string $code, int $expectedCount): void
    {
        $analyzer = new MissingDocBlockAnalyzer(
            new MissingDocblockConfigDTO(
                false,
                false,
                false,
                false,
                true,
                false,
                false,
                false
            )
        );

        $missingDocBlocks = $analyzer->getMissingDocblocks($code, 'test.php');
        $this->assertCount($expectedCount, $missingDocBlocks);
    }
}
