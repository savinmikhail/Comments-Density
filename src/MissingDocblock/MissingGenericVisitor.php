<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\NodeVisitorAbstract;

use function array_unique;

class MissingGenericVisitor extends NodeVisitorAbstract {
    public bool $hasConsistentTypes = false;
    public array $elementTypes = [];

    public function enterNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Return_ && $node->expr instanceof Array_) {
            foreach ($node->expr->items as $item) {
                if ($item->value instanceof New_) {
                    $this->elementTypes[] = $item->value->class->toString();
                } elseif ($item->value instanceof Variable) {
                    // Here you can add logic to infer variable types if necessary
                    $this->elementTypes[] = 'variable';
                }
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