<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity;

use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\ConfigLoader;
use SavinMikhail\CommentsDensity\DTO\Input\ConfigDTO;
use SavinMikhail\CommentsDensity\DTO\Input\MissingDocblockConfigDTO;
use SavinMikhail\CommentsDensity\Exception\CommentsDensityException;
use org\bovigo\vfs\vfsStream;

class ConfigLoaderTest extends TestCase
{
    private ConfigLoader $configLoader;

    protected function setUp(): void
    {
        $this->configLoader = new ConfigLoader();
    }

    private function invokeMethod(object $object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function testParseConfigFileThrowsExceptionWhenFileDoesNotExist()
    {
        $this->expectException(CommentsDensityException::class);
        $this->expectExceptionMessage('Config file does not exists! Looking for non_existent_file.php');

        $this->invokeMethod($this->configLoader, 'parseConfigFile', ['non_existent_file.php']);
    }

    public function testParseConfigFileReturnsArrayWhenFileExists()
    {
        vfsStream::setup('root', null, ['comments_density.php' => '<?php return ["key" => "value"];']);
        $filePath = vfsStream::url('root/comments_density.php');

        $config = $this->invokeMethod($this->configLoader, 'parseConfigFile', [$filePath]);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('key', $config);
        $this->assertEquals('value', $config['key']);
    }

    public function testGetOutput()
    {
        $config = [
            'output' => [
                'file' => 'output.txt'
            ]
        ];

        $outputConfig = $this->invokeMethod($this->configLoader, 'getOutput', [$config]);

        $this->assertArrayHasKey('file', $outputConfig);
        $this->assertStringContainsString(DIRECTORY_SEPARATOR . 'output.txt', $outputConfig['file']);
    }

    public function testGetOnly()
    {
        $config = [
            'only' => ['fixme', 'todo']
        ];

        $only = $this->invokeMethod($this->configLoader, 'getOnly', [$config]);

        $this->assertEquals($config['only'], $only);
    }

    public function testGetOnlyReturnsEmptyArrayWhenNotSet()
    {
        $config = [];

        $only = $this->invokeMethod($this->configLoader, 'getOnly', [$config]);

        $this->assertEmpty($only);
    }

    public function testGetThresholds()
    {
        $config = [
            'thresholds' => ['key' => 'value']
        ];

        $thresholds = $this->invokeMethod($this->configLoader, 'getThresholds', [$config]);

        $this->assertEquals($config['thresholds'], $thresholds);
    }

    public function testGetThresholdsReturnsEmptyArrayWhenNotSet()
    {
        $config = [];

        $thresholds = $this->invokeMethod($this->configLoader, 'getThresholds', [$config]);

        $this->assertEmpty($thresholds);
    }

    public function testGetConfigDto()
    {
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
                    "requireForAllMethods" => true
                ]
            ];'
        ]);

        $configFile = vfsStream::url('root/comments_density.php');
        $configLoaderMock = $this->getMockBuilder(ConfigLoader::class)
            ->onlyMethods(['getConfig', 'getProjectRoot'])
            ->getMock();

        $configLoaderMock->method('getConfig')
            ->willReturn(require $configFile);

        $configLoaderMock->method('getProjectRoot')
            ->willReturn(vfsStream::url('root'));

        $configDto = $configLoaderMock->getConfigDto();

        $this->assertInstanceOf(ConfigDTO::class, $configDto);
    }

    public function testGetDirectoriesThrowsExceptionWhenDirectoryDoesNotExist()
    {
        $config = [
            'directories' => ['non_existent_directory']
        ];

        $this->expectException(CommentsDensityException::class);
        $this->expectExceptionMessage('non_existent_directory directory does not exist');

        $this->invokeMethod($this->configLoader, 'getDirectories', [$config]);
    }

    public function testGetDirectories()
    {
        vfsStream::setup('root', null, ['dir1' => [], 'dir2' => []]);
        $config = [
            'directories' => ['dir1', 'dir2']
        ];

        $configLoaderMock = $this->getMockBuilder(ConfigLoader::class)
            ->onlyMethods(['getProjectRoot'])
            ->getMock();

        $configLoaderMock->method('getProjectRoot')
            ->willReturn(vfsStream::url('root'));

        $directories = $this->invokeMethod($configLoaderMock, 'getDirectories', [$config]);

        $this->assertCount(2, $directories);
    }

    public function testGetProjectRoot()
    {
        $configLoaderMock = $this->getMockBuilder(ConfigLoader::class)
            ->onlyMethods(['getProjectRoot'])
            ->getMock();

        $configLoaderMock->method('getProjectRoot')
            ->willReturn('/Users/projects/tests');

        $projectRoot = $this->invokeMethod($configLoaderMock, 'getProjectRoot');

        $this->assertStringEndsWith(DIRECTORY_SEPARATOR . 'tests', $projectRoot);
    }

    public function testGetMissingDocblockConfig()
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
                'requireForAllMethods' => true
            ]
        ];

        $missingDocblockConfigDTO = $this->invokeMethod($this->configLoader, 'getMissingDocblockConfig', [$config]);

        $this->assertInstanceOf(MissingDocblockConfigDTO::class, $missingDocblockConfigDTO);
        $this->assertTrue($missingDocblockConfigDTO->class);
    }
}
