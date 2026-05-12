<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '1');

$projectBase = realpath(__DIR__ . '/../../../../../../../') . '/';

define('SYSPATH', $projectBase);

require_once __DIR__ . '/../../../../eeObjectMock.php';

$repoAddonsPath = realpath(__DIR__ . '/../../../../../Addons');
$isolatedAddonsPath = sys_get_temp_dir() . '/structure-nav-basic-' . uniqid('', true);

mkdir($isolatedAddonsPath . '/structure/libraries/nestedset', 0777, true);
mkdir($isolatedAddonsPath . '/channel', 0777, true);

$linkedFiles = [
    'structure/mod.structure.php',
    'structure/sql.structure.php',
    'structure/addon.setup.php',
    'structure/helper.php',
    'structure/libraries/nestedset/structure_nestedset.php',
    'structure/libraries/nestedset/structure_nestedset_adapter_ee.php',
    'channel/mod.channel.php',
];

foreach ($linkedFiles as $relativePath) {
    symlink($repoAddonsPath . '/' . $relativePath, $isolatedAddonsPath . '/' . $relativePath);
}

file_put_contents(
    $isolatedAddonsPath . '/structure/libraries/Structure_nav_parser.php',
    <<<'PHP'
<?php

namespace ExpressionEngine\Addons\Structure\Libraries;

class Structure_core_nav_parser
{
    public static $variables = [];
    public static $lastAddEntryVars = null;

    public function get_variables($add_entry_vars = false)
    {
        self::$lastAddEntryVars = $add_entry_vars;

        return self::$variables;
    }
}
PHP
);

define('APP_VER', '7.5.21');
define('BASEPATH', $isolatedAddonsPath . '/');
define('PATH_ADDONS', rtrim($isolatedAddonsPath, '/') . '/');
define('PATH_PRO_ADDONS', PATH_ADDONS);
define('PATH_MOD', PATH_ADDONS);
define('STRUCTURE_NAV_BASIC_REAL_METHOD_BOOTSTRAP', true);

register_shutdown_function(static function () use ($isolatedAddonsPath): void {
    if (!is_dir($isolatedAddonsPath)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($isolatedAddonsPath, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $path) {
        if ($path->isDir()) {
            rmdir($path->getPathname());
            continue;
        }

        unlink($path->getPathname());
    }

    rmdir($isolatedAddonsPath);
});
