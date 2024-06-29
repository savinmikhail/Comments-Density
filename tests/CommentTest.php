<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\Comments\DocBlockComment;
use SavinMikhail\CommentsDensity\Comments\FixMeComment;
use SavinMikhail\CommentsDensity\Comments\LicenseComment;
use SavinMikhail\CommentsDensity\Comments\RegularComment;
use SavinMikhail\CommentsDensity\Comments\TodoComment;

class CommentTest extends TestCase
{
    public static function regularCommentRegexDataProvider(): array
    {
        return [
            ['//dd()',  RegularComment::class],
            ['#something',  RegularComment::class],
            ['/* bla bal */',  RegularComment::class],
            ['/** @var string $name */', DocBlockComment::class],
            ['//todo: asdf', TodoComment::class],
            ['// TODO asdf', TodoComment::class],
            ['//fixme: asdf', FixMeComment::class],
            ['// FIXME asdf', FixMeComment::class],
            ['/** License MIT */', LicenseComment::class],
        ];
    }

    public function testRegularCommentRegex(string $comment, string $class): void
    {
        $factory = new CommentFactory();
        $commentType = $factory->classifyComment($comment);
        $this->assertTrue($commentType instanceof $class);
    }
}
