<?php

require __DIR__ . '/../bootstrap.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$captured = (object) [
    'queries' => [],
    'result_array_calls' => 0,
];
$returned = false;
$target = realpath(PATH_ADDONS . 'structure/sql.structure.php');
$linesToTrack = array_flip([83, 85, 87, 90, 91, 92, 94]);
$xdebugAvailable = function_exists('xdebug_start_code_coverage') && function_exists('xdebug_get_code_coverage');

ee()->setMock('db', new class($captured) {
    private $captured;

    public function __construct(object $captured)
    {
        $this->captured = $captured;
    }

    public function query($sql)
    {
        $this->captured->queries[] = $sql;

        return new class($this->captured) {
            private $captured;

            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            public function result_array()
            {
                $this->captured->result_array_calls++;

                return [
                    ['cat_id' => 10, 'cat_name' => 'Alpha'],
                    ['cat_id' => 20, 'cat_name' => 'Beta'],
                ];
            }
        };
    }
});

register_shutdown_function(function () use (&$returned, $outputFile, $captured, $target, $linesToTrack, $xdebugAvailable) {
    $coverage = [];

    if ($xdebugAvailable) {
        $coverage = xdebug_get_code_coverage();
    }

    $fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
    $functionCoverage = $fileCoverage['functions']['Sql_structure->get_categories'] ?? ['branches' => [], 'paths' => []];
    $bufferedOutput = '';

    if (ob_get_level() > 0) {
        $bufferedOutput = ob_get_contents();
        ob_end_clean();
    }

    file_put_contents($outputFile, json_encode([
        'returned' => $returned,
        'real_module_path' => $target,
        'queries' => $captured->queries,
        'result_array_calls' => $captured->result_array_calls,
        'output' => $bufferedOutput,
        'xdebug_available' => $xdebugAvailable,
        'lines' => array_intersect_key($fileCoverage['lines'] ?? [], $linesToTrack),
        'branches' => $functionCoverage['branches'],
        'paths' => $functionCoverage['paths'],
    ], JSON_PRETTY_PRINT));
});

ob_start();

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

$sql = (new ReflectionClass('Sql_structure'))->newInstanceWithoutConstructor();
$sql->site_id = 1;
$sql->cache = [];
$sql->get_categories(7);
$returned = true;
