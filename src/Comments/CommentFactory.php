<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Comments;

class CommentFactory
{
    private array $commentTypes;

    public function __construct()
    {
        $this->commentTypes =  [
            new TodoComment(),
            new FixMeComment(),
            new RegularComment(),
            new LicenseComment(),
            new DocBlockComment(),
        ];
    }

    public function getCommentTypes(): array
    {
        return $this->commentTypes;
    }

    public function getCommentType(string $name): ?CommentTypeInterface
    {
        foreach ($this->commentTypes as $commentType) {
            if ($commentType->getName() === $name) {
                return $commentType;
            }
        }
        return null;
    }

    public function classifyComment(string $token): ?CommentTypeInterface
    {
        foreach ($this->commentTypes as $commentType) {
            if ($commentType->is($token)) {
                return $commentType;
            }
        }
        return null;
    }
}
