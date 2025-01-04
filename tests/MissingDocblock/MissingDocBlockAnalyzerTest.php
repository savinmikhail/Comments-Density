<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\MissingDocblock;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\CommentFinder;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\CommentTypeFactory;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\MissingDocBlock;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\ConsoleOutputDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\MissingDocblockConfigDTO;

final class MissingDocBlockAnalyzerTest extends TestCase
{
    private CommentFinder $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new CommentFinder(
            new CommentTypeFactory(),
            new Config(
                output: ConsoleOutputDTO::create(),
                directories: [],
                docblockConfigDTO: new MissingDocblockConfigDTO(
                    class: true,
                    interface: true,
                    trait: true,
                    enum: true,
                    function: true,
                    property: true,
                    constant: true,
                ),
            ),
        );
    }

    public static function methodDeclarationDataProvider(): Generator
    {
        yield 'public method' => [
            <<<'PHP'
                <?php
                /**  */
                class TestClass {
                    public function testMethod() {
                    }
                }
                PHP, 1,
        ];

        yield 'static method' => [
            <<<'PHP'
                <?php
                /**  */
                class TestClass {
                    public static function testMethod1() {
                        // method body
                    }
                }
                PHP, 1,
        ];

        yield 'private method' => [
            <<<'PHP'
                <?php
                /**  */
                class TestClass {
                    private function __testMethod() {
                        // method body
                    }
                }
                PHP, 1,
        ];

        yield 'protected method' => [
            <<<'PHP'
                <?php
                /**  */
                class TestClass {
                    protected function testMethod3() {
                        // method body
                    }
                }
                PHP, 1,
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
            <<<'PHP'
                <?php
                class TestClass {}
                PHP, 1,
        ];

        yield 'final class' => [
            <<<'PHP'
                <?php
                final class TestClass {}
                PHP, 1,
        ];

        yield 'readonly class' => [
            <<<'PHP'
                <?php
                readonly class TestClass {}
                PHP, 1,
        ];

        yield 'final readonly class' => [
            <<<'PHP'
                <?php
                final readonly class TestClass {}
                PHP, 1,
        ];
    }

    public static function traitDeclarationDataProvider(): Generator
    {
        yield 'simple trait' => [
            <<<'PHP'
                <?php
                trait TestTrait {}
                PHP, 1,
        ];
    }

    public static function interfaceDeclarationDataProvider(): Generator
    {
        yield 'simple interface' => [
            <<<'PHP'
                <?php
                interface TestInterface {}
                PHP, 1,
        ];
    }

    public static function anonymousClassDeclarationDataProvider(): Generator
    {
        yield 'simple anonymous class' => [
            <<<'PHP'
                <?php
                $instance = new class {};
                PHP, 0,
        ];

        yield 'anonymous class with inheritance' => [
            <<<'PHP'
                <?php
                return $baseHydrator->bindTo(new class() extends \Error {
                });
                PHP, 0,
        ];
    }

    public static function enumDeclarationDataProvider(): Generator
    {
        yield 'simple enum' => [
            <<<'PHP'
                <?php
                enum Status {
                    case COMPLETED;
                }
                PHP, 1,
        ];

        yield 'typed enum' => [
            <<<'PHP'
                <?php
                enum Status: string {
                    case COMPLETED = 'string';
                }
                PHP, 1,
        ];
    }

    public static function propertyDataProvider(): Generator
    {
        yield 'public int property' => [
            <<<'PHP'
                <?php
                /**  */
                class Foo {
                    public int $public;
                }
                PHP, 1,
        ];

        yield 'public readonly string property' => [
            <<<'PHP'
                <?php
                /**  */
                class Foo {
                    public readonly string $publicReadonly;
                }
                PHP, 1,
        ];

        yield 'public static array property' => [
            <<<'PHP'
                <?php
                /**  */
                class Foo {
                    public static array $publicStatic;
                }
                PHP, 1,
        ];

        yield 'protected DateTime property' => [
            <<<'PHP'
                <?php
                /**  */
                class Foo {
                    protected DateTime $protected;
                }
                PHP, 1,
        ];

        yield 'private int property' => [
            <<<'PHP'
                <?php
                /**  */
                class Foo {
                    private int $private;
                }
                PHP, 1,
        ];

        yield 'method with local variable' => [
            <<<'PHP'
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
                PHP, 0,
        ];

        yield 'untyped variable' => [
            <<<'PHP'
                <?php

                foo($bar);

                PHP, 0,
        ];

        yield 'construct property declaration' => [
            <<<'PHP'
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
                PHP, 0,
        ];
    }

    public function testFunctionDeclaration(): void
    {
        $content = <<<'PHP'
            <?php
            function testFunction() {
                // function body
            }
            PHP;
        $missingDocBlocks = $this->getMissingDocBlocks(($this->analyzer)($content, 'test.php'));

        self::assertCount(1, $missingDocBlocks);
    }

    #[DataProvider('methodDeclarationDataProvider')]
    public function testMethodDeclaration(string $content, int $expectedCount): void
    {
        self::assertCount($expectedCount, $this->getMissingDocBlocks(($this->analyzer)($content, 'test.php')));
    }

    public function testFunctionImport(): void
    {
        $content = <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace SavinMikhail\CommentsDensity;

            use function in_array;
            use function is_array;
            PHP;
        $missingDocBlocks = $this->getMissingDocBlocks(($this->analyzer)($content, 'test.php'));

        self::assertCount(0, $missingDocBlocks);
    }

    #[DataProvider('closureDataProvider')]
    public function testClosuresAndArrowFunctions(string $content): void
    {
        $missingDocBlocks = $this->getMissingDocBlocks(($this->analyzer)($content, 'test.php'));
        self::assertCount(0, $missingDocBlocks);
    }

    #[DataProvider('classDeclarationDataProvider')]
    public function testClassDeclaration(string $content, int $expectedCount): void
    {
        $missingDocBlocks = $this->getMissingDocBlocks(($this->analyzer)($content, 'test.php'));
        self::assertCount($expectedCount, $missingDocBlocks);
    }

    #[DataProvider('traitDeclarationDataProvider')]
    public function testTraitDeclaration(string $content, int $expectedCount): void
    {
        $missingDocBlocks = $this->getMissingDocBlocks(($this->analyzer)($content, 'test.php'));
        self::assertCount($expectedCount, $missingDocBlocks);
    }

    #[DataProvider('interfaceDeclarationDataProvider')]
    public function testInterfaceDeclaration(string $content, int $expectedCount): void
    {
        $missingDocBlocks = $this->getMissingDocBlocks(($this->analyzer)($content, 'test.php'));
        self::assertCount($expectedCount, $missingDocBlocks);
    }

    #[DataProvider('anonymousClassDeclarationDataProvider')]
    public function testAnonymousClassDeclaration(string $content, int $expectedCount): void
    {
        $missingDocBlocks = $this->getMissingDocBlocks(($this->analyzer)($content, 'test.php'));
        self::assertCount($expectedCount, $missingDocBlocks);
    }

    #[DataProvider('enumDeclarationDataProvider')]
    public function testEnumDeclaration(string $content, int $expectedCount): void
    {
        $missingDocBlocks = $this->getMissingDocBlocks(($this->analyzer)($content, 'test.php'));
        self::assertCount($expectedCount, $missingDocBlocks);
    }

    #[DataProvider('propertyDataProvider')]
    public function testProperties(string $content, int $expectedCount): void
    {
        $missingDocBlocks = $this->getMissingDocBlocks(($this->analyzer)($content, 'test.php'));
        self::assertCount($expectedCount, $missingDocBlocks);
    }

    public function testConstant(): void
    {
        $content = <<<'PHP'
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
            PHP;
        $missingDocBlocks = $this->getMissingDocBlocks(($this->analyzer)($content, 'test.php'));

        self::assertCount(4, $missingDocBlocks);
    }

    private function getMissingDocBlocks(array $comments): array
    {
        return array_filter(
            $comments,
            static fn(CommentDTO $commentDTO): bool => $commentDTO->commentType === MissingDocBlock::NAME,
        );
    }
}
