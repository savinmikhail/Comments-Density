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

    public function testMethodDeclaration(): void
    {
        $code = <<<'CODE'
<?php
/** 
 * docblock 
 */
class TestClass {
    public function testMethod() {
        // method body
    }
}
CODE;
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');

        $this->assertCount(1, $missingDocBlocks);
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

    public function testClassDeclaration(): void
    {
        $code = <<<'CODE'
<?php
class TestClass {}
CODE;
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');

        $this->assertCount(1, $missingDocBlocks);
    }

    public function testTraitDeclaration(): void
    {
        $code = <<<'CODE'
<?php
trait TestTrait {}
CODE;
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');

        $this->assertCount(1, $missingDocBlocks);
    }

    public function testInterfaceDeclaration(): void
    {
        $code = <<<'CODE'
<?php
interface TestInterface {}
CODE;
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');

        $this->assertCount(1, $missingDocBlocks);
    }

    public function testAnonymousClassDeclaration(): void
    {
        $code = <<<'CODE'
<?php
$instance = new class {};
return $baseHydrator->bindTo(new class() extends \Error {
    });
CODE;
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');

        $this->assertCount(0, $missingDocBlocks);
    }

    public function testEnumDeclaration(): void
    {
        $code = <<<'CODE'
<?php
enum Status {
    case PENDING;
    case COMPLETED;
}
CODE;
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');

        $this->assertCount(1, $missingDocBlocks);
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

    #[DataProvider('propertyDataProvider')]
    public function testProperties(string $code, int $expectedCount): void
    {
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');
        $this->assertCount($expectedCount, $missingDocBlocks);
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

    public static function smartDocblockAnalyzisDataProvider(): Generator
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
        return [new User];
    }
}

CODE
            , 1
        ];

        yield 'method with uncaught exception' => [
            <<<'CODE'
<?php

class Foo 
{
    public function baz(): array
    {
        throw new Exception();
    }
}

CODE
            , 1
        ];

//        yield 'method with caught exception' => [
//            <<<'CODE'
//<?php
//
//class Foo
//{
//    public function baz(): array
//    {
//        try {
//            throw new Exception();
//        } catch (Exception $e) {
//            //do something
//        }
//    }
//}
//
//CODE
//            , 0
//        ];
    }

    #[DataProvider('smartDocblockAnalyzisDataProvider')]
    public function testSmartDocblockAnalysis(string $code, int $expectedCount): void
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
