<?php

namespace SavinMikhail\CommentsDensity\DTO\Output;

final class CommentStatisticsDTO
{
    /**
     * @readonly
     */
    public string $typeColor;
    /**
     * @readonly
     */
    public string $type;
    /**
     * @readonly
     */
    public int $lines;
    /**
     * @readonly
     */
    public string $color;
    /**
     * @readonly
     */
    public int $count;
    public function __construct(string $typeColor, string $type, int $lines, string $color, int $count)
    {
        $this->typeColor = $typeColor;
        $this->type = $type;
        $this->lines = $lines;
        $this->color = $color;
        $this->count = $count;
    }
}
