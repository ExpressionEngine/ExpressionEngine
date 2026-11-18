<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../ExpressionEngine/legacy/Libraries/FileFieldFieldTest.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$target = realpath(SYSPATH . 'ee/legacy/libraries/File_field.php');
$method = new ReflectionMethod('File_field', 'parse_field');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

/**
 * Build a parse-field harness with deterministic mocks.
 *
 * @param array<int|string, mixed> $uploadPreferences
 * @return \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseFieldHarness
 */
function build_parse_field_subject(array $uploadPreferences = [])
{
    ee()->resetMocks();
    ee()->config->resetConfig();
    ee()->setMock('Format', new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldFormatFactoryMock());
    ee()->setMock('Model', new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldModelServiceMock());

    $subject = new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseFieldHarness();
    $subject->_upload_prefs = $uploadPreferences;

    return $subject;
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

$subject = build_parse_field_subject();
$subject->parse_field('');

$subject = build_parse_field_subject();
$subject->parse_field('https://legacy.example.com/images/category/photo.jpg');

$subject = build_parse_field_subject([
    9 => new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseUploadDestinationMock(
        9,
        'Assets',
        new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseFilesystemMock()
    ),
]);
$subject->getFileReturn = [
    'file_name' => 'missing.jpg',
    'upload_location_id' => 99,
    'directory_id' => 99,
    'file_hw_original' => '10 10',
    'file_size' => 42,
    'mime_type' => 'image/jpeg',
];
$subject->parse_field('22');

$filesystem = new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseFilesystemMock('https://cdn.example.com/uploads/');
$subject = build_parse_field_subject([
    9 => new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseUploadDestinationMock(9, 'Assets', $filesystem),
]);
$subject->getFileReturn = [
    'file_name' => 'brochure image.png',
    'upload_location_id' => 9,
    'directory_id' => 22,
    'file_hw_original' => '480 640',
    'file_size' => 1024,
    'mime_type' => 'image/png',
];
$subject->parse_field('{file:42:url}');

$filesystem = new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseFilesystemMock('https://cdn.example.com/uploads/');
$subject = build_parse_field_subject([
    9 => new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseUploadDestinationMock(9, 'Assets', $filesystem),
]);
$subject->getFileReturn = [
    'file_name' => 'manual.pdf',
    'upload_location_id' => 9,
    'directory_id' => 9,
    'file_hw_original' => '',
    'file_size' => 88,
    'mime_type' => 'application/pdf',
];
$subject->parse_field('{filedir_9}manual.pdf');

$subject = build_parse_field_subject([
    9 => new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseUploadDestinationMock(
        9,
        'Assets',
        new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseFilesystemMock()
    ),
]);
$subject->parse_field([
    'file_name' => 'array-input.jpg',
    'upload_location_id' => 9,
    'directory_id' => 9,
    'file_hw_original' => '',
    'file_size' => 5,
    'mime_type' => 'image/jpeg',
]);

$filesystem = new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseFilesystemMock('https://cdn.example.com/uploads/');
$filesystem->throwOnPath['error%20doc.pdf'] = true;
$subject = build_parse_field_subject([
    9 => new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseUploadDestinationMock(9, 'Assets', $filesystem),
]);
$subject->getFileReturn = [
    'file_name' => 'error doc.pdf',
    'upload_location_id' => 9,
    'directory_id' => 9,
    'file_hw_original' => '',
    'file_size' => 3,
    'mime_type' => 'application/pdf',
];
$subject->parse_field('77');

$filesystem = new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseFilesystemMock('https://cdn.example.com/uploads/');
$filesystem->sizesByPath['_thumb/sample photo.jpg'] = 256;
$modelObject = new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseModelObjectMock(
    'https://files.example.com/base/',
    'https://files.example.com/base/sample%20photo.jpg',
    '/var/www/files/sample photo.jpg',
    ['thumb' => '/var/www/files/_thumb/sample photo.jpg']
);
$subject = build_parse_field_subject([
    9 => new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseUploadDestinationMock(9, 'Assets', $filesystem),
]);
$subject->_manipulations[9] = [
    new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseManipulationMock('thumb', 150, 120, ['width' => 90, 'height' => 72]),
];
$subject->getFileReturn = [
    'file_name' => 'sample photo.jpg',
    'upload_location_id' => 9,
    'directory_id' => 44,
    'file_hw_original' => '100 200',
    'file_size' => 64,
    'mime_type' => 'image/jpeg',
    'model_object' => $modelObject,
];
$subject->parse_field('55');

$filesystem = new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseFilesystemMock('https://cdn.example.com/uploads/');
$filesystem->existingPaths['/var/www/files/_thumb/has-path.jpg'] = true;
$filesystem->sizesByPath['_thumb/has-path.jpg'] = 321;
$modelObject = new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseModelObjectMock(
    'https://files.example.com/base/',
    'https://files.example.com/base/has-path.jpg',
    '/var/www/files/has-path.jpg',
    ['thumb' => '/var/www/files/_thumb/has-path.jpg']
);
$subject = build_parse_field_subject([
    9 => new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseUploadDestinationMock(9, 'Assets', $filesystem),
]);
$subject->_manipulations[9] = [
    new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseManipulationMock('thumb', 151, 121),
];
$subject->getFileReturn = [
    'file_name' => 'has-path.jpg',
    'upload_location_id' => 9,
    'directory_id' => 44,
    'file_hw_original' => '100 200',
    'file_size' => 12,
    'mime_type' => 'image/jpeg',
    'model_object' => $modelObject,
];
$subject->parse_field('56');

$filesystem = new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseFilesystemMock('https://cdn.example.com/uploads/');
$filesystem->sizesByPath['vector.svg'] = 19;
$subject = build_parse_field_subject([
    9 => new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseUploadDestinationMock(9, 'Assets', $filesystem),
]);
$subject->_manipulations[9] = [
    new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldParseManipulationMock('thumb', 10, 11),
];
$subject->getFileReturn = [
    'file_name' => 'vector.svg',
    'upload_location_id' => 9,
    'directory_id' => 9,
    'file_hw_original' => '',
    'file_size' => 10,
    'mime_type' => 'image/svg+xml',
    'model_object' => null,
];
$subject->parse_field('19');

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['File_field->parse_field'] ?? ['branches' => [], 'paths' => []];
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
