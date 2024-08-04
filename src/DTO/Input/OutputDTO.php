<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\DTO\Input;

final class OutputDTO
{
    /**
     * @readonly
     */
    public string $type;
    /**
     * @readonly
     */
    public string $file;
    public function __construct(string $type, string $file)
    {
        $this->type = $type;
        $this->file = $file;
    }
}
