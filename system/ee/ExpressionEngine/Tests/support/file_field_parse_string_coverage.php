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
$method = new ReflectionMethod('File_field', 'parse_string');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

/**
 * Build a parse_string harness with deterministic model/load mocks.
 *
 * @return array<string, mixed>
 */
function build_parse_string_subject(): array
{
    ee()->resetMocks();
    ee()->config->resetConfig();

    $load = new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldLoadMock();
    $modelService = new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldModelServiceMock();

    ee()->setMock('load', $load);
    ee()->setMock('Model', $modelService);

    $subject = new \ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\FileFieldFieldHarness();

    return [
        'load' => $load,
        'model' => $modelService,
        'subject' => $subject,
    ];
}

/**
 * Create a minimal model-like file object for parse_string replacement tests.
 *
 * @param int $fileId
 * @param array<string, mixed> $fieldValues
 * @param array<int, string> $fields
 * @param string $absoluteUrl
 * @return object
 */
function make_parse_string_file_model(int $fileId, array $fieldValues, array $fields, string $absoluteUrl): object
{
    return new class($fileId, $fieldValues, $fields, $absoluteUrl) {
        /** @var int */
        public $file_id;

        /** @var array<string, mixed> */
        private $fieldValues;

        /** @var array<int, string> */
        private $fields;

        /** @var string */
        private $absoluteUrl;

        /**
         * @param int $fileId
         * @param array<string, mixed> $fieldValues
         * @param array<int, string> $fields
         * @param string $absoluteUrl
         */
        public function __construct(int $fileId, array $fieldValues, array $fields, string $absoluteUrl)
        {
            $this->file_id = $fileId;
            $this->fieldValues = $fieldValues;
            $this->fields = $fields;
            $this->absoluteUrl = $absoluteUrl;
        }

        /**
         * @return array<int, string>
         */
        public function getFields(): array
        {
            return $this->fields;
        }

        /**
         * @return string
         */
        public function getAbsoluteURL(): string
        {
            return $this->absoluteUrl;
        }

        /**
         * @param string $name
         * @return mixed
         */
        public function __get(string $name)
        {
            return $this->fieldValues[$name] ?? null;
        }
    };
}

/**
 * Install mock upload-directory paths used by parse_string filedir substitutions.
 *
 * @param array<string|int, string> $paths
 * @return void
 */
function set_parse_string_upload_paths(array $paths): void
{
    ee()->setMock('file_upload_preferences_model', new class($paths) {
        /** @var array<string|int, string> */
        private $paths;

        /**
         * @param array<string|int, string> $paths
         */
        public function __construct(array $paths)
        {
            $this->paths = $paths;
        }

        /**
         * @return array<string|int, string>
         */
        public function get_paths()
        {
            return $this->paths;
        }
    });
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

$env = build_parse_string_subject();
$env['subject']->parse_string('');

$env = build_parse_string_subject();
$env['subject']->parse_string('plain text without tokens');

$env = build_parse_string_subject();
$env['subject']->parse_string('bad={file:abc:url} and file: marker only');

$env = build_parse_string_subject();
$env['subject']->parse_string('T={file:99:title} U={file:99:url}');

$env = build_parse_string_subject();
set_parse_string_upload_paths([
    7 => 'https://assets.example.com/uploads/',
]);
$env['model']->fileResultsAllByFilterKey[$env['model']->buildFileFilterKey([])] = [
    make_parse_string_file_model(
        12,
        [
            'width' => 1200,
            'height' => 800,
            'title' => 'Hero Banner',
            'credit' => 'Staff',
        ],
        ['title', 'credit'],
        'https://files.example.com/hero-banner.jpg'
    ),
    make_parse_string_file_model(
        45,
        [
            'width' => 640,
            'height' => 480,
            'title' => 'Detail Shot',
            'credit' => 'Designer',
        ],
        ['title', 'credit'],
        'https://files.example.com/detail-shot.jpg'
    ),
];
$env['subject']->parse_string(
    'T={file:12:title} C={file:45:credit} W={file:12:width} H={file:45:height} U={file:12:url} P={filedir_7}brochure.pdf M={filedir_99}skip.pdf'
);

$env = build_parse_string_subject();
set_parse_string_upload_paths([
    3 => 'https://cdn.example.com/site-images/',
]);
$env['subject']->parse_string('Before &#123;filedir_3&#125;photo.jpg After', true);

$env = build_parse_string_subject();
set_parse_string_upload_paths([
    3 => 'https://cdn.example.com/site-images/',
]);
$env['subject']->parse_string('Before &#123;filedir_3&#125;photo.jpg After', false);

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['File_field->parse_string'] ?? ['branches' => [], 'paths' => []];
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
