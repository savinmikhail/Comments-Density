<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\ConfigLoader;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\MissingDocblockConfigDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Exception\CommentsDensityException;

use function define;

use const DIRECTORY_SEPARATOR;

final class ConfigLoaderTest extends TestCase
{
    private ConfigLoader $configLoader;

    protected function setUp(): void
    {
        define('COMMENTS_DENSITY_ENVIRONMENT', 'test');
        $this->configLoader = new ConfigLoader();
    }

    public function testParseConfigFileThrowsExceptionWhenFileDoesNotExist(): void
    {
        $this->expectException(CommentsDensityException::class);
        $this->expectExceptionMessage('Config file does not exists! Looking for non_existent_file.php');

        $this->invokeMethod($this->configLoader, 'parseConfigFile', ['non_existent_file.php']);
    }

    public function testParseConfigFileReturnsArrayWhenFileExists(): void
    {
        vfsStream::setup('root', null, ['comments_density.php' => '<?php return ["key" => "value"];']);
        $filePath = vfsStream::url('root/comments_density.php');

        $config = $this->invokeMethod($this->configLoader, 'parseConfigFile', [$filePath]);

        self::assertIsArray($config);
        self::assertArrayHasKey('key', $config);
        self::assertEquals('value', $config['key']);
    }

    public function testGetOutput(): void
    {
        $config = [
            'output' => [
                'file' => 'tuptuo.html',
            ],
        ];

        $outputConfig = $this->invokeMethod($this->configLoader, 'getOutput', [$config]);

        self::assertStringContainsString(DIRECTORY_SEPARATOR . 'tuptuo.html', $outputConfig->file);
    }

    public function testGetOnly(): void
    {
        $config = [
            'only' => ['fixme', 'todo'],
        ];

        $only = $this->invokeMethod($this->configLoader, 'getOnly', [$config]);

        self::assertEquals($config['only'], $only);
    }

    public function testGetOnlyReturnsEmptyArrayWhenNotSet(): void
    {
        $config = [];

        $only = $this->invokeMethod($this->configLoader, 'getOnly', [$config]);

        self::assertEmpty($only);
    }

    public function testGetThresholds(): void
    {
        $config = [
            'thresholds' => ['key' => 'value'],
        ];

        $thresholds = $this->invokeMethod($this->configLoader, 'getThresholds', [$config]);

        self::assertEquals($config['thresholds'], $thresholds);
    }

    public function testGetThresholdsReturnsEmptyArrayWhenNotSet(): void
    {
        $config = [];

        $thresholds = $this->invokeMethod($this->configLoader, 'getThresholds', [$config]);

        self::assertEmpty($thresholds);
    }

    public function testGetConfigDto(): never
    {
        self::markTestIncomplete();
        $root = vfsStream::setup('root', null, [
            'dir1' => [],
            'dir2' => [],
            'comments_density.php' => '<?php return [
                "thresholds" => ["key" => "value"],
                "exclude" => ["dir1", "dir2"],
                "output" => ["file" => "output.txt"],
                "directories" => ["dir1", "dir2"],
                "only" => ["file1.php", "file2.php"],
                "missingDocblock" => [
                    "class" => true,
                    "interface" => true,
                    "trait" => true,
                    "enum" => true,
                    "function" => true,
                    "property" => true,
                    "constant" => true,
                    "requireForAllMethods" => true,
                ],
                "use_baseline" => true,
            ];',
        ]);

        $configFile = vfsStream::url('root/comments_density.php');
        $configLoaderMock = $this->getMockBuilder(ConfigLoader::class)
            ->onlyMethods(['getConfig', 'getProjectRoot'])
            ->getMock();

        $configLoaderMockz->method('getConfig')
            ->willReturn(require $configFile);

        $configLoaderMock->method('getProjectRoot')
            ->willReturn(vfsStream::url('root'));

        $configDto = $configLoaderMock->getConfigDto();

        self::assertInstanceOf(Config::class, $configDto);
    }

    public function testGetDirectoriesThrowsExceptionWhenDirectoryDoesNotExist(): void
    {
        $config = [
            'directories' => ['non_existent_directory'],
        ];

        $this->expectException(CommentsDensityException::class);
        $this->expectExceptionMessage('non_existent_directory directory does not exist');

        $this->invokeMethod($this->configLoader, 'getDirectories', [$config]);
    }

    public function testGetDirectories(): never
    {
        self::markTestIncomplete();

        vfsStream::setup('root', null, ['dir1' => [], 'dir2' => []]);
        $config = [
            'directories' => ['dir1', 'dir2'],
        ];

        $configLoaderMock = $this->getMockBuilder(ConfigLoader::class)
            ->onlyMethods(['getProjectRoot'])
            ->getMock();

        $configLoaderMock->method('getProjectRoot')
            ->willReturn(vfsStream::url('root'));

        $directories = $this->invokeMethod($configLoaderMock, 'getDirectories', [$config]);

        self::assertCount(2, $directories);
    }

    public function testGetProjectRoot(): never
    {
        self::markTestIncomplete();

        $configLoaderMock = $this->getMockBuilder(ConfigLoader::class)
            ->onlyMethods(['getProjectRoot'])
            ->getMock();

        $configLoaderMock->method('getProjectRoot')
            ->willReturn('/Users/projects/tests');

        $projectRoot = $this->invokeMethod($configLoaderMock, 'getProjectRoot');

        self::assertStringEndsWith(DIRECTORY_SEPARATOR . 'tests', $projectRoot);
    }

    public function testGetMissingDocblockConfig(): void
    {
        $config = [
            'missingDocblock' => [
                'class' => true,
                'interface' => true,
                'trait' => true,
                'enum' => true,
                'function' => true,
                'property' => true,
                'constant' => true,
                'requireForAllMethods' => true,
            ],
        ];

        $missingDocblockConfigDTO = $this->invokeMethod($this->configLoader, 'getMissingDocblockConfig', [$config]);

        self::assertInstanceOf(MissingDocblockConfigDTO::class, $missingDocblockConfigDTO);
        self::assertTrue($missingDocblockConfigDTO->class);
    }

    private function invokeMethod(object $object, string $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
