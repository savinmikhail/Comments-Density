<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\MissingDocblock;

use SavinMikhail\CommentsDensity\DTO\Input\MissingDocblockConfigDTO;

use function is_array;

use const T_CLASS;
use const T_CONST;
use const T_DOC_COMMENT;
use const T_ENUM;
use const T_FUNCTION;
use const T_INTERFACE;
use const T_TRAIT;
use const T_VARIABLE;

final class MissingDocBlockAnalyzer
{
    private bool $exceedThreshold = false;

    public function __construct(
        private readonly Tokenizer $tokenizer,
        private readonly MissingDocblockConfigDTO $missingDocblockConfigDTO,
    ) {
    }

    /**
     * Analyzes the tokens of a file for docblocks.
     *
     * @param array $tokens The tokens to analyze.
     * @return array The analysis results.
     */
    private function analyzeTokens(array $tokens, string $filename): array
    {
        $lastDocBlock = null;
        $missingDocBlocks = [];
        $tokenCount = count($tokens);

        for ($i = 0; $i < $tokenCount; $i++) {
            $token = $tokens[$i];

            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_DOC_COMMENT) {
                $lastDocBlock = $token[1];
            } elseif ($this->isDocBlockRequired($token, $tokens, $i)) {
                if (empty($lastDocBlock)) {
                    $missingDocBlocks[] = $this->createMissingDocBlockStat($token, $filename);
                }
                $lastDocBlock = null;
            }
        }

        return $missingDocBlocks;
    }

    private function shouldAnalyzeToken(array $token): bool
    {
        return match ($token[0]) {
            T_CLASS => $this->missingDocblockConfigDTO->class,
            T_TRAIT => $this->missingDocblockConfigDTO->trait,
            T_INTERFACE => $this->missingDocblockConfigDTO->interface,
            T_ENUM => $this->missingDocblockConfigDTO->enum,
            T_FUNCTION => $this->missingDocblockConfigDTO->function,
            T_CONST => $this->missingDocblockConfigDTO->constant,
            T_VARIABLE => $this->missingDocblockConfigDTO->property,
            default => false,
        };
    }

    private function isDocBlockRequired(array $token, array $tokens, int $index): bool
    {
        if (!$this->shouldAnalyzeToken($token)) {
            return false;
        }

        if ($this->tokenizer->isClass($token)) {
               return ! $this->tokenizer->isAnonymousClass($tokens, $index)
                && !$this->tokenizer->isClassNameResolution($tokens, $index);
        }

        if ($this->tokenizer->isFunction($token)) {
             return  ! $this->tokenizer->isAnonymousFunction($tokens, $index)
                && ! $this->tokenizer->isFunctionImport($tokens, $index);
        }

        if ($this->tokenizer->isVariable($token)) {
            return $this->tokenizer->isPropertyOrConstant($tokens, $index);
        }

        if ($this->tokenizer->isConst($token)) {
            return $this->tokenizer->isPropertyOrConstant($tokens, $index);
        }

        return true;
    }

    private function createMissingDocBlockStat(array $token, string $filename): array
    {
        return [
            'type' => 'missingDocblock',
            'content' => '',
            'file' => $filename,
            'line' => $token[2]
        ];
    }

    public function getMissingDocblocks(array $tokens, string $filename): array
    {
        return $this->analyzeTokens($tokens, $filename);
    }

    public function getColor(): string
    {
        return 'red';
    }

    public function getStatColor(float $count, array $thresholds): string
    {
        if (! isset($thresholds['missingDocBlock'])) {
            return 'white';
        }
        if ($count <= $thresholds['missingDocBlock']) {
            return 'green';
        }
        $this->exceedThreshold = true;
        return 'red';
    }

    public function hasExceededThreshold(): bool
    {
        return $this->exceedThreshold;
    }

    public function getName(): string
    {
        return 'missingDocblock';
    }
}
