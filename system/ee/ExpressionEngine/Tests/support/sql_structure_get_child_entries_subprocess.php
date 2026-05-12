<?php

require __DIR__ . '/../bootstrap.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';
require_once PATH_ADDONS . 'structure/Conduit/StaticCache.php';

use ExpressionEngine\Structure\Conduit\StaticCache;

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$target = realpath(PATH_ADDONS . 'structure/sql.structure.php');
$method = new ReflectionMethod('Sql_structure', 'get_child_entries');
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');
$captured = (object) [
    'category_calls' => [],
    'queries' => [],
];

ee()->resetMocks();
StaticCache::clear();
ee()->setMock('db', new class($captured) {
    private $captured;
    private $responses;

    /**
     * Store capture state and queue the reachable query responses.
     *
     * @param object $captured
     * @return void
     */
    public function __construct(object $captured)
    {
        $this->captured = $captured;
        $this->responses = [
            [
                'num_rows' => 2,
                'rows' => [
                    ['entry_id' => 30],
                    ['entry_id' => 31],
                ],
            ],
            [
                'num_rows' => 0,
                'rows' => [],
            ],
            [
                'num_rows' => 1,
                'rows' => [],
            ],
            [
                'num_rows' => 0,
                'rows' => [],
            ],
            [
                'num_rows' => 0,
                'rows' => [],
            ],
        ];
    }

    /**
     * Return the next queued result and capture the generated SQL.
     *
     * @param string $sql
     * @return object
     */
    public function query($sql)
    {
        $this->captured->queries[] = $sql;
        $response = array_shift($this->responses) ?? ['num_rows' => 0, 'rows' => []];

        return new class($response) {
            private $response;

            /**
             * Store the queued response payload.
             *
             * @param array $response
             * @return void
             */
            public function __construct(array $response)
            {
                $this->response = $response;
            }

            /**
             * Return the configured row count.
             *
             * @return int
             */
            public function num_rows(): int
            {
                return $this->response['num_rows'];
            }

            /**
             * Return the configured result rows.
             *
             * @return array
             */
            public function result_array(): array
            {
                return $this->response['rows'];
            }
        };
    }
});

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

$sql = new class($captured) extends Sql_structure {
    private $captured;

    /**
     * Store capture state and initialize the method dependencies.
     *
     * @param object $captured
     * @return void
     */
    public function __construct(object $captured)
    {
        $this->captured = $captured;
        $this->site_id = 1;
        $this->cache = [];
    }

    /**
     * Return the configured category rows for each exercised slug.
     *
     * @param string|int $cat
     * @return array
     */
    public function get_entries_by_category($cat)
    {
        $this->captured->category_calls[] = $cat;

        if ($cat === 'news') {
            return [
                ['entry_id' => 30],
                ['entry_id' => 31],
                ['entry_id' => 45],
            ];
        }

        if ($cat === 'empty-slug') {
            return [];
        }

        return [];
    }
};

StaticCache::set([7, 'news', 'n'], [90, 91]);
$cachedResult = $sql->get_child_entries(7, 'news', 'n');

StaticCache::clear();
$filteredResult = $sql->get_child_entries(5, 'news', 'n');

StaticCache::clear();
$includeHiddenEmptyCategoryResult = $sql->get_child_entries(0, 'empty-slug', 'y');

StaticCache::clear();
$invalidParentResult = $sql->get_child_entries('invalid', '', 'n');
$invalidParentCached = StaticCache::has(['invalid', '', 'n']);
$invalidParentCachedValue = StaticCache::get(['invalid', '', 'n']);

StaticCache::clear();
$falseParentResult = $sql->get_child_entries(false, '', 'n');
$falseParentCached = StaticCache::has([false, '', 'n']);
$falseParentCachedValue = StaticCache::get([false, '', 'n']);

StaticCache::clear();
$positiveEmptyResult = $sql->get_child_entries(12, '', 'y');

StaticCache::clear();
$emptyResultFirst = $sql->get_child_entries(9, '', 'n');
$emptyResultSecond = $sql->get_child_entries(9, '', 'n');
$emptyResultCached = StaticCache::has([9, '', 'n']);
$emptyResultCachedValue = StaticCache::get([9, '', 'n']);

$coverage = [];

if ($xdebugAvailable) {
    $coverage = xdebug_get_code_coverage();
}

$fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
$functionCoverage = $fileCoverage['functions']['Sql_structure->get_child_entries'] ?? ['branches' => [], 'paths' => []];
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
    'cached_result' => $cachedResult,
    'filtered_result' => $filteredResult,
    'include_hidden_empty_category_result' => $includeHiddenEmptyCategoryResult,
    'invalid_parent_result' => $invalidParentResult,
    'invalid_parent_cached' => $invalidParentCached,
    'invalid_parent_cached_value' => $invalidParentCachedValue,
    'false_parent_result' => $falseParentResult,
    'false_parent_cached' => $falseParentCached,
    'false_parent_cached_value' => $falseParentCachedValue,
    'positive_empty_result' => $positiveEmptyResult,
    'empty_result_first' => $emptyResultFirst,
    'empty_result_second' => $emptyResultSecond,
    'empty_result_cached' => $emptyResultCached,
    'empty_result_cached_value' => $emptyResultCachedValue,
    'category_calls' => $captured->category_calls,
    'queries' => $captured->queries,
    'xdebug_available' => $xdebugAvailable,
    'lines' => $coveredLines,
    'branches' => $functionCoverage['branches'] ?? [],
    'paths' => $functionCoverage['paths'] ?? [],
    'line_percentage' => $linePercentage,
    'branch_percentage' => $branchPercentage,
    'covered_branches' => $coveredBranches,
    'uncovered_lines' => array_values(array_keys(array_filter($coveredLines, function ($covered) {
        return ! $covered;
    }))),
    'uncovered_branches' => array_values(array_keys(array_filter($coveredBranches, function ($covered) {
        return ! $covered;
    }))),
], JSON_PRETTY_PRINT));
