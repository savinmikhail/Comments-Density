<?php

namespace App\Main\Plugin;

use PhpToken;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\CommentDTO;
use SavinMikhail\CommentsDensity\AnalyzeComments\Analyzer\DTO\Output\Report;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\FixMeComment;
use SavinMikhail\CommentsDensity\AnalyzeComments\Comments\TodoComment;
use SavinMikhail\CommentsDensity\AnalyzeComments\Config\DTO\Config;
use SavinMikhail\CommentsDensity\Plugin\PluginInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class OpenIssue implements PluginInterface
{
    private const YOUTRACK_URL = 'https://yt';
    private const AUTHORIZATION_TOKEN = '';
    private const PROJECT_ID = '59-178';
    private const STAGE = 'Второй этап';
    private const BRANCH_NAME = 'develop';
    private const GITLAB_PROJECT_URL = 'https://gitlab/backend';

    public function handle(Report $report, Config $config): void
    {
        $httpClient = HttpClient::create();

        foreach ($report->comments as $comment) {
            if (!in_array($comment->commentType, [TodoComment::NAME, FixMeComment::NAME], true)) {
                continue;
            }

            if ($this->hasIssueUrl($comment->content)) {
                continue;
            }

            $draftId = $this->createDraft($httpClient, $comment);
            $issueId = $this->createIssueFromDraft($httpClient, $draftId);
            $issueUrl = self::YOUTRACK_URL . '/issue/' . $issueId;
            $this->updateCommentInFile($comment, $issueUrl);
        }
    }

    private function buildDescription(CommentDTO $comment): string
    {
        $gitlabUrl = self::GITLAB_PROJECT_URL . '/-/blob/' . self::BRANCH_NAME . $comment->file . '?ref_type=heads#L' . $comment->line;
        return
            "**Comment**: $comment->content \n"
            . "**File**: $comment->file \n"
            . "**Line**: $comment->line \n"
            . "**Gitlab url**: $gitlabUrl \n";
    }

    private function buildSummary(): string
    {
        return 'Сфера > ' . self::STAGE . ' > Back > Техдолг';
    }

    private function createDraft(HttpClientInterface $httpClient, CommentDTO $comment): string
    {
        $response = $httpClient->request(
            'POST',
            self::YOUTRACK_URL . '/api/users/me/drafts?$top=-1&fields=$type,applicableActions(description,executing,id,name,userInputType),attachments($type,author(fullName,id,ringId),comment(id,visibility($type)),created,id,imageDimensions(height,width),issue(id,project(id,ringId),visibility($type)),mimeType,name,removed,size,thumbnailURL,url,visibility($type,implicitPermittedUsers($type,avatarUrl,banBadge,banned,email,fullName,id,isLocked,issueRelatedGroup(icon),login,name,online,profiles(general(trackOnlineStatus)),ringId),permittedGroups($type,allUsersGroup,icon,id,name,ringId),permittedUsers($type,avatarUrl,banBadge,banned,email,fullName,id,isLocked,issueRelatedGroup(icon),login,name,online,profiles(general(trackOnlineStatus)),ringId))),canAddPublicComment,canUpdateVisibility,comments(attachments($type,author(fullName,id,ringId),comment(id,visibility($type)),created,id,imageDimensions(height,width),issue(id,project(id,ringId),visibility($type)),mimeType,name,removed,size,thumbnailURL,url,visibility($type,implicitPermittedUsers($type,avatarUrl,banBadge,banned,email,fullName,id,isLocked,issueRelatedGroup(icon),login,name,online,profiles(general(trackOnlineStatus)),ringId),permittedGroups($type,allUsersGroup,icon,id,name,ringId),permittedUsers($type,avatarUrl,banBadge,banned,email,fullName,id,isLocked,issueRelatedGroup(icon),login,name,online,profiles(general(trackOnlineStatus)),ringId))),id),created,description,externalIssue(key,name,url),fields($type,hasStateMachine,id,isUpdatable,name,projectCustomField($type,bundle(id),canBeEmpty,emptyFieldText,field(fieldType(isMultiValue,valueType),id,localizedName,name,ordinal),id,isEstimation,isPublic,isSpentTime,ordinal,size),value($type,archived,avatarUrl,buildIntegration,buildLink,color(background,id),description,fullName,id,isResolved,localizedName,login,markdownText,minutes,name,presentation,ringId,text)),hasEmail,hiddenAttachmentsCount,id,idReadable,isDraft,links(direction,id,issuesSize,linkType(aggregation,directed,localizedName,localizedSourceToTarget,localizedTargetToSource,name,sourceToTarget,targetToSource,uid),trimmedIssues($type,comments($type),created,id,idReadable,isDraft,numberInProject,project(id,ringId),reporter(id),resolved,summary,voters(hasVote),votes,watchers(hasStar)),unresolvedIssuesSize),mentionedArticles(idReadable,summary),mentionedIssues(idReadable,resolved,summary),mentionedUsers($type,avatarUrl,banBadge,banned,canReadProfile,fullName,id,isLocked,login,name,ringId),messages,numberInProject,project($type,id,isDemo,leader(id),name,plugins(helpDeskSettings(enabled),timeTrackingSettings(enabled,estimate(field(id,name),id),timeSpent(field(id,name),id)),vcsIntegrationSettings(processors(enabled,migrationFailed,server(enabled,url),upsourceHubResourceKey,url))),ringId,shortName,team($type,allUsersGroup,icon,id,name,ringId)),reporter($type,avatarUrl,banBadge,banned,email,fullName,id,isLocked,issueRelatedGroup(icon),login,name,online,profiles(general(trackOnlineStatus)),ringId),resolved,summary,tags(color(id),id,isUpdatable,isUsable,name,owner(id),query),updated,updater($type,avatarUrl,banBadge,banned,email,fullName,id,isLocked,issueRelatedGroup(icon),login,name,online,profiles(general(trackOnlineStatus)),ringId),usesMarkdown,visibility($type,implicitPermittedUsers($type,avatarUrl,banBadge,banned,email,fullName,id,isLocked,issueRelatedGroup(icon),login,name,online,profiles(general(trackOnlineStatus)),ringId),permittedGroups($type,allUsersGroup,icon,id,name,ringId),permittedUsers($type,avatarUrl,banBadge,banned,email,fullName,id,isLocked,issueRelatedGroup(icon),login,name,online,profiles(general(trackOnlineStatus)),ringId)),voters(hasVote),votes,watchers(hasStar),widgets(base,indexPath,place,pluginName)',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . self::AUTHORIZATION_TOKEN,
                ],
                'json' => [
                    'summary' => $this->buildSummary(),
                    'description' => $this->buildDescription($comment),
                    'project' => ['id' => self::PROJECT_ID],
                ],
            ]
        );
        $response = $response->toArray();
        return $response['id']; // looks like 68-107353
    }

    private function createIssueFromDraft(HttpClientInterface $httpClient, string $draftId): string
    {
        $response = $httpClient->request(
            'POST',
            self::YOUTRACK_URL . "/api/issues?draftId={$draftId}&\$top=-1&fields=id,idReadable,numberInProject,messages",
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . self::AUTHORIZATION_TOKEN,
                ],
                'json' =>  [
                    'issueId' => $draftId,
                ],
            ]
        );
        $response = $response->toArray();
        return $response['idReadable']; // looks like backend-287
    }

    private function hasIssueUrl(string $commentContent): bool
    {
        return (bool)preg_match('/https:\/\/yt\.kr\.digital\/issue\/\w+-\d+/', $commentContent);
    }

    private function updateCommentInFile(CommentDTO $comment, string $issueUrl): void
    {
        $fileContent = file_get_contents($comment->file);
        $tokens = \PhpToken::tokenize($fileContent);
        $changed = false;
        foreach ($tokens as $token) {
            if ($token->line === $comment->line && $token->is([T_COMMENT, T_DOC_COMMENT]) ) {
                $token->text = rtrim($token->text) . " ($issueUrl)";
                $changed = true;
            }
        }
        if (!$changed) {
            return;
        }
        $this->save($comment->file, $tokens);
    }

    /**
     * @param PhpToken[] $tokens
     */
    private function save(string $file, array $tokens): void
    {
        $content = implode('', array_map(static fn(\PhpToken $token) => $token->text, $tokens));
        $res = file_put_contents($file, $content);
        if ($res === false) {
            throw new \Exception('failed to write to file: ' . $file);
        }
    }
}
