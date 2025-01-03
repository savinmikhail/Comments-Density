<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Metrics;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\PerformanceMetricsDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Metrics\ResourceUtilization;

final class PerformanceMonitorTest extends TestCase
{
    public function testStart(): void
    {
        $performanceMonitor = new ResourceUtilization();
        $reflection = new ReflectionClass($performanceMonitor);
        $startTimeProperty = $reflection->getProperty('startTime');
        $startTimeProperty->setAccessible(true);

        $performanceMonitor->start();

        self::assertNotNull($startTimeProperty->getValue($performanceMonitor));
    }

    public function testStop(): void
    {
        $performanceMonitor = new ResourceUtilization();
        $reflection = new ReflectionClass($performanceMonitor);
        $endTimeProperty = $reflection->getProperty('endTime');
        $endTimeProperty->setAccessible(true);
        $peakMemoryUsageProperty = $reflection->getProperty('peakMemoryUsage');
        $peakMemoryUsageProperty->setAccessible(true);

        $performanceMonitor->start();
        usleep(50000);
        $performanceMonitor->stop();

        self::assertNotNull($endTimeProperty->getValue($performanceMonitor));
        self::assertNotNull($peakMemoryUsageProperty->getValue($performanceMonitor));
    }

    public function testGetPerformanceMetrics(): void
    {
        $performanceMonitor = new ResourceUtilization();
        $performanceMonitor->start();
        usleep(50000);
        $performanceMonitor->stop();

        $performanceMetrics = $performanceMonitor->getPerformanceMetrics();

        self::assertInstanceOf(PerformanceMetricsDTO::class, $performanceMetrics);

        $reflection = new ReflectionClass($performanceMetrics);
        $executionTimeProperty = $reflection->getProperty('executionTime');
        $executionTimeProperty->setAccessible(true);
        $peakMemoryUsageProperty = $reflection->getProperty('peakMemoryUsage');
        $peakMemoryUsageProperty->setAccessible(true);

        self::assertGreaterThan(0, $executionTimeProperty->getValue($performanceMetrics));
        self::assertGreaterThan(0, $peakMemoryUsageProperty->getValue($performanceMetrics));
    }
}
