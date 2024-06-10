<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity;

use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\CDS;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;

final class CDSTest extends TestCase
{
    public function testCalculateCDS(): void
    {
        $commentFactory = new CommentFactory();
        $calculator = new CDS([], $commentFactory);

        $commentStatistics = [
            'docBlock' => 10,
            'regular' => 5,
            'todo' => 2,
            'fixme' => 1,
            'license' => 1,
            'missingDocblock' => 3,
        ];

        $cds = $calculator->calculateCDS($commentStatistics);
        $this->assertIsFloat($cds);
        $this->assertGreaterThanOrEqual(0, $cds);
        $this->assertLessThanOrEqual(1, $cds);
    }
}
