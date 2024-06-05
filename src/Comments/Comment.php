<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Comments;

abstract class Comment
{
    protected bool $exceedThreshold = false;

    public function isExceededThreshold(): bool
    {
        return $this->exceedThreshold;
    }

    /**
     * @return array<int, CommentTypeInterface>
     */
    public static function getTypes(): array
    {
        return [
            new RegularComment(),
            new DocBlockComment(),
            new FixMeComment(),
            new TodoComment(),
            new LicenseComment(),
        ];
    }
}