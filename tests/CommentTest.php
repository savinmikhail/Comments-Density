<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity;

use JetBrains\PhpStorm\NoReturn;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\CommentDensity;
use Symfony\Component\Console\Output\ConsoleOutput;

class CommentTest extends TestCase
{
    public static function regularCommentRegexDataProvider(): array
    {
        return [
            ['//dd()',  true],
            ['#something',  true],
            ['/* bla bal */',  true],
            ['/** @var string $name */', false],
            ['//todo: asdf', false],
            ['//fixme: asdf', false]
        ];
    }

    #[DataProvider('regularCommentRegexDataProvider')]
    public function testRegularCommentRegex(string $comment, bool $shouldMatch): void
    {
        $this->markTestIncomplete();
    }

    #[NoReturn]
    public function testCheckForDocBlocks(): void
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'ClassSample.php';
        $commentDensity = new CommentDensity(new ConsoleOutput(), []);
        $res = $commentDensity->checkForDocBlocks($file);
//        dd($res);
    }
}
