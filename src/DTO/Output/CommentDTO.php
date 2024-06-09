<?php

namespace SavinMikhail\CommentsDensity\DTO\Output;

final readonly class CommentDTO
{
    public function __construct(
        public string $commentType,
        public string $commentTypeColor,
        public string $file,
        public int $line,
        public string $content,
    ) {
    }
}
