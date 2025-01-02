<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Metrics;

use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\Analyzer\DTO\Output\PerformanceMetricsDTO;
use SavinMikhail\CommentsDensity\Metrics\ResourceUtilization;

final class PerformanceMonitorTest extends TestCase
{
    public function testStart(): void
    {
        $performanceMonitor = new ResourceUtilization();
        $reflection = new \ReflectionClass($performanceMonitor);
        $startTimeProperty = $reflection->getProperty('startTime');
        $startTimeProperty->setAccessible(true);

        $performanceMonitor->start();

        $this->assertNotNull($startTimeProperty->getValue($performanceMonitor));
    }

    public function testStop(): void
    {
        $performanceMonitor = new ResourceUtilization();
        $reflection = new \ReflectionClass($performanceMonitor);
        $endTimeProperty = $reflection->getProperty('endTime');
        $endTimeProperty->setAccessible(true);
        $peakMemoryUsageProperty = $reflection->getProperty('peakMemoryUsage');
        $peakMemoryUsageProperty->setAccessible(true);

        $performanceMonitor->start();
        usleep(50000);
        $performanceMonitor->stop();

        $this->assertNotNull($endTimeProperty->getValue($performanceMonitor));
        $this->assertNotNull($peakMemoryUsageProperty->getValue($performanceMonitor));
    }

    public function testGetPerformanceMetrics(): void
    {
        $performanceMonitor = new ResourceUtilization();
        $performanceMonitor->start();
        usleep(50000);
        $performanceMonitor->stop();

        $performanceMetrics = $performanceMonitor->getPerformanceMetrics();

        $this->assertInstanceOf(PerformanceMetricsDTO::class, $performanceMetrics);

        $reflection = new \ReflectionClass($performanceMetrics);
        $executionTimeProperty = $reflection->getProperty('executionTime');
        $executionTimeProperty->setAccessible(true);
        $peakMemoryUsageProperty = $reflection->getProperty('peakMemoryUsage');
        $peakMemoryUsageProperty->setAccessible(true);

        $this->assertGreaterThan(0, $executionTimeProperty->getValue($performanceMetrics));
        $this->assertGreaterThan(0, $peakMemoryUsageProperty->getValue($performanceMetrics));
    }
}
