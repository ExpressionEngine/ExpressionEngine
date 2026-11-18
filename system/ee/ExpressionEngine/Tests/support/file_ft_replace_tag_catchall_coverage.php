<?php

require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../ExpressionEngine/Addons/file/FT/FileFtReplaceTagCatchallTest.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$target = realpath(PATH_ADDONS . 'file/ft.file.php');
$method = new ReflectionMethod('File_ft', 'replace_tag_catchall');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

/**
 * Build a File_ft instance with the requested load mock.
 *
 * @param object|null $loadRecorder
 * @return File_ft
 */
function file_ft_replace_tag_catchall_make_fieldtype($loadRecorder = null)
{
    ee()->resetMocks();
    ee()->setMock('load', $loadRecorder ?: new FileFtLoadRecorder());

    return new File_ft();
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

file_ft_replace_tag_catchall_make_fieldtype()->replace_tag_catchall([
    'url' => 'https://cdn.example.com/files/manual.pdf',
]);

file_ft_replace_tag_catchall_make_fieldtype()->replace_tag_catchall([
    'url' => 'https://cdn.example.com/files/manual.pdf',
], [], '{frontedit}', 'frontedit');

file_ft_replace_tag_catchall_make_fieldtype()->replace_tag_catchall([
    'url' => 'https://cdn.example.com/files/banner.png',
    'path' => 'https://cdn.example.com/files/',
    'filename' => 'banner',
    'extension' => 'png',
], [], false, 'thumbs');

file_ft_replace_tag_catchall_make_fieldtype()->replace_tag_catchall([
    'url' => 'https://cdn.example.com/files/banner.png',
    'path' => 'https://cdn.example.com/files/',
    'filename' => 'banner',
], [], false, 'thumbs');

file_ft_replace_tag_catchall_make_fieldtype()->replace_tag_catchall([
    'url' => 'https://cdn.example.com/files/banner.png',
    'filename' => 'banner',
    'extension' => 'png',
], [], false, 'thumbs');

file_ft_replace_tag_catchall_make_fieldtype()->replace_tag_catchall([
    'url' => 'https://cdn.example.com/files/banner.png',
    'path' => 'https://cdn.example.com/files/',
    'extension' => 'png',
], [], false, 'thumbs');

file_ft_replace_tag_catchall_make_fieldtype()->replace_tag_catchall([
    'url' => 'https://cdn.example.com/files/banner.png',
    'url:large' => 'https://cdn.example.com/files/_large/banner.png',
], [], false, 'large');

file_ft_replace_tag_catchall_make_fieldtype()->replace_tag_catchall([
    'url' => 'https://cdn.example.com/files/banner.png',
], [], false, 'large');

file_ft_replace_tag_catchall_make_fieldtype()->replace_tag_catchall([
    'url' => 'https://cdn.example.com/files/banner.png',
    'url:large' => 'https://cdn.example.com/files/_large/banner.png',
    'path:large' => '/srv/uploads/_large/banner.png',
    'fs_filename' => 'banner.png',
    'model_object' => (object) ['file_name' => 'ignored.png'],
], [], null, 'large');

file_ft_replace_tag_catchall_make_fieldtype()->replace_tag_catchall([
    'url' => 'https://cdn.example.com/files/hero.jpg',
    'url:small' => 'https://cdn.example.com/files/_small/hero.jpg',
    'path:small' => '/srv/uploads/_small/hero.jpg',
    'model_object' => (object) ['file_name' => 'hero.jpg'],
], [], null, 'small');

file_ft_replace_tag_catchall_make_fieldtype()->replace_tag_catchall([
    'url' => 'https://cdn.example.com/files/banner.png',
    'url:large' => 'https://cdn.example.com/files/_large/banner.png',
    'model_object' => (object) ['file_name' => 'banner.png'],
], [], null, 'large');

file_ft_replace_tag_catchall_make_fieldtype()->replace_tag_catchall(false, [], null, 'large');

file_ft_replace_tag_catchall_make_fieldtype()->replace_tag_catchall([], [], '{fallback}', 'large');

$loadRecorder = new FileFtReplaceTagCatchallLoadRecorder();
file_ft_replace_tag_catchall_make_fieldtype($loadRecorder)->replace_tag_catchall([
    'filename' => 'Guide Download',
    'file_pre_format' => '<p>',
    'file_post_format' => '</p>',
    'file_properties' => 'class="download" rel="noopener"',
    'url:manual' => 'https://cdn.example.com/files/final-guide.pdf',
], [
    'wrap' => 'link',
], false, 'manual');

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['File_ft->replace_tag_catchall'] ?? ['branches' => [], 'paths' => []];
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
    ? count(array_filter($coveredBranches)) / $totalBranches * 100
    : 0.0;

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
