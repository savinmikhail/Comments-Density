<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Metrics;

use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\Comments\CommentFactory;
use SavinMikhail\CommentsDensity\Comments\DocBlockComment;
use SavinMikhail\CommentsDensity\DTO\Output\CdsDTO;
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
            'docBlock' => ['count' => 10, 'lines' => 12],
            'regular' => ['count' => 10, 'lines' => 12],
            'todo' => ['count' => 10, 'lines' => 12],
            'fixme' => ['count' => 10, 'lines' => 12],
            'license' => ['count' => 10, 'lines' => 12],
            'missingDocblock' => ['count' => 10, 'lines' => 12],
        ];

        $cdsValue = $this->cds->calculateCDS($commentStatistics);
        $this->assertIsFloat($cdsValue);
        $this->assertGreaterThanOrEqual(0, $cdsValue);
        $this->assertLessThanOrEqual(1, $cdsValue);
    }

    public function testPrepareCDS(): void
    {
        $cdsValue = 0.75;
        $cdsDTO = $this->cds->prepareCDS($cdsValue);

        $this->assertInstanceOf(CdsDTO::class, $cdsDTO);
        $this->assertEquals(0.75, $cdsDTO->cds);
        $this->assertEquals('green', $cdsDTO->color);
    }

    public function testGetColorForCDS(): void
    {
        $reflection = new \ReflectionClass($this->cds);
        $method = $reflection->getMethod('getColorForCDS');
        $method->setAccessible(true);

        $this->assertEquals('green', $method->invokeArgs($this->cds, [0.75]));
        $this->assertEquals('red', $method->invokeArgs($this->cds, [0.25]));

        $cds = new CDS([], new CommentFactory());
        $reflection = new \ReflectionClass($cds);
        $method = $reflection->getMethod('getColorForCDS');
        $method->setAccessible(true);

        $this->assertEquals('white', $method->invokeArgs($cds, [0.75]));
    }

    public function testHasExceededThreshold(): void
    {
        $this->assertFalse($this->cds->hasExceededThreshold());

        $cds = $this->cds->calculateCDS([
            'docBlock' => ['count' => 1, 'lines' => 12],
            'missingDocblock' => ['count' => 10, 'lines' => 12],
        ]);
        $this->cds->prepareCDS($cds);

        $this->assertTrue($this->cds->hasExceededThreshold());
    }
}
