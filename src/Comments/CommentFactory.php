<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\Comments;

use function in_array;

final class CommentFactory
{
    /** @var array<array-key, CommentTypeInterface> */
    private array $commentTypes;
    private array $allowedTypes;

    public function __construct(array $allowedTypes = [])
    {
        $this->commentTypes =  [
            new TodoComment(),
            new FixMeComment(),
            new RegularComment(),
            new LicenseComment(),
            new DocBlockComment(),
        ];

        $this->allowedTypes = $allowedTypes;
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
            if (! $commentType->matchesPattern($token)) {
                continue;
            }
            if (
                empty($this->allowedTypes)
                || in_array($commentType->getName(), $this->allowedTypes, true)
            ) {
                return $commentType;
            }
        }
        return null;
    }
}
