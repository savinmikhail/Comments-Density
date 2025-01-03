<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Metrics;

use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Metrics\ComToLoc;

final class ComToLocTest extends TestCase
{
    public function testPrepareComToLocAboveThreshold(): void
    {
        $comToLoc = new ComToLoc(['Com/LoC' => 0.1]);
        $commentStat = [new CommentStatisticsDTO('red', 'missingDocblock', 2, 'red', 3)];

        $comToLocDTO = $comToLoc->prepareComToLoc($commentStat, 2);

        self::assertEquals($comToLocDTO->comToLoc, 1);
        self::assertEquals($comToLocDTO->color, 'green');
    }

    public function testPrepareComToLocWithoutThreshold(): void
    {
        $comToLoc = new ComToLoc([]);
        $commentStat = [new CommentStatisticsDTO('red', 'missingDocblock', 2, 'red', 3)];
        $comToLocDTO = $comToLoc->prepareComToLoc($commentStat, 2);

        self::assertEquals($comToLocDTO->comToLoc, 1);
        self::assertEquals($comToLocDTO->color, 'white');
    }

    public function testPrepareComToLocColorWhenExceedThreshold(): void
    {
        $comToLoc = new ComToLoc(['Com/LoC' => 1]);
        $commentStat = [new CommentStatisticsDTO('red', 'missingDocblock', 1, 'red', 3)];

        $comToLocDTO = $comToLoc->prepareComToLoc($commentStat, 2);

        self::assertEquals($comToLocDTO->comToLoc, 0.5);
        self::assertEquals($comToLocDTO->color, 'red');
    }

    public function testPrepareComToLocWithZeroLinesOfCode(): void
    {
        $comToLoc = new ComToLoc(['Com/LoC' => 1]);
        $commentStat = [new CommentStatisticsDTO('red', 'missingDocblock', 1, 'red', 3)];

        $comToLocDTO = $comToLoc->prepareComToLoc($commentStat, 0);

        self::assertEquals($comToLocDTO->comToLoc, 0);
        self::assertEquals($comToLocDTO->color, 'red');
    }

    public function testPrepareComToLocExceedThreshold(): void
    {
        $comToLoc = new ComToLoc(['Com/LoC' => 1]);
        $commentStat = [new CommentStatisticsDTO('red', 'missingDocblock', 1, 'red', 3)];

        $comToLoc->prepareComToLoc($commentStat, 2);
        $exceededThreshold = $comToLoc->hasExceededThreshold();
        self::assertTrue($exceededThreshold);
    }
}
