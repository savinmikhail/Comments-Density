<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock\Visitors;

use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;

use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;

final class MethodRegistrar
{
    private array $variableTypes = [];

    public function __construct(private readonly ?Class_ $class)
    {
    }

    public function registerClassMethod(ClassMethod $node): void
    {
        if ($this->class) {
            $className = $this->class->namespacedName->toString();
            $this->variableTypes['this'] = $className;
        }
        $stmts = $node->getStmts();
        if (!$stmts) {
            return;
        }
        foreach ($stmts as $stmt) {
            if (!($stmt instanceof Expression && $stmt->expr instanceof Assign)) {
                continue;
            }
            $var = $stmt->expr->var;
            $expr = $stmt->expr->expr;
            if ($var instanceof Variable && $expr instanceof New_) {
                $this->variableTypes[$var->name] = $expr->class->name;
            }
        }
    }

    public function getVariableTypes(): array
    {
        return $this->variableTypes;
    }
}
