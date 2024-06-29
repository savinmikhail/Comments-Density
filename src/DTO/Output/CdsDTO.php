<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\DTO\Output;

final class CdsDTO
{
    /**
     * @readonly
     */
    public float $cds;
    /**
     * @readonly
     */
    public string $color;
    public function __construct(float $cds, string $color)
    {
        $this->cds = $cds;
        $this->color = $color;
    }
}
