<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Metrics;

use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\Metrics\ComToLoc;

final class ComToLocTest  extends TestCase
{
    public function testPrepareComToLocAboveThreshold(): void
    {
        $comToLoc = new ComToLoc(['Com/LoC' => 0.1]);
        $commentStat = ['missingDocblock' => ['lines' => 2, 'times' => 3]];
        $comToLocDTO = $comToLoc->prepareComToLoc($commentStat, 2);

        $this->assertEquals($comToLocDTO->comToLoc, 1);
        $this->assertEquals($comToLocDTO->color, 'green');
    }

    public function testPrepareComToLocWithoutThreshold(): void
    {
        $comToLoc = new ComToLoc([]);
        $commentStat = ['missingDocblock' => ['lines' => 2, 'times' => 3]];
        $comToLocDTO = $comToLoc->prepareComToLoc($commentStat, 2);

        $this->assertEquals($comToLocDTO->comToLoc, 1);
        $this->assertEquals($comToLocDTO->color, 'white');
    }

    public function testPrepareComToLocColorWhenExceedThreshold(): void
    {
        $comToLoc = new ComToLoc(['Com/LoC' => 1]);
        $commentStat = ['missingDocblock' => ['lines' => 1, 'times' => 3]];
        $comToLocDTO = $comToLoc->prepareComToLoc($commentStat, 2);

        $this->assertEquals($comToLocDTO->comToLoc, 0.5);
        $this->assertEquals($comToLocDTO->color, 'red');
    }

    public function testPrepareComToLocWithZeroLinesOfCode(): void
    {
        $comToLoc = new ComToLoc(['Com/LoC' => 1]);
        $commentStat = ['missingDocblock' => ['lines' => 1, 'times' => 3]];
        $comToLocDTO = $comToLoc->prepareComToLoc($commentStat, 0);

        $this->assertEquals($comToLocDTO->comToLoc, 0);
        $this->assertEquals($comToLocDTO->color, 'red');
    }

    public function testPrepareComToLocExceedThreshold(): void
    {
        $comToLoc = new ComToLoc(['Com/LoC' => 1]);
        $commentStat = ['missingDocblock' => ['lines' => 1, 'times' => 3]];
        $comToLoc->prepareComToLoc($commentStat, 2);
        $exceededThreshold = $comToLoc->hasExceededThreshold();
        $this->assertEquals(true, $exceededThreshold);
    }
}