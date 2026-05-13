<?php

require __DIR__ . '/../bootstrap.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$target = realpath(PATH_ADDONS . 'structure/sql.structure.php');
$method = new ReflectionMethod('Sql_structure', 'theme_url');
$methodStartLine = $method->getStartLine();
$methodEndLine = $method->getEndLine();
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');
$urlThemesDefinedBefore = defined('URL_THEMES');

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

$makeConfig = static function (string $themeFolderUrl): object {
    return new class($themeFolderUrl) {
        public $slashItemCalls = 0;
        private $themeFolderUrl;

        public function __construct(string $themeFolderUrl)
        {
            $this->themeFolderUrl = $themeFolderUrl;
        }

        public function slash_item($key): string
        {
            $this->slashItemCalls++;

            if ($key !== 'theme_folder_url') {
                return '';
            }

            return rtrim($this->themeFolderUrl, '/') . '/';
        }
    };
};

$fallbackConfig = $makeConfig('https://cdn.example.com/themes');
ee()->setMock('config', $fallbackConfig);

$fallbackSql = (new ReflectionClass('Sql_structure'))->newInstanceWithoutConstructor();
$fallbackFirst = $fallbackSql->theme_url();
$fallbackSecond = $fallbackSql->theme_url();

$constantConfig = $makeConfig('https://ignored.example.com/themes');

if (! defined('URL_THEMES')) {
    define('URL_THEMES', 'https://themes.example/');
}

ee()->setMock('config', $constantConfig);

$constantSql = (new ReflectionClass('Sql_structure'))->newInstanceWithoutConstructor();
$constantResult = $constantSql->theme_url();

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['Sql_structure->theme_url'] ?? ['branches' => [], 'paths' => []];
$coveredLines = [];

foreach (($fileCoverage['lines'] ?? []) as $line => $hitCount) {
    if ($line < $methodStartLine || $line > $methodEndLine) {
        continue;
    }

    if ($hitCount === -2) {
        continue;
    }

    $coveredLines[$line] = ($hitCount > 0);
}

ksort($coveredLines);

$coveredPaths = [];

foreach (($functionCoverage['paths'] ?? []) as $index => $path) {
    $coveredPaths[$index] = (($path['hit'] ?? 0) > 0);
}

$linePercentage = count($coveredLines) > 0
    ? count(array_filter($coveredLines)) / count($coveredLines) * 100
    : 0.0;
$branchPercentage = count($coveredPaths) > 0
    ? count(array_filter($coveredPaths)) / count($coveredPaths) * 100
    : 0.0;

file_put_contents($outputFile, json_encode([
    'real_module_path' => $target,
    'url_themes_defined_before' => $urlThemesDefinedBefore,
    'fallback_first' => $fallbackFirst,
    'fallback_second' => $fallbackSecond,
    'fallback_config_calls' => $fallbackConfig->slashItemCalls,
    'constant_result' => $constantResult,
    'constant_config_calls' => $constantConfig->slashItemCalls,
    'xdebug_available' => $xdebugAvailable,
    'lines' => array_intersect_key($fileCoverage['lines'] ?? [], $coveredLines),
    'method_start_line' => $methodStartLine,
    'method_end_line' => $methodEndLine,
    'branches' => $functionCoverage['branches'] ?? [],
    'paths' => $functionCoverage['paths'] ?? [],
    'line_percentage' => $linePercentage,
    'branch_percentage' => $branchPercentage,
    'covered_lines' => $coveredLines,
    'covered_paths' => $coveredPaths,
    'uncovered_lines' => array_values(array_keys(array_filter($coveredLines, static function ($covered) {
        return ! $covered;
    }))),
    'uncovered_paths' => array_values(array_keys(array_filter($coveredPaths, static function ($covered) {
        return ! $covered;
    }))),
], JSON_PRETTY_PRINT));
