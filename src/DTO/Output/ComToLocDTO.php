<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\DTO\Output;

final class ComToLocDTO
{
    /**
     * @readonly
     */
    public float $comToLoc;
    /**
     * @readonly
     */
    public string $color;
    public function __construct(float $comToLoc, string $color)
    {
        $this->comToLoc = $comToLoc;
        $this->color = $color;
    }
}
