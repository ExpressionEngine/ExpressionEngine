<?php

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../ExpressionEngine/Addons/Structure/Mod/tags/StructureFirstChildRedirectTest.php';

$outputFile = $argv[1] ?? null;

if (! $outputFile) {
    fwrite(STDERR, "Missing output file path.\n");
    exit(1);
}

$setUp = new ReflectionMethod(StructureFirstChildRedirectTest::class, 'setUp');
$setUp->setAccessible(true);
$structureProperty = new ReflectionProperty(StructureTestBase::class, 'structure');
$structureProperty->setAccessible(true);
$returned = false;

$test = new StructureFirstChildRedirectTest('first-child-redirect-subprocess');
$setUp->invoke($test);
$structure = $structureProperty->getValue($test);

ee()->setMock('config', new class {
    public function item($key)
    {
        if ($key === 'site_id') {
            return 1;
        }

        if ($key === 'base_url') {
            return 'https://example.com/';
        }

        return null;
    }
});

ee()->setMock('db', new class extends FakeDb {
    public function query($sql)
    {
        return new class {
            public $num_rows = 1;

            public function row($column = null)
            {
                return 101;
            }
        };
    }
});

register_shutdown_function(function () use (&$returned, $outputFile) {
    $target = realpath(__DIR__ . '/../../Addons/structure/mod.structure.php');
    $coverage = xdebug_get_code_coverage();
    $fileCoverage = $coverage[$target] ?? ['lines' => []];

    file_put_contents($outputFile, json_encode([
        'returned' => $returned,
        'lines' => array_intersect_key($fileCoverage['lines'] ?? [], array_flip(range(1044, 1046))),
    ], JSON_PRETTY_PRINT));
});

xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE | XDEBUG_CC_BRANCH_CHECK);
$structure->first_child_redirect();
$returned = true;
