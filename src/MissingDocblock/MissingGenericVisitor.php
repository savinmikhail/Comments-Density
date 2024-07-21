<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Expr\Assign;

use function array_unique;

class MissingGenericVisitor extends NodeVisitorAbstract
{
    public bool $hasConsistentTypes = false;

    /** @var string[] */
    public array $elementTypes = [];

    public function enterNode(Node $node): null
    {
        if ($node instanceof Return_) {
            $this->analyzeExpression($node->expr);
            return null;
        }
        if ($node instanceof Yield_ || $node instanceof YieldFrom) {
            $this->analyzeExpression($node->value);
            return null;
        }

        return null;
    }

    private function analyzeExpression(Expr $expr): void
    {
        if ($expr instanceof Array_) {
            foreach ($expr->items as $item) {
                $this->analyzeExpression($item->value);
            }
        } elseif ($expr instanceof New_) {
            if ($expr->class instanceof Name) {
                $this->elementTypes[] = $expr->class->toString();
            }
        } elseif ($expr instanceof Variable) {
            $this->elementTypes[] = 'variable';
        } elseif ($expr instanceof FuncCall || $expr instanceof StaticCall || $expr instanceof MethodCall) {
            $this->elementTypes[] = 'function_call';
        } elseif ($expr instanceof Cast) {
            $this->analyzeExpression($expr->expr);
        } elseif ($expr instanceof Yield_ || $expr instanceof YieldFrom) {
            $this->analyzeExpression($expr->value);
        } elseif ($expr instanceof StaticPropertyFetch) {
            $this->elementTypes[] = 'static_property';
        } elseif ($expr instanceof Assign) {
            $this->analyzeExpression($expr->expr);
        } elseif ($expr instanceof Instanceof_) {
            $this->analyzeExpression($expr->expr);
        } elseif ($expr instanceof ConstFetch) {
            $this->elementTypes[] = 'constant';
        }
    }

    public function leaveNode(Node $node): null
    {
        if (!empty($this->elementTypes)) {
            $this->hasConsistentTypes = count(array_unique($this->elementTypes)) === 1;
        }

        return null;
    }
}
