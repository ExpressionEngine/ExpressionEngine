<?php

require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../ExpressionEngine/Addons/file/FT/FileFtReplaceReplaceTest.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$target = realpath(PATH_ADDONS . 'file/ft.file.php');
$method = new ReflectionMethod('File_ft', '_wrap_it');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

/**
 * Reset the shared mocks and seed the formatter + loader doubles used by replace_replace().
 *
 * @param mixed $formattedUrl
 * @return FileFtReplaceReplaceLoadRecorder
 */
function file_ft_wrap_it_bootstrap($formattedUrl)
{
    $loadRecorder = new FileFtReplaceReplaceLoadRecorder();

    ee()->resetMocks();
    ee()->setMock('load', $loadRecorder);
    ee()->setMock('Format', new FileFtReplaceReplaceFormatRecorder($formattedUrl));

    return $loadRecorder;
}

if ($xdebugAvailable) {
    $coverageFlags = 0;

    if (defined('XDEBUG_CC_UNUSED')) {
        $coverageFlags |= XDEBUG_CC_UNUSED;
    }

    if (defined('XDEBUG_CC_DEAD_CODE')) {
        $coverageFlags |= XDEBUG_CC_DEAD_CODE;
    }

    if (defined('XDEBUG_CC_BRANCH_CHECK')) {
        $coverageFlags |= XDEBUG_CC_BRANCH_CHECK;
    }

    if ($coverageFlags > 0) {
        xdebug_start_code_coverage($coverageFlags);
    }

    if ($coverageFlags === 0) {
        xdebug_start_code_coverage();
    }
}

file_ft_wrap_it_bootstrap('https://cdn.example.com/files/final-guide.pdf');
(new File_ft())->replace_replace([
    'url' => 'https://cdn.example.com/files/draft-guide.pdf',
    'filename' => 'Guide Download',
], [
    'find' => 'draft',
    'replace' => 'final',
    'wrap' => 'plain',
]);

file_ft_wrap_it_bootstrap('https://cdn.example.com/files/final-guide.pdf');
(new File_ft())->replace_replace([
    'url' => 'https://cdn.example.com/files/draft-guide.pdf',
    'filename' => 'Guide Download',
    'file_pre_format' => '<p>',
    'file_post_format' => '</p>',
    'file_properties' => 'class="download" rel="noopener"',
], [
    'find' => 'draft',
    'replace' => 'final',
    'wrap' => 'link',
]);

file_ft_wrap_it_bootstrap('https://cdn.example.com/images/hero-final.png');
(new File_ft())->replace_replace([
    'url' => 'https://cdn.example.com/images/hero-draft.png',
    'filename' => 'Hero Banner',
    'image_pre_format' => '<figure>',
    'image_post_format' => '</figure>',
    'image_properties' => 'width="1280" height="720" loading="lazy"',
], [
    'find' => 'draft',
    'replace' => 'final',
    'wrap' => 'image',
]);

file_ft_wrap_it_bootstrap('https://cdn.example.com/images/hero-final.png');
(new File_ft())->replace_replace([
    'url' => 'https://cdn.example.com/images/hero-draft.png',
    'filename' => 'Hero Banner',
    'image_pre_format' => '<figure>',
    'image_post_format' => '</figure>',
    'image_properties' => '',
], [
    'find' => 'draft',
    'replace' => 'final',
    'wrap' => 'image',
]);

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['File_ft->_wrap_it'] ?? ['branches' => [], 'paths' => []];
$coveredLines = [];
$sourceLines = file($target, FILE_IGNORE_NEW_LINES) ?: [];

foreach (range($method->getStartLine(), $method->getEndLine()) as $line) {
    if (! array_key_exists($line, $fileCoverage['lines'] ?? [])) {
        continue;
    }

    $sourceLine = trim($sourceLines[$line - 1] ?? '');

    if ($sourceLine === '{' || $sourceLine === '}') {
        continue;
    }

    $coveredLines[$line] = (($fileCoverage['lines'][$line] ?? 0) > 0);
}

$coveredBranches = [];
$totalBranches = 0;

foreach (($functionCoverage['branches'] ?? []) as $branchId => $branch) {
    if (count($branch['out_hit'] ?? []) <= 1) {
        continue;
    }

    foreach (($branch['out_hit'] ?? []) as $edgeIndex => $hitCount) {
        $totalBranches++;
        $coveredBranches[$branchId . ':' . $edgeIndex] = ($hitCount > 0);
    }
}

$linePercentage = count($coveredLines) > 0
    ? count(array_filter($coveredLines)) / count($coveredLines) * 100
    : 0.0;
$branchPercentage = $totalBranches > 0
    ? count(array_filter($coveredBranches)) / count($coveredBranches) * 100
    : 100.0;

file_put_contents($outputFile, json_encode([
    'real_module_path' => $target,
    'xdebug_available' => $xdebugAvailable,
    'line_percentage' => $linePercentage,
    'branch_percentage' => $branchPercentage,
    'total_branches' => $totalBranches,
    'lines' => $coveredLines,
    'branches' => $functionCoverage['branches'] ?? [],
    'paths' => $functionCoverage['paths'] ?? [],
    'covered_branches' => $coveredBranches,
    'uncovered_lines' => array_values(array_keys(array_filter($coveredLines, function ($covered) {
        return ! $covered;
    }))),
    'uncovered_branches' => array_values(array_keys(array_filter($coveredBranches, function ($covered) {
        return ! $covered;
    }))),
], JSON_PRETTY_PRINT));
