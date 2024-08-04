<?php

namespace SavinMikhail\CommentsDensity\DTO\Output;

final class CommentDTO
{
    /**
     * @readonly
     */
    public string $commentType;
    /**
     * @readonly
     */
    public string $commentTypeColor;
    /**
     * @readonly
     */
    public string $file;
    /**
     * @readonly
     */
    public int $line;
    /**
     * @readonly
     */
    public string $content;
    public function __construct(string $commentType, string $commentTypeColor, string $file, int $line, string $content)
    {
        $this->commentType = $commentType;
        $this->commentTypeColor = $commentTypeColor;
        $this->file = $file;
        $this->line = $line;
        $this->content = $content;
    }
}
