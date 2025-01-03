<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\MissingDocblock;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\MissingDocblockConfigDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\MissingDocblock\MissingDocBlockAnalyzer;

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
                true,
            ),
        );
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
                CODE, 1,
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
                CODE, 1,
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
                CODE, 1,
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
                CODE, 1,
        ];
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
                '<?php $closure = [\'My\Namespace\Foo\'::class];',
            ],
            [
                '<?php $closure = [Bar::class, \'method\'];',
            ],
            [
                '<?php $arrowFunction = fn() => 2;',
            ],
        ];
    }

    public static function classDeclarationDataProvider(): Generator
    {
        yield 'simple class' => [
            <<<'CODE'
                <?php
                class TestClass {}
                CODE, 1,
        ];

        yield 'final class' => [
            <<<'CODE'
                <?php
                final class TestClass {}
                CODE, 1,
        ];

        yield 'readonly class' => [
            <<<'CODE'
                <?php
                readonly class TestClass {}
                CODE, 1,
        ];

        yield 'final readonly class' => [
            <<<'CODE'
                <?php
                final readonly class TestClass {}
                CODE, 1,
        ];
    }

    public static function traitDeclarationDataProvider(): Generator
    {
        yield 'simple trait' => [
            <<<'CODE'
                <?php
                trait TestTrait {}
                CODE, 1,
        ];
    }

    public static function interfaceDeclarationDataProvider(): Generator
    {
        yield 'simple interface' => [
            <<<'CODE'
                <?php
                interface TestInterface {}
                CODE, 1,
        ];
    }

    public static function anonymousClassDeclarationDataProvider(): Generator
    {
        yield 'simple anonymous class' => [
            <<<'CODE'
                <?php
                $instance = new class {};
                CODE, 0,
        ];

        yield 'anonymous class with inheritance' => [
            <<<'CODE'
                <?php
                return $baseHydrator->bindTo(new class() extends \Error {
                });
                CODE, 0,
        ];
    }

    public static function enumDeclarationDataProvider(): Generator
    {
        yield 'simple enum' => [
            <<<'CODE'
                <?php
                enum Status {
                    case COMPLETED;
                }
                CODE, 1,
        ];

        yield 'typed enum' => [
            <<<'CODE'
                <?php
                enum Status: string {
                    case COMPLETED = 'string';
                }
                CODE, 1,
        ];
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
                CODE, 1,
        ];

        yield 'public readonly string property' => [
            <<<'CODE'
                <?php
                /**  */
                class Foo {
                    public readonly string $publicReadonly;
                }
                CODE, 1,
        ];

        yield 'public static array property' => [
            <<<'CODE'
                <?php
                /**  */
                class Foo {
                    public static array $publicStatic;
                }
                CODE, 1,
        ];

        yield 'protected DateTime property' => [
            <<<'CODE'
                <?php
                /**  */
                class Foo {
                    protected DateTime $protected;
                }
                CODE, 1,
        ];

        yield 'private int property' => [
            <<<'CODE'
                <?php
                /**  */
                class Foo {
                    private int $private;
                }
                CODE, 1,
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
                CODE, 0,
        ];

        yield 'untyped variable' => [
            <<<'CODE'
                <?php

                foo($bar);

                CODE, 0,
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
                CODE, 0,
        ];
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

        self::assertCount(1, $missingDocBlocks);
    }

    #[DataProvider('methodDeclarationDataProvider')]
    public function testMethodDeclaration(string $code, int $expectedCount): void
    {
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');
        self::assertCount($expectedCount, $missingDocBlocks);
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

        self::assertCount(0, $missingDocBlocks);
    }

    #[DataProvider('closureDataProvider')]
    public function testClosuresAndArrowFunctions(string $code): void
    {
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');
        self::assertCount(0, $missingDocBlocks);
    }

    #[DataProvider('classDeclarationDataProvider')]
    public function testClassDeclaration(string $code, int $expectedCount): void
    {
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');
        self::assertCount($expectedCount, $missingDocBlocks);
    }

    #[DataProvider('traitDeclarationDataProvider')]
    public function testTraitDeclaration(string $code, int $expectedCount): void
    {
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');
        self::assertCount($expectedCount, $missingDocBlocks);
    }

    #[DataProvider('interfaceDeclarationDataProvider')]
    public function testInterfaceDeclaration(string $code, int $expectedCount): void
    {
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');
        self::assertCount($expectedCount, $missingDocBlocks);
    }

    #[DataProvider('anonymousClassDeclarationDataProvider')]
    public function testAnonymousClassDeclaration(string $code, int $expectedCount): void
    {
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');
        self::assertCount($expectedCount, $missingDocBlocks);
    }

    #[DataProvider('enumDeclarationDataProvider')]
    public function testEnumDeclaration(string $code, int $expectedCount): void
    {
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');
        self::assertCount($expectedCount, $missingDocBlocks);
    }

    #[DataProvider('propertyDataProvider')]
    public function testProperties(string $code, int $expectedCount): void
    {
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($code, 'test.php');
        self::assertCount($expectedCount, $missingDocBlocks);
    }

    public function testConstant(): void
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

        self::assertCount(4, $missingDocBlocks);
    }

    public function testGetColor(): void
    {
        self::assertEquals('red', $this->analyzer->getColor());
    }

    public function testGetStatColor(): void
    {
        $thresholds = [
            'missingDocBlock' => 10,
        ];

        // Case 1: Count below threshold
        self::assertEquals('green', $this->analyzer->getStatColor(5, $thresholds));
        self::assertFalse($this->analyzer->hasExceededThreshold());

        // Case 2: Count at threshold
        self::assertEquals('green', $this->analyzer->getStatColor(10, $thresholds));
        self::assertFalse($this->analyzer->hasExceededThreshold());

        // Case 3: Count above threshold
        self::assertEquals('red', $this->analyzer->getStatColor(15, $thresholds));
        self::assertTrue($this->analyzer->hasExceededThreshold());

        // Case 4: Threshold not set
        self::assertEquals('white', $this->analyzer->getStatColor(15, []));
    }

    public function testHasExceededThreshold(): void
    {
        $thresholds = [
            'missingDocBlock' => 10,
        ];

        $this->analyzer->getStatColor(15, $thresholds);
        self::assertTrue($this->analyzer->hasExceededThreshold());
    }

    public function testGetName(): void
    {
        self::assertEquals('missingDocblock', $this->analyzer->getName());
    }
}
