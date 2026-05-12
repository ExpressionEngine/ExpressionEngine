<?php

require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../ExpressionEngine/Addons/file/FT/FileFtTestBase.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$target = realpath(PATH_ADDONS . 'file/ft.file.php');
$method = new ReflectionMethod('File_ft', 'process_image');
TestReflectionHelper::makeAccessible($method);
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

/**
 * Reset the ee() singleton mocks for one isolated process_image() scenario.
 *
 * @param FileFtImageLibStub|null $imageLib
 * @param FileFtTemplateStub|null $template
 * @param FileFtConfigStub|null $config
 * @param FileFtPermissionStub|null $permission
 * @return array<string, mixed>
 */
function file_ft_process_image_bootstrap(
    FileFtImageLibStub $imageLib = null,
    FileFtTemplateStub $template = null,
    FileFtConfigStub $config = null,
    FileFtPermissionStub $permission = null
) {
    ee()->resetMocks();

    $load = new FileFtLoadRecorder();
    $template = $template ?: new FileFtTemplateStub();
    $config = $config ?: new FileFtConfigStub();
    $permission = $permission ?: new FileFtPermissionStub();
    $imageLib = $imageLib ?: new FileFtImageLibStub();

    ee()->setMock('load', $load);
    ee()->setMock('TMPL', $template);
    ee()->setMock('config', $config);
    ee()->setMock('Permission', $permission);
    ee()->setMock('image_lib', $imageLib);

    return [
        'load' => $load,
        'template' => $template,
        'config' => $config,
        'permission' => $permission,
        'image_lib' => $imageLib,
    ];
}

/**
 * Build a File_ft instance for one coverage scenario.
 *
 * @return File_ft
 */
function file_ft_process_image_make_fieldtype()
{
    return new File_ft();
}

/**
 * Build the editable-image payload passed through public wrapper methods.
 *
 * @param FileFtProcessImageFilesystemStub $filesystem
 * @param array<string, mixed> $modelAttributes
 * @param array<string, mixed> $dataOverrides
 * @return array<string, mixed>
 */
function file_ft_process_image_make_data(
    FileFtProcessImageFilesystemStub $filesystem,
    array $modelAttributes = [],
    array $dataOverrides = []
) {
    $modelObject = new FileFtProcessImageModelObjectStub(array_merge([
        'filesystem' => $filesystem,
    ], $modelAttributes));

    return array_merge([
        'model_object' => $modelObject,
        'fs_filename' => $modelObject->file_name,
        'filesystem' => $filesystem,
        'source_image' => $modelObject->getAbsolutePath(),
    ], $dataOverrides);
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

$environment = file_ft_process_image_bootstrap();
$fieldtype = file_ft_process_image_make_fieldtype();
$method->invoke($fieldtype, 'sharpen', [], [], false, false);

$environment = file_ft_process_image_bootstrap();
file_ft_process_image_make_fieldtype()->replace_resize(
    file_ft_process_image_make_data(
        new FileFtProcessImageFilesystemStub(),
        ['isImage' => false, 'isEditableImage' => false]
    ),
    ['width' => '900'],
    '{tagdata}'
);

$environment = file_ft_process_image_bootstrap();
file_ft_process_image_make_fieldtype()->replace_resize(
    file_ft_process_image_make_data(
        new FileFtProcessImageFilesystemStub(),
        ['isEditableImage' => false]
    ),
    ['width' => '320'],
    null
);

$environment = file_ft_process_image_bootstrap();
file_ft_process_image_make_fieldtype()->replace_resize(
    file_ft_process_image_make_data(
        new FileFtProcessImageFilesystemStub(),
        ['isEditableImage' => false]
    ),
    ['width' => '320'],
    '{tagdata}'
);

$environment = file_ft_process_image_bootstrap();
$nonWritableFilesystem = new FileFtProcessImageFilesystemStub();
$nonWritableFilesystem->directories['/srv/uploads/gallery/_resize' . DIRECTORY_SEPARATOR] = true;
$nonWritableFilesystem->writableDirectories['/srv/uploads/gallery/_resize' . DIRECTORY_SEPARATOR] = false;
file_ft_process_image_make_fieldtype()->replace_resize(
    file_ft_process_image_make_data($nonWritableFilesystem),
    ['width' => '320'],
    false
);

$environment = file_ft_process_image_bootstrap();
$missingSourceFilesystem = new FileFtProcessImageFilesystemStub();
$missingSourceFilesystem->copyToTempFileExceptions['/srv/uploads/gallery/hero.jpg'] = 'missing source image';
file_ft_process_image_make_fieldtype()->replace_resize(
    file_ft_process_image_make_data($missingSourceFilesystem),
    ['width' => '320'],
    false
);

$debugConfig = new FileFtConfigStub();
$debugConfig->items = array_merge($debugConfig->items, [
    'image_resize_protocol' => 'imagick',
    'image_library_path' => '/opt/homebrew/lib',
    'image_manipulation_quality' => 92,
    'debug' => 2,
]);
$debugImageLib = new FileFtImageLibStub();
$debugImageLib->actionResults['resize'] = false;
$debugImageLib->displayErrorsReturn = 'resize failed';
$environment = file_ft_process_image_bootstrap($debugImageLib, null, $debugConfig);
file_ft_process_image_make_fieldtype()->replace_resize(
    file_ft_process_image_make_data(new FileFtProcessImageFilesystemStub()),
    ['width' => '320', 'maintain_ratio' => 'n'],
    false
);

$superAdminConfig = new FileFtConfigStub();
$superAdminConfig->items = array_merge($superAdminConfig->items, [
    'debug' => 1,
]);
$superAdminPermission = new FileFtPermissionStub();
$superAdminPermission->isSuperAdmin = true;
$superAdminImageLib = new FileFtImageLibStub();
$superAdminImageLib->actionResults['rotate'] = false;
$environment = file_ft_process_image_bootstrap($superAdminImageLib, null, $superAdminConfig, $superAdminPermission);
file_ft_process_image_make_fieldtype()->replace_rotate(
    file_ft_process_image_make_data(new FileFtProcessImageFilesystemStub()),
    ['angle' => '90'],
    false
);

$noResultsConfig = new FileFtConfigStub();
$noResultsConfig->items = array_merge($noResultsConfig->items, [
    'image_manipulation_quality' => 120,
    'debug' => 1,
]);
$noResultsImageLib = new FileFtImageLibStub();
$noResultsImageLib->actionResults['resize'] = false;
$environment = file_ft_process_image_bootstrap($noResultsImageLib, null, $noResultsConfig, new FileFtPermissionStub());
file_ft_process_image_make_fieldtype()->replace_resize(
    file_ft_process_image_make_data(new FileFtProcessImageFilesystemStub()),
    ['maintain_ratio' => 'y'],
    false
);

$cropEnvironment = file_ft_process_image_bootstrap();
$cropEnvironment['template']->parseVariablesReturn = 'cropped-template';
file_ft_process_image_make_fieldtype()->replace_crop(
    file_ft_process_image_make_data(new FileFtProcessImageFilesystemStub()),
    [
        'position' => 'center',
        'width' => '50',
        'height' => '20',
        'x' => '5',
        'y' => '7',
    ],
    '{file}{url}:{width}x{height}{/file}'
);

$cachedResizeEnvironment = file_ft_process_image_bootstrap();
$cachedResizeFilesystem = new FileFtProcessImageFilesystemStub();
$cachedResizeParams = ['height' => '240'];
$cachedResizeDestination = '/srv/uploads/gallery/_resize/hero_resize_' . md5(serialize($cachedResizeParams)) . '.jpg';
$cachedResizeFilesystem->directories['/srv/uploads/gallery/_resize' . DIRECTORY_SEPARATOR] = true;
$cachedResizeFilesystem->existingPaths[$cachedResizeDestination] = true;
file_ft_process_image_make_fieldtype()->replace_resize(
    file_ft_process_image_make_data($cachedResizeFilesystem),
    $cachedResizeParams,
    null
);

$heightOnlyResizeEnvironment = file_ft_process_image_bootstrap();
file_ft_process_image_make_fieldtype()->replace_resize(
    file_ft_process_image_make_data(new FileFtProcessImageFilesystemStub()),
    ['height' => '240'],
    false
);

$bothDimensionsResizeEnvironment = file_ft_process_image_bootstrap();
file_ft_process_image_make_fieldtype()->replace_resize(
    file_ft_process_image_make_data(new FileFtProcessImageFilesystemStub()),
    [
        'width' => '320',
        'height' => '180',
    ],
    false
);

$cropWidthOnlyEnvironment = file_ft_process_image_bootstrap();
file_ft_process_image_make_fieldtype()->replace_crop(
    file_ft_process_image_make_data(new FileFtProcessImageFilesystemStub()),
    [
        'position' => 'center',
        'width' => '50',
        'x' => '5',
        'y' => '7',
    ],
    false
);

$cropHeightOnlyEnvironment = file_ft_process_image_bootstrap();
file_ft_process_image_make_fieldtype()->replace_crop(
    file_ft_process_image_make_data(new FileFtProcessImageFilesystemStub()),
    [
        'position' => 'center',
        'height' => '20',
        'x' => '5',
        'y' => '7',
    ],
    false
);

$webpEnvironment = file_ft_process_image_bootstrap();
file_ft_process_image_make_fieldtype()->replace_webp(
    file_ft_process_image_make_data(new FileFtProcessImageFilesystemStub()),
    ['quality' => '60'],
    false
);

$avifEnvironment = file_ft_process_image_bootstrap();
file_ft_process_image_make_fieldtype()->replace_avif(
    file_ft_process_image_make_data(new FileFtProcessImageFilesystemStub()),
    [
        'master_dim' => 'height',
        'quality' => '55',
    ],
    false
);

$wrapEnvironment = file_ft_process_image_bootstrap();
file_ft_process_image_make_fieldtype()->replace_resize(
    file_ft_process_image_make_data(
        new FileFtProcessImageFilesystemStub(),
        [],
        [
            'filename' => 'Hero Banner',
            'image_pre_format' => '<figure>',
            'image_post_format' => '</figure>',
            'image_properties' => 'class="hero"',
        ]
    ),
    [
        'width' => '320',
        'wrap' => 'image',
    ],
    false
);

$resizeCropEnvironment = file_ft_process_image_bootstrap();
file_ft_process_image_make_fieldtype()->replace_resize_crop(
    file_ft_process_image_make_data(new FileFtProcessImageFilesystemStub()),
    [
        'resize:width' => '320',
        'crop:width' => '150',
        'crop:height' => '60',
    ],
    false
);

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['File_ft->process_image'] ?? ['branches' => [], 'paths' => []];
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
