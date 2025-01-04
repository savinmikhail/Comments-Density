<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\AnalyzeComments\Comments;

use function in_array;

final readonly class CommentTypeFactory
{
    /** @var CommentTypeInterface[] */
    private array $commentTypes;

    /**
     * @param string[] $allowedTypes
     */
    public function __construct(private array $allowedTypes = [])
    {
        $this->commentTypes =  [ // the order is important (might be fix with more sophisticated regexps though
            new LicenseComment(),
            new DocBlockComment(),
            new TodoComment(),
            new FixMeComment(),
            new RegularComment(),
            new MissingDocBlock(),
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
