<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\Analyzer;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SavinMikhail\CommentsDensity\Analyzer\Analyzer;
use SavinMikhail\CommentsDensity\Analyzer\AnalyzerFactory;
use SavinMikhail\CommentsDensity\Baseline\Storage\BaselineStorageInterface;
use SavinMikhail\CommentsDensity\Cache\Cache;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Input\MissingDocblockConfigDTO;
use Symfony\Component\Console\Output\OutputInterface;

final class AnalyzerFactoryTest extends TestCase
{
    public function testGetAnalyzer(): void
    {
        // Mock the dependencies
        $configDto = $this->createMock(ConfigDTO::class);
        $configDto->docblockConfigDTO = $this->createMock(MissingDocblockConfigDTO::class);
        $configDto->only = [];
        $configDto->thresholds = [];
        $configDto->cacheDir = '/path/to/cache';

        $output = $this->createMock(OutputInterface::class);
        $baselineStorage = $this->createMock(BaselineStorageInterface::class);

        // Create the factory
        $factory = new AnalyzerFactory();

        // Get the Analyzer instance
        $analyzer = $factory->getAnalyzer($configDto, $output, $baselineStorage);

        // Assert that the returned object is an instance of Analyzer
        $this->assertInstanceOf(Analyzer::class, $analyzer);

        // Use reflection to access the private property 'cache'
        $reflection = new ReflectionClass($analyzer);
        $cacheProperty = $reflection->getProperty('cache');
        $cacheProperty->setAccessible(true);

        // Assert that the Cache instance is set correctly in the Analyzer
        $this->assertInstanceOf(Cache::class, $cacheProperty->getValue($analyzer));
    }
}

