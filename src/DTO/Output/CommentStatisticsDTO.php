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
    public int $count;
    /**
     * @readonly
     */
    public string $color;
    public function __construct(string $typeColor, string $type, int $count, string $color)
    {
        $this->typeColor = $typeColor;
        $this->type = $type;
        $this->count = $count;
        $this->color = $color;
    }
}
