<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\DTO\Input;

final class ConfigDTO
{
    /**
     * @readonly
     */
    public array $thresholds;
    /**
     * @readonly
     */
    public array $exclude;
    /**
     * @readonly
     */
    public array $output;
    /**
     * @readonly
     */
    public array $directories;
    /**
     * @readonly
     */
    public ?array $only;
    public function __construct(array $thresholds, array $exclude, array $output, array $directories, ?array $only)
    {
        $this->thresholds = $thresholds;
        $this->exclude = $exclude;
        $this->output = $output;
        $this->directories = $directories;
        $this->only = $only;
    }
}
