<?php

namespace App\Main\Plugin;

use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\Report;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\RegularComment;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;
use SavinMikhail\CommentsDensity\AnalyzeComments\File\FileEditor;
use SavinMikhail\CommentsDensity\Plugin\PluginInterface;

final readonly class RemoveRelaxComment implements PluginInterface
{
    public function handle(Report $report, Config $config): void
    {
        foreach ($report->comments as $comment) {
            if ($comment->commentType !== RegularComment::NAME) {
                continue;
            }

            if (!str_contains($comment->content, '// relax')) {
                continue;
            }

            $newComment = new CommentDTO(
                commentType: $comment->commentType,
                commentTypeColor: $comment->commentTypeColor,
                file: $comment->file,
                line: $comment->line,
                content: '',
            );
            (new FileEditor())->updateCommentInFile($newComment);
        }
    }
}
