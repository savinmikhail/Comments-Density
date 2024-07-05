<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Metrics;

use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\Metrics\CDS;

final class CDSTest extends TestCase
{
    public function testCalculateCDS(): void
    {
        $commentFactory = new CommentFactory();
        $calculator = new CDS([], $commentFactory);

        $commentStatistics = [
            'docBlock' => ['count' => 10, 'lines' => 12],
            'regular' => ['count' => 10, 'lines' => 12],
            'todo' => ['count' => 10, 'lines' => 12],
            'fixme' => ['count' => 10, 'lines' => 12],
            'license' => ['count' => 10, 'lines' => 12],
            'missingDocblock' => ['count' => 10, 'lines' => 12],
        ];

        $cds = $calculator->calculateCDS($commentStatistics);
        $this->assertIsFloat($cds);
        $this->assertGreaterThanOrEqual(0, $cds);
        $this->assertLessThanOrEqual(1, $cds);
    }
}
