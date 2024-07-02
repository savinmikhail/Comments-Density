<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\MissingDocBlockAnalyzer;
use function token_get_all;

final class MissingDocBlockAnalyzerTest extends TestCase
{
    private MissingDocBlockAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new MissingDocBlockAnalyzer();
    }

    public function testFunctionDeclaration(): void
    {
        $code = <<<'CODE'
<?php
function testFunctionn() {
    // function body
}
CODE;
        $tokens = token_get_all($code);
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($tokens, 'test.php');

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
        $tokens = token_get_all($code);
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($tokens, 'test.php');

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
        $tokens = token_get_all($code);
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($tokens, 'test.php');

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
                '<?php $closure = [Bar::class, \'method\'];',
            ],
            [
                '<?php $arrowFunction = fn() => 2;',
            ]
        ];
    }

    /**
     * @dataProvider closureDataProvider
     */
    public function testClosuresAndArrowFunctions(string $code): void
    {
        $tokens = token_get_all($code);
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($tokens, 'test.php');
        $this->assertCount(0, $missingDocBlocks);
    }

    public function testClassDeclaration(): void
    {
        $code = <<<'CODE'
<?php
class TestClass {}
CODE;
        $tokens = token_get_all($code);
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($tokens, 'test.php');

        $this->assertCount(1, $missingDocBlocks);
    }

    public function testTraitDeclaration(): void
    {
        $code = <<<'CODE'
<?php
trait TestTrait {}
CODE;
        $tokens = token_get_all($code);
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($tokens, 'test.php');

        $this->assertCount(1, $missingDocBlocks);
    }

    public function testInterfaceDeclaration(): void
    {
        $code = <<<'CODE'
<?php
interface TestInterface {}
CODE;
        $tokens = token_get_all($code);
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($tokens, 'test.php');

        $this->assertCount(1, $missingDocBlocks);
    }

    public function testAnonymousClassDeclaration(): void
    {
        $code = <<<'CODE'
<?php
$instance = new class {};
CODE;
        $tokens = token_get_all($code);
        $missingDocBlocks = $this->analyzer->getMissingDocblocks($tokens, 'test.php');

        $this->assertCount(0, $missingDocBlocks);
    }
}
