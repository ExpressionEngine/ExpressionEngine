<?php

require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../ExpressionEngine/Addons/file/FT/FileFtReplaceCropTest.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$target = realpath(PATH_ADDONS . 'file/ft.file.php');
$method = new ReflectionMethod('File_ft', 'replace_crop');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

/**
 * Build a coverage spy for replace_crop().
 *
 * @return FileFtReplaceCropSpy
 */
function file_ft_replace_crop_make_fieldtype()
{
    return new FileFtReplaceCropSpy();
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

file_ft_replace_crop_make_fieldtype()->replace_crop(['file_id' => 4], [], '{file:url}');
file_ft_replace_crop_make_fieldtype()->replace_crop(['file_id' => 7], ['wrap' => 'plain'], false);
file_ft_replace_crop_make_fieldtype()->replace_crop([], ['width' => '1200'], '{file:url}');
file_ft_replace_crop_make_fieldtype()->replace_crop(
    ['file_id' => 11, 'url' => '{filedir_3}manual.pdf'],
    ['height' => '600'],
    false
);

$defaultedModelObject = new FileFtReplaceCropModelObjectStub(
    'hero.jpg',
    's3-assets',
    '/srv/uploads/hero.jpg'
);
file_ft_replace_crop_make_fieldtype()->replace_crop(
    ['model_object' => new FileFtReplaceCropModelObjectStub(
        'hero.jpg',
        's3-assets',
        '/srv/uploads/hero.jpg',
        false,
        false
    )],
    ['width' => '900'],
    '{tagdata}'
);
file_ft_replace_crop_make_fieldtype()->replace_crop(
    [
        'file_id' => 22,
        'model_object' => $defaultedModelObject,
        'extra' => 'kept',
    ],
    ['width' => '900', 'quality' => '80'],
    '{tagdata}'
);

$preseededModelObject = new FileFtReplaceCropModelObjectStub(
    'hero.jpg',
    's3-assets',
    '/srv/uploads/hero.jpg',
    true,
    false
);
$filesystem = (object) ['adapter' => 'local-cache'];
file_ft_replace_crop_make_fieldtype()->replace_crop(
    [
        'model_object' => $preseededModelObject,
        'fs_filename' => 'cached-name.jpg',
        'filesystem' => $filesystem,
        'source_image' => '/tmp/cached-name.jpg',
    ],
    ['width' => '320'],
    null
);

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['File_ft->replace_crop'] ?? ['branches' => [], 'paths' => []];
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
