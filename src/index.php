<?php

$filename = __DIR__ . "/sample.php";

// Output the results
$comments = getCommentsFromFile($filename);
print_r($comments);
$lineCounts = countCommentLines($comments);
print_r($lineCounts);
$totalLines = countTotalLines($filename);
echo "Total lines in file: $totalLines\n";

function getCommentsFromFile(string $filename): array
{
    $code = file_get_contents($filename);

    // Regex patterns for different types of comments
    $patterns = [
        'singleLine' => '/\/\/(?!.*\b(?:todo|fixme)\b:?).*/i', // Matches // comments, excludes TODO/FIXME, case-insensitive
        'multiLine'  => '/\/\*(?!\*|\s*\*.*\b(?:todo|fixme)\b:?).+?\*\//is', // Matches /* */ comments, excludes /** */ and those containing TODO/FIXME, case-insensitive
        'docBlock'   => '/\/\*\*(?!\s*\*\/)(?![\s\S]*?\b(license|copyright|permission)\b).+?\*\//is', // Matches docblocks, excludes licenses
        'todo'       => '/(?:\/\/|#|\/\*.*?\*\/).*\btodo\b:?.*/i', // Matches TODO comments, optional colon, case-insensitive
        'fixme'      => '/(?:\/\/|#|\/\*.*?\*\/).*\bfixme\b:?.*/i', // Matches FIXME comments, optional colon, case-insensitive
        'license'    => '/\/\*\*.*?\b(license|copyright|permission)\b.*?\*\//is' // Matches license information within docblocks
    ];

    // Array to store results
    $comments = [
        'singleLine' => [],
        'multiLine'  => [],
        'docBlock'   => [],
        'todo'       => [],
        'fixme'      => [],
        'license'    => [] // New category for license comments
    ];

    // Apply regex patterns to find comments
    foreach ($patterns as $type => $pattern) {
        preg_match_all($pattern, $code, $matches);
        $comments[$type] = $matches[0];
    }

    return $comments;
}

function countCommentLines(array $comments): array
{
    $lineCounts = [];
    foreach ($comments as $type => $commentArray) {
        $lineCounts[$type] = 0;
        foreach ($commentArray as $comment) {
            // Count the number of newlines in each comment and add 1 for the line itself
            $lineCounts[$type] += substr_count($comment, "\n") + 1;
        }
    }
    $lineCounts['total'] = array_sum($lineCounts);
    return $lineCounts;
}

function countTotalLines(string $filename): int {
    $fileContent = file($filename);
    return count($fileContent);
}

