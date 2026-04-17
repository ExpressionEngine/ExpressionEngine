<?php

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../ExpressionEngine/Addons/Structure/Mod/StructureDebugTest.php';

$mode = 'die';
$outputFile = null;

foreach (array_slice($argv, 1) as $arg) {
    if (strpos($arg, '--mode=') === 0) {
        $mode = substr($arg, 7);
        continue;
    }

    if ($outputFile === null) {
        $outputFile = $arg;
    }
}

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$setUp = new ReflectionMethod(StructureDebugTest::class, 'setUp');
$setUp->setAccessible(true);
$structureProperty = new ReflectionProperty(StructureTestBase::class, 'structure');
$structureProperty->setAccessible(true);
$returned = false;
$output = '';
$target = realpath(PATH_ADDONS . 'structure/mod.structure.php');
$linesToTrack = array_flip(range(2102, 2109));

if ($mode === 'aggregate') {
    $returnFile = sys_get_temp_dir() . '/structure-debug-return-' . uniqid('', true) . '.json';
    $dieFile = sys_get_temp_dir() . '/structure-debug-die-' . uniqid('', true) . '.json';
    $commands = [
        'return' => escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --mode=return ' . escapeshellarg($returnFile) . ' 2>&1',
        'die' => escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --mode=die ' . escapeshellarg($dieFile) . ' 2>&1',
    ];
    $commandResults = [];

    foreach ($commands as $commandMode => $command) {
        $commandOutput = [];
        $exitCode = 0;
        exec($command, $commandOutput, $exitCode);
        $commandResults[$commandMode] = [
            'command' => $command,
            'output' => $commandOutput,
            'exit_code' => $exitCode,
        ];
    }

    $returnResult = json_decode(file_get_contents($returnFile), true);
    $dieResult = json_decode(file_get_contents($dieFile), true);

    @unlink($returnFile);
    @unlink($dieFile);

    $executableLines = [2102, 2103, 2104, 2106, 2107, 2109];
    $coveredLines = [];

    foreach ($executableLines as $line) {
        $coveredLines[$line] = (($returnResult['lines'][(string) $line] ?? 0) > 0)
            || (($dieResult['lines'][(string) $line] ?? 0) > 0);
    }

    $coveredPaths = [];

    foreach ([0, 1] as $index) {
        $coveredPaths[$index] = (($returnResult['paths'][$index]['hit'] ?? 0) > 0)
            || (($dieResult['paths'][$index]['hit'] ?? 0) > 0);
    }

    $linePercentage = count(array_filter($coveredLines)) / count($coveredLines) * 100;
    $branchPercentage = count(array_filter($coveredPaths)) / count($coveredPaths) * 100;

    file_put_contents($outputFile, json_encode([
        'real_module_path' => $target,
        'line_percentage' => $linePercentage,
        'branch_percentage' => $branchPercentage,
        'covered_lines' => $coveredLines,
        'covered_paths' => $coveredPaths,
        'uncovered_lines' => array_values(array_keys(array_filter($coveredLines, function ($covered) {
            return ! $covered;
        }))),
        'uncovered_paths' => array_values(array_keys(array_filter($coveredPaths, function ($covered) {
            return ! $covered;
        }))),
        'return_result' => $returnResult,
        'die_result' => $dieResult,
        'commands' => $commandResults,
    ], JSON_PRETTY_PRINT));

    exit(0);
}

register_shutdown_function(function () use (&$returned, &$output, $outputFile, $target, $linesToTrack) {
    $coverage = xdebug_get_code_coverage();
    $fileCoverage = $coverage[$target] ?? ['lines' => [], 'functions' => []];
    $functionCoverage = $fileCoverage['functions']['Structure->debug'] ?? ['branches' => [], 'paths' => []];
    $bufferedOutput = $output;

    if (ob_get_level() > 0) {
        $bufferedOutput = ob_get_contents();
        ob_end_clean();
    }

    file_put_contents($outputFile, json_encode([
        'returned' => $returned,
        'output' => $bufferedOutput,
        'lines' => array_intersect_key($fileCoverage['lines'] ?? [], $linesToTrack),
        'branches' => $functionCoverage['branches'],
        'paths' => $functionCoverage['paths'],
    ], JSON_PRETTY_PRINT));
});

$test = new StructureDebugTest('structure-debug-subprocess');
$setUp->invoke($test);
$structure = $structureProperty->getValue($test);

ob_start();
xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE | XDEBUG_CC_BRANCH_CHECK);

if ($mode === 'die') {
    $structure->debug(['shutdown' => 'yes'], true);
    $returned = true;
    $output = ob_get_clean();
    exit(0);
}

if ($mode === 'return') {
    $structure->debug(['shutdown' => 'no'], false);
    $returned = true;
    $output = ob_get_clean();
    exit(0);
}

ob_end_clean();
fwrite(STDERR, "Unsupported mode: {$mode}\n");
exit(1);
