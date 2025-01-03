<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Metrics;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\CdsDTO;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\CommentStatisticsDTO;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\Metrics\CDS;

final class CDSTest extends TestCase
{
    private CDS $cds;

    private CommentFactory $commentFactory;

    protected function setUp(): void
    {
        $this->commentFactory = new CommentFactory();
        $this->cds = new CDS(['CDS' => 0.5], $this->commentFactory);
    }

    public function testCalculateCDS(): void
    {
        $commentStatistics = [
            new CommentStatisticsDTO('red', 'docBlock', 12, 'red', 10),
            new CommentStatisticsDTO('red', 'regular', 12, 'red', 10),
            new CommentStatisticsDTO('red', 'todo', 12, 'red', 10),
            new CommentStatisticsDTO('red', 'fixme', 12, 'red', 10),
            new CommentStatisticsDTO('red', 'license', 12, 'red', 10),
            new CommentStatisticsDTO('red', 'missingDocblock', 12, 'red', 10),
        ];

        $cdsValue = $this->cds->calculateCDS($commentStatistics);
        self::assertIsFloat($cdsValue);
        self::assertGreaterThanOrEqual(0, $cdsValue);
        self::assertLessThanOrEqual(1, $cdsValue);
    }

    public function testPrepareCDS(): void
    {
        $cdsValue = 0.75;
        $cdsDTO = $this->cds->prepareCDS($cdsValue);

        self::assertInstanceOf(CdsDTO::class, $cdsDTO);
        self::assertEquals(0.75, $cdsDTO->cds);
        self::assertEquals('green', $cdsDTO->color);
    }

    public function testGetColorForCDS(): void
    {
        $reflection = new ReflectionClass($this->cds);
        $method = $reflection->getMethod('getColorForCDS');
        $method->setAccessible(true);

        self::assertEquals('green', $method->invokeArgs($this->cds, [0.75]));
        self::assertEquals('red', $method->invokeArgs($this->cds, [0.25]));

        $cds = new CDS([], new CommentFactory());
        $reflection = new ReflectionClass($cds);
        $method = $reflection->getMethod('getColorForCDS');
        $method->setAccessible(true);

        self::assertEquals('white', $method->invokeArgs($cds, [0.75]));
    }

    public function testHasExceededThreshold(): void
    {
        self::assertFalse($this->cds->hasExceededThreshold());

        $commentStatistics = [
            new CommentStatisticsDTO('red', 'docBlock', 12, 'red', 1),
            new CommentStatisticsDTO('red', 'missingDocblock', 12, 'red', 10),
        ];

        $cds = $this->cds->calculateCDS($commentStatistics);
        $this->cds->prepareCDS($cds);

        self::assertTrue($this->cds->hasExceededThreshold());
    }
}
