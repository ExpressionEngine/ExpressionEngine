<?php

use PHPUnit\Framework\TestCase;

class StructureNavBasicRealMethodTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testNavBasicCoversNoResultsAndParsedPaths()
    {
        require_once __DIR__ . '/../../../../eeObjectMock.php';

        if (!defined('APP_VER')) {
            define('APP_VER', '7.5.14');
        }
        if (!defined('BASEPATH')) {
            define('BASEPATH', __DIR__);
        }
        if (defined('PATH_ADDONS')) {
            $this->markTestSkipped('Isolated PATH_ADDONS stubbing is not available under this bootstrap.');
        }
        if (!defined('PATH_ADDONS')) {
            $addonsPath = defined('SYSPATH')
                ? (SYSPATH . 'ee/ExpressionEngine/Addons/')
                : (__DIR__ . '/../../../../Addons/');
            define('PATH_ADDONS', $addonsPath);
        }
        if (!defined('PATH_MOD')) {
            define('PATH_MOD', PATH_ADDONS);
        }
        if (!defined('PATH_PRO_ADDONS')) {
            define('PATH_PRO_ADDONS', PATH_ADDONS);
        }

        if (!class_exists('ExpressionEngine\\Addons\\Structure\\Libraries\\Structure_core_nav_parser')) {
            eval(<<<'PHPSTUB'
namespace ExpressionEngine\Addons\Structure\Libraries;
class Structure_core_nav_parser {
    public function get_variables($add_entry_vars = false)
    {
        return $GLOBALS['__structure_nav_basic_vars'] ?? [];
    }
}
PHPSTUB
            );
        }

        require_once '/Users/tomjaeger/Sites/ee_test_repo/system/ee/ExpressionEngine/Addons/structure/mod.structure.php';

        ee()->resetMocks();
        ee()->setMock('load', new class {
            public function add_package_path($path)
            {
            }
            public function library($name)
            {
            }
            public function helper($name)
            {
            }
        });
        ee()->setMock('TMPL', new class {
            public $tagdata = 'TAGDATA';
            public function no_results()
            {
                return 'NO_RESULTS';
            }
            public function parse_variables($tagdata, $variables)
            {
                return 'PARSED:' . count($variables);
            }
        });

        $structure = (new ReflectionClass('Structure'))->newInstanceWithoutConstructor();

        $GLOBALS['__structure_nav_basic_vars'] = [];
        $this->assertSame('NO_RESULTS', $structure->nav_basic(false));

        $GLOBALS['__structure_nav_basic_vars'] = [['entry_id' => 1]];
        $this->assertSame('PARSED:1', $structure->nav_basic(true));
    }
}
