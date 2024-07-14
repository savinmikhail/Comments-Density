<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use PhpParser\Node;
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
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Expr\Assign;

use function array_unique;

class MissingGenericVisitor extends NodeVisitorAbstract
{
    public bool $hasConsistentTypes = false;
    public array $elementTypes = [];

    private array $nodeHandlers;

    public function __construct()
    {
        $this->nodeHandlers = [
            Return_::class => 'handleReturn',
            Yield_::class => 'handleYield',
            YieldFrom::class => 'handleYieldFrom',
            Array_::class => 'handleArray',
            New_::class => 'handleNew',
            Variable::class => 'handleVariable',
            FuncCall::class => 'handleFunctionCall',
            StaticCall::class => 'handleFunctionCall',
            MethodCall::class => 'handleFunctionCall',
            Cast::class => 'handleCast',
            StaticPropertyFetch::class => 'handleStaticPropertyFetch',
            Assign::class => 'handleAssign',
            Instanceof_::class => 'handleInstanceof',
            ConstFetch::class => 'handleConstFetch',
        ];
    }

    public function enterNode(Node $node): void
    {
        foreach ($this->nodeHandlers as $nodeClass => $handlerMethod) {
            if ($node instanceof $nodeClass) {
                $this->{$handlerMethod}($node);
                break;
            }
        }
    }

    private function handleReturn(Return_ $node): void
    {
        $this->analyzeExpression($node->expr);
    }

    private function handleYield(Yield_ $node): void
    {
        $this->analyzeExpression($node->value);
    }

    private function handleYieldFrom(YieldFrom $node): void
    {
        $this->analyzeExpression($node->expr);
    }

    private function handleArray(Array_ $node): void
    {
        foreach ($node->items as $item) {
            $this->analyzeExpression($item->value);
        }
    }

    private function handleNew(New_ $node): void
    {
        if ($node->class instanceof Node\Name) {
            $this->elementTypes[] = $node->class->toString();
        }
    }

    private function handleVariable(Variable $node): void
    {
        $this->elementTypes[] = 'variable';
    }

    private function handleFunctionCall(Node $node): void
    {
        $this->elementTypes[] = 'function_call';
    }

    private function handleCast(Cast $node): void
    {
        $this->analyzeExpression($node->expr);
    }

    private function handleStaticPropertyFetch(StaticPropertyFetch $node): void
    {
        $this->elementTypes[] = 'static_property';
    }

    private function handleAssign(Assign $node): void
    {
        $this->analyzeExpression($node->expr);
    }

    private function handleInstanceof(Instanceof_ $node): void
    {
        $this->analyzeExpression($node->expr);
    }

    private function handleConstFetch(ConstFetch $node): void
    {
        $this->elementTypes[] = 'constant';
    }

    private function analyzeExpression($expr): void
    {
        foreach ($this->nodeHandlers as $nodeClass => $handlerMethod) {
            if ($expr instanceof $nodeClass) {
                $this->{$handlerMethod}($expr);
                break;
            }
        }
    }

    public function leaveNode(Node $node): void
    {
        if (!empty($this->elementTypes)) {
            $this->hasConsistentTypes = count(array_unique($this->elementTypes)) === 1;
        }
    }
}
