<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\DTO\Input;

final class ConfigDTO
{
    /**
     * @var array<string, float>
     * @readonly
     */
    public array $thresholds;
    /**
     * @var string[]
     * @readonly
     */
    public array $exclude;
    /**
     * @var OutputDTO
     * @readonly
     */
    public OutputDTO $output;
    /**
     * @var string[]
     * @readonly
     */
    public array $directories;
    /**
     * @var string[]
     * @readonly
     */
    public array $only;
    /**
     * @var MissingDocblockConfigDTO
     * @readonly
     */
    public MissingDocblockConfigDTO $docblockConfigDTO;
    /**
     * @var bool
     * @readonly
     */
    public bool $useBaseline;
    /**
     * @param array<string, float> $thresholds
     * @param string[] $exclude
     * @param OutputDTO $output
     * @param string[] $directories
     * @param string[] $only
     * @param MissingDocblockConfigDTO $docblockConfigDTO
     * @param bool $useBaseline
     */
    public function __construct(array $thresholds, array $exclude, OutputDTO $output, array $directories, array $only, MissingDocblockConfigDTO $docblockConfigDTO, bool $useBaseline)
    {
        $this->thresholds = $thresholds;
        $this->exclude = $exclude;
        $this->output = $output;
        $this->directories = $directories;
        $this->only = $only;
        $this->docblockConfigDTO = $docblockConfigDTO;
        $this->useBaseline = $useBaseline;
    }
}
