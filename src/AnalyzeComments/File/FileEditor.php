<?php

namespace SavinMikhail\CommentsDensity\AnalyzeComments\File;

use PhpToken;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Exception\CommentsDensityException;

final readonly class FileEditor
{
    public function updateCommentInFile(CommentDTO $comment): void
    {
        $fileContent = file_get_contents($comment->file);
        $tokens = PhpToken::tokenize($fileContent);
        $changed = false;
        foreach ($tokens as $token) {
            if ($token->line !== $comment->line) {
                continue;
            }
            if (!$token->is([T_COMMENT, T_DOC_COMMENT])) {
                continue;
            }
            $token->text = $comment->content;
            $changed = true;
        }
        if (!$changed) {
            return;
        }
        $this->save($comment->file, $tokens);
    }

    /**
     * @param PhpToken[] $tokens
     * @throws CommentsDensityException
     */
    private function save(string $file, array $tokens): void
    {
        $content = implode('', array_map(static fn(PhpToken $token) => $token->text, $tokens));
        $res = file_put_contents($file, $content);
        if ($res === false) {
            throw new CommentsDensityException('failed to write to file: ' . $file);
        }
    }
}