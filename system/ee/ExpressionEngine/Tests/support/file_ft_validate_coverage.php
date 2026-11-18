<?php

require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../ExpressionEngine/Addons/file/FT/FileFtTestBase.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$target = realpath(PATH_ADDONS . 'file/ft.file.php');
$method = new ReflectionMethod('File_ft', 'validate');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

/**
 * Reset EE mocks and seed the shared File_ft validation doubles.
 *
 * @return array<string, mixed>
 */
function file_ft_validate_bootstrap(): array
{
    ee()->resetMocks();

    $load = new FileFtLoadRecorder();
    $session = new FileFtSessionStub();
    $fileField = new FileFtFileFieldStub();
    $model = new FileFtModelServiceStub();

    ee()->setMock('load', $load);
    ee()->setMock('session', $session);
    ee()->setMock('file_field', $fileField);
    ee()->setMock('Model', $model);

    return [
        'load' => $load,
        'session' => $session,
        'file_field' => $fileField,
        'model' => $model,
    ];
}

/**
 * Build a File_ft instance with the requested field context.
 *
 * @param array<string, mixed> $settings
 * @param int $contentId
 * @param string $fieldName
 * @return File_ft
 */
function file_ft_validate_make_fieldtype(array $settings = [], int $contentId = 0, string $fieldName = 'file_field'): File_ft
{
    $fieldtype = new File_ft();
    $fieldtype->settings = array_merge([
        'field_required' => 'n',
    ], $settings);
    $fieldtype->content_id = $contentId;
    $fieldtype->field_name = $fieldName;

    return $fieldtype;
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

file_ft_validate_make_fieldtype([
    'field_required' => true,
])->validate('');

file_ft_validate_make_fieldtype([
    'field_required' => 'y',
])->validate(null);

file_ft_validate_make_fieldtype([
    'field_required' => false,
])->validate('');

file_ft_validate_make_fieldtype([
    'field_required' => 'n',
])->validate(null);

$env = file_ft_validate_bootstrap();
file_ft_validate_make_fieldtype([
    'field_required' => 'y',
])->validate('{filedir_1}missing.png');

$env = file_ft_validate_bootstrap();
$env['file_field']->fileModel = new FileFtFileModelStub(true);
file_ft_validate_make_fieldtype([
    'field_required' => 'y',
])->validate('{filedir_1}new-no-member.png');

$env = file_ft_validate_bootstrap();
$env['session']->member = (object) ['member_id' => 42];
$env['file_field']->fileModel = new FileFtFileModelStub(true);
file_ft_validate_make_fieldtype([
    'field_required' => 'y',
])->validate('{filedir_1}new-member-ok.png');

$env = file_ft_validate_bootstrap();
$env['file_field']->fileModel = new FileFtFileModelStub(false);
$env['model']->setFirstResult('ChannelEntry', 19, (object) [
    'feature_image' => '{filedir_1}same.png',
]);
file_ft_validate_make_fieldtype([
    'field_required' => 'y',
], 19, 'feature_image')->validate('{filedir_1}same.png');

$env = file_ft_validate_bootstrap();
$env['session']->member = (object) ['member_id' => 77];
$env['file_field']->fileModel = new FileFtFileModelStub(false);
$env['model']->setFirstResult('ChannelEntry', 19, (object) [
    'feature_image' => '{filedir_1}previous.png',
]);
file_ft_validate_make_fieldtype([
    'field_required' => 'y',
], 19, 'feature_image')->validate('{filedir_2}updated.png');

$env = file_ft_validate_bootstrap();
$env['file_field']->fileModel = new FileFtFileModelStub(false);
ee()->setMock('grid_model', new FileFtGridModelStub([
    19 => [
        7 => '{filedir_1}same-grid.png',
    ],
]));
file_ft_validate_make_fieldtype([
    'field_required' => 'y',
    'grid_row_id' => 7,
    'grid_field_id' => 33,
    'grid_content_type' => 'channel',
], 19)->validate('{filedir_1}same-grid.png');

$env = file_ft_validate_bootstrap();
$env['file_field']->fileModel = new FileFtFileModelStub(true);
ee()->setMock('grid_model', new FileFtGridModelStub([
    19 => [],
]));
file_ft_validate_make_fieldtype([
    'field_required' => 'y',
    'grid_row_name' => 'new_row_1',
    'grid_field_id' => 33,
    'grid_content_type' => 'channel',
    'fluid_field_data_id' => 12,
], 19)->validate('{filedir_1}grid-new.png');

$env = file_ft_validate_bootstrap();
$env['session']->member = (object) ['member_id' => 55];
$env['file_field']->fileModel = new FileFtFileModelStub(true);
$env['model']->setFirstResult('ChannelEntry', 88, null);
file_ft_validate_make_fieldtype([
    'field_required' => 'y',
], 88, 'feature_image')->validate('{filedir_1}missing-entry.png');

$env = file_ft_validate_bootstrap();
$env['file_field']->fileModel = new FileFtFileModelStub(true);
ee()->setMock('channel_form', (object) []);
ee()->setMock('channel_form_lib', new FileFtChannelFormLibStub(0));
file_ft_validate_make_fieldtype([
    'field_required' => 'y',
])->validate('{filedir_1}logged-out-missing.png');

$env = file_ft_validate_bootstrap();
$env['file_field']->fileModel = new FileFtFileModelStub(true);
$env['model']->setFirstResult('Member', 88, (object) ['member_id' => 88]);
ee()->setMock('channel_form', (object) []);
ee()->setMock('channel_form_lib', new FileFtChannelFormLibStub(88));
file_ft_validate_make_fieldtype([
    'field_required' => 'y',
])->validate('{filedir_1}logged-out-ok.png');

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['File_ft->validate'] ?? ['branches' => [], 'paths' => []];
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
