<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output;

final readonly class CommentDTO
{
    public function __construct(
        public string $commentType,
        public string $commentTypeColor,
        public string $file,
        public int $line,
        public string $content,
    ) {}

    public static function __set_state(array $array): self
    {
        return new self(
            $array['commentType'],
            $array['commentTypeColor'],
            $array['file'],
            $array['line'],
            $array['content'],
        );
    }
}
