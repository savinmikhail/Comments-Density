<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Comments;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SavinMikhail\CommentsDensity\Comments\Comment;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\Comments\DocBlockComment;
use SavinMikhail\CommentsDensity\Comments\FixMeComment;
use SavinMikhail\CommentsDensity\Comments\LicenseComment;
use SavinMikhail\CommentsDensity\Comments\RegularComment;
use SavinMikhail\CommentsDensity\Comments\TodoComment;

final class CommentTest extends TestCase
{
    public static function regularCommentRegexDataProvider(): array
    {
        return [
            ['//dd()', RegularComment::class],
            ['#something', RegularComment::class],
            ['/* bla bal */', RegularComment::class],
            ['/** @var string $name */', DocBlockComment::class],
            ['//todo: asdf', TodoComment::class],
            ['// TODO asdf', TodoComment::class],
            ['//fixme: asdf', FixMeComment::class],
            ['// FIXME asdf', FixMeComment::class],
            ['/** License MIT */', LicenseComment::class],
        ];
    }

    public static function isWithinThresholdDataProvider(): array
    {
        return [
            [RegularComment::class, 5, ['regular' => 10], true],
            [RegularComment::class, 15, ['regular' => 10], false],
            [TodoComment::class, 5, ['todo' => 5], true],
            [TodoComment::class, 4, ['todo' => 5], true],
            [FixMeComment::class, 5, ['fixme' => 4], false],
            [FixMeComment::class, 3, ['fixme' => 4], true],
            [DocBlockComment::class, 5, ['docBlock' => 4], true],
            [DocBlockComment::class, 3, ['docBlock' => 4], false],
            [LicenseComment::class, 3, ['license' => 4], false],
            [LicenseComment::class, 5, ['license' => 4], true],
        ];
    }

    public static function isExceededThresholdDataProvider(): array
    {
        return [
            [RegularComment::class, 5, ['regular' => 10], false],
            [RegularComment::class, 15, ['regular' => 10], true],
            [TodoComment::class, 5, ['todo' => 5], false],
            [TodoComment::class, 4, ['todo' => 5], false],
            [FixMeComment::class, 5, ['fixme' => 4], true],
            [FixMeComment::class, 3, ['fixme' => 4], false],
            [DocBlockComment::class, 5, ['docBlock' => 4], false],
            [DocBlockComment::class, 3, ['docBlock' => 4], true],
            [LicenseComment::class, 3, ['license' => 4], true],
            [LicenseComment::class, 5, ['license' => 4], false],
        ];
    }

    public static function getStatColorDataProvider(): array
    {
        return [
            [RegularComment::class, 5, ['regular' => 10], 'green'],
            [RegularComment::class, 15, ['regular' => 10], 'red'],
            [RegularComment::class, 5, [], 'white'],
        ];
    }

    #[DataProvider('regularCommentRegexDataProvider')]
    public function testRegularCommentRegex(string $comment, string $class): void
    {
        $factory = new CommentFactory();
        $commentType = $factory->classifyComment($comment);
        self::assertInstanceOf($class, $commentType);
    }

    #[DataProvider('isWithinThresholdDataProvider')]
    public function testIsWithinThreshold(string $class, int $count, array $thresholds, bool $expected): void
    {
        /** @var Comment $comment */
        $comment = new $class();
        $result = $this->invokeMethod($comment, 'isWithinThreshold', [$count, $thresholds]);
        self::assertEquals($expected, $result);
    }

    #[DataProvider('isExceededThresholdDataProvider')]
    public function testIsExceededThreshold(string $class, int $count, array $thresholds, bool $expected): void
    {
        /** @var Comment $comment */
        $comment = new $class();
        $this->invokeMethod($comment, 'getStatColor', [$count, $thresholds]);
        self::assertEquals($expected, $comment->hasExceededThreshold());
    }

    #[DataProvider('getStatColorDataProvider')]
    public function testGetStatColor(string $class, int $count, array $thresholds, string $expectedColor): void
    {
        $comment = new $class();
        $color = $comment->getStatColor($count, $thresholds);
        self::assertEquals($expectedColor, $color);
    }

    public function testToString(): void
    {
        $comment = new RegularComment();
        self::assertEquals('regular', (string) $comment);

        $comment = new DocBlockComment();
        self::assertEquals('docBlock', (string) $comment);
    }

    /**
     * Helper method to invoke protected/private methods.
     */
    private function invokeMethod(object $object, string $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
