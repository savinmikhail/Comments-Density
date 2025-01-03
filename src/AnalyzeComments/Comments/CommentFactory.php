<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Comments;

use function in_array;

final readonly class CommentFactory
{
    /** @var CommentTypeInterface[] */
    private array $commentTypes;

    /**
     * @param string[] $allowedTypes
     */
    public function __construct(private array $allowedTypes = [])
    {
        $this->commentTypes =  [
            new TodoComment(),
            new FixMeComment(),
            new RegularComment(),
            new LicenseComment(),
            new DocBlockComment(),
        ];
    }

    /**
     * @return CommentTypeInterface[]
     */
    public function getCommentTypes(): iterable
    {
        foreach ($this->commentTypes as $commentType) {
            yield $commentType;
        }
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

    public function classifyComment(string $comment): ?CommentTypeInterface
    {
        foreach ($this->commentTypes as $commentType) {
            if (! $commentType->matchesPattern($comment)) {
                continue;
            }
            if (
                $this->allowedTypes === []
                || in_array($commentType->getName(), $this->allowedTypes, true)
            ) {
                return $commentType;
            }
        }

        return null;
    }
}
