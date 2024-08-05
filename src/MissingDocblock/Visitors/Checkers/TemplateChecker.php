<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock\Visitors\Checkers;

use phpDocumentor\Reflection\DocBlockFactory;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use ReflectionClass;
use Roave\BetterReflection\BetterReflection;
use function class_exists;
use function interface_exists;

final readonly class TemplateChecker
{
    private DocBlockFactory $docBlockFactory;

    public function __construct()
    {
        $this->docBlockFactory = DocBlockFactory::createInstance();
    }

    public function isTemplatedClass(ComplexType|Identifier|Name|null $type): bool
    {
        if ($type === null) {
            return false;
        }
        if (!($type instanceof Name)) {
            return false;
        }

        $typeName = $type->toString();

        if (!class_exists($typeName) && !interface_exists($typeName)) {
            return false;
        }

        $class = new ReflectionClass($typeName);
        $docComment = $class->getDocComment();

        if (!$docComment) {
            $classInfo = (new BetterReflection())
                ->reflector()
                ->reflectClass(ReflectionClass::class);

            $docComment = $classInfo->getDocComment();
        }

        if (!$docComment) {
            return false;
        }

        $docBlock = $this->docBlockFactory->create($docComment);

        return !empty($docBlock->getTagsByName('template'));
    }
}