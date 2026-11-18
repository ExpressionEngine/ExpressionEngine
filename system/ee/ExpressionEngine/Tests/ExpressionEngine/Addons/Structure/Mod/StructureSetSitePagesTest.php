<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructureSetSitePagesTest extends StructureTestBase
{
    public function testSetSitePagesEncodesAndStoresPagesBySiteId()
    {
        $captured = (object) ['data' => null, 'table' => null, 'where' => null, 'queries' => []];

        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;
            public function __construct($cap) { $this->cap = $cap; }
            public function escape_str($str) { return addslashes($str); }
            public function update_string($table, $data, $where)
            {
                $this->cap->table = $table;
                $this->cap->data = $data;
                $this->cap->where = $where;
                return 'UPDATE exp_sites SET site_pages=... WHERE ' . $where;
            }
            public function query($sql)
            {
                $this->cap->queries[] = $sql;
                return new eeDbResultMock([]);
            }
        });

        ee()->config->items['site_id'] = 2;
        $pages = ['url' => '/', 'uris' => [123 => '/test']];
        $this->structure->set_site_pages(null, $pages);

        $this->assertSame('exp_sites', $captured->table);
        $this->assertArrayHasKey('site_pages', $captured->data);

        $decoded = unserialize(base64_decode($captured->data['site_pages']));
        $this->assertArrayHasKey(2, $decoded);
        $this->assertSame('/test', $decoded[2]['uris'][123]);
        $this->assertNotEmpty($captured->queries);
    }

    public function testSetSitePagesUsesProvidedSiteIdOverConfig()
    {
        $captured = (object) ['data' => null, 'where' => null];
        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap; public function __construct($c){$this->cap=$c;}
            public function escape_str($str){ return addslashes($str); }
            public function update_string($table, $data, $where){ $this->cap->data=$data; $this->cap->where=$where; return 'UPDATE'; }
            public function query($sql){ return new eeDbResultMock([]); }
        });
        $pages = ['url' => '/', 'uris' => [9 => '/x']];
        $this->structure->set_site_pages(5, $pages);
        $decoded = unserialize(base64_decode($captured->data['site_pages']));
        $this->assertArrayHasKey(5, $decoded);
        $this->assertStringContainsString("site_id = '5'", $captured->where);
    }

    public function testSetSitePagesStripsTrailingSlashWhenSettingDisabled()
    {
        require_once PATH_ADDONS . 'structure/sql.structure.php';

        $captured = (object) ['data' => null, 'table' => null, 'where' => null, 'queries' => []];

        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;
            public function __construct($cap) { $this->cap = $cap; }
            public function escape_str($str) { return addslashes($str); }
            public function update_string($table, $data, $where)
            {
                $this->cap->table = $table;
                $this->cap->data = $data;
                $this->cap->where = $where;
                return 'UPDATE exp_sites SET site_pages=... WHERE ' . $where;
            }
            public function query($sql)
            {
                $this->cap->queries[] = $sql;
                return new eeDbResultMock([]);
            }
        });

        // Mock addons_model to return module as not installed so get_settings() returns early
        ee()->setMock('addons_model', new class {
            public function module_installed($module) { return false; }
        });

        // Create a test subclass that overrides get_settings()
        $sql = new class extends Sql_structure {
            public function get_settings() {
                return ['add_trailing_slash' => 'n'];
            }
            public function get_site_id() {
                return 1;
            }
        };

        ee()->config->items['site_id'] = 2;
        $pages = ['url' => '/', 'uris' => [123 => '/test/', 456 => '/about/page/', 789 => '/no-slash']];
        $sql->set_site_pages(null, $pages);

        $decoded = unserialize(base64_decode($captured->data['site_pages']));
        $this->assertArrayHasKey(2, $decoded);
        $this->assertSame('/test', $decoded[2]['uris'][123], 'Trailing slash should be stripped when setting is disabled');
        $this->assertSame('/about/page', $decoded[2]['uris'][456], 'Trailing slash should be stripped when setting is disabled');
        $this->assertSame('/no-slash', $decoded[2]['uris'][789], 'URI without trailing slash should remain unchanged');
    }

    public function testSetSitePagesPreservesTrailingSlashWhenSettingEnabled()
    {
        require_once PATH_ADDONS . 'structure/sql.structure.php';

        $captured = (object) ['data' => null, 'table' => null, 'where' => null, 'queries' => []];

        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;
            public function __construct($cap) { $this->cap = $cap; }
            public function escape_str($str) { return addslashes($str); }
            public function update_string($table, $data, $where)
            {
                $this->cap->table = $table;
                $this->cap->data = $data;
                $this->cap->where = $where;
                return 'UPDATE exp_sites SET site_pages=... WHERE ' . $where;
            }
            public function query($sql)
            {
                $this->cap->queries[] = $sql;
                return new eeDbResultMock([]);
            }
        });

        // Mock addons_model to return module as not installed so get_settings() returns early
        ee()->setMock('addons_model', new class {
            public function module_installed($module) { return false; }
        });

        // Create a test subclass that overrides get_settings()
        $sql = new class extends Sql_structure {
            public function get_settings() {
                return ['add_trailing_slash' => 'y'];
            }
            public function get_site_id() {
                return 1;
            }
        };

        ee()->config->items['site_id'] = 2;
        $pages = ['url' => '/', 'uris' => [123 => '/test/', 456 => '/about/page', 789 => '/no-slash']];
        $sql->set_site_pages(null, $pages);

        $decoded = unserialize(base64_decode($captured->data['site_pages']));
        $this->assertArrayHasKey(2, $decoded);
        $this->assertSame('/test/', $decoded[2]['uris'][123], 'Trailing slash should be preserved when setting is enabled');
        $this->assertSame('/about/page', $decoded[2]['uris'][456], 'URI without trailing slash should remain unchanged when setting is enabled');
        $this->assertSame('/no-slash', $decoded[2]['uris'][789], 'URI without trailing slash should remain unchanged when setting is enabled');
    }

    public function testSetSitePagesHandlesRootUriCorrectly()
    {
        require_once PATH_ADDONS . 'structure/sql.structure.php';

        $captured = (object) ['data' => null, 'table' => null, 'where' => null, 'queries' => []];

        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;
            public function __construct($cap) { $this->cap = $cap; }
            public function escape_str($str) { return addslashes($str); }
            public function update_string($table, $data, $where)
            {
                $this->cap->table = $table;
                $this->cap->data = $data;
                $this->cap->where = $where;
                return 'UPDATE exp_sites SET site_pages=... WHERE ' . $where;
            }
            public function query($sql)
            {
                $this->cap->queries[] = $sql;
                return new eeDbResultMock([]);
            }
        });

        // Mock addons_model to return module as not installed so get_settings() returns early
        ee()->setMock('addons_model', new class {
            public function module_installed($module) { return false; }
        });

        // Test with trailing slash disabled
        $sqlDisabled = new class extends Sql_structure {
            public function get_settings() {
                return ['add_trailing_slash' => 'n'];
            }
            public function get_site_id() {
                return 1;
            }
        };

        ee()->config->items['site_id'] = 2;
        $pages = ['url' => '/', 'uris' => [1 => '/']];
        $sqlDisabled->set_site_pages(null, $pages);

        $decoded = unserialize(base64_decode($captured->data['site_pages']));
        $this->assertArrayHasKey(2, $decoded);
        $this->assertSame('/', $decoded[2]['uris'][1], 'Root URI should remain unchanged when trailing slash is disabled');

        // Reset captured data
        $captured->data = null;

        // Test with trailing slash enabled
        $sqlEnabled = new class extends Sql_structure {
            public function get_settings() {
                return ['add_trailing_slash' => 'y'];
            }
            public function get_site_id() {
                return 1;
            }
        };

        $pages = ['url' => '/', 'uris' => [1 => '/']];
        $sqlEnabled->set_site_pages(null, $pages);

        $decoded = unserialize(base64_decode($captured->data['site_pages']));
        $this->assertArrayHasKey(2, $decoded);
        $this->assertSame('/', $decoded[2]['uris'][1], 'Root URI should remain unchanged when trailing slash is enabled');
    }

    public function testSetSitePagesHandlesMixedUrisWithTrailingSlashEnabled()
    {
        require_once PATH_ADDONS . 'structure/sql.structure.php';

        $captured = (object) ['data' => null, 'table' => null, 'where' => null, 'queries' => []];

        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;
            public function __construct($cap) { $this->cap = $cap; }
            public function escape_str($str) { return addslashes($str); }
            public function update_string($table, $data, $where)
            {
                $this->cap->table = $table;
                $this->cap->data = $data;
                $this->cap->where = $where;
                return 'UPDATE exp_sites SET site_pages=... WHERE ' . $where;
            }
            public function query($sql)
            {
                $this->cap->queries[] = $sql;
                return new eeDbResultMock([]);
            }
        });

        // Mock addons_model to return module as not installed so get_settings() returns early
        ee()->setMock('addons_model', new class {
            public function module_installed($module) { return false; }
        });

        // Create a test subclass that overrides get_settings()
        $sql = new class extends Sql_structure {
            public function get_settings() {
                return ['add_trailing_slash' => 'y'];
            }
            public function get_site_id() {
                return 1;
            }
        };

        ee()->config->items['site_id'] = 2;
        $pages = ['url' => '/', 'uris' => [
            123 => '/test/',
            456 => '/about',
            789 => '/page/',
            999 => '/mixed/path'
        ]];
        $sql->set_site_pages(null, $pages);

        $decoded = unserialize(base64_decode($captured->data['site_pages']));
        $this->assertArrayHasKey(2, $decoded);
        $this->assertSame('/test/', $decoded[2]['uris'][123], 'URI with trailing slash should be preserved');
        $this->assertSame('/about', $decoded[2]['uris'][456], 'URI without trailing slash should remain unchanged');
        $this->assertSame('/page/', $decoded[2]['uris'][789], 'URI with trailing slash should be preserved');
        $this->assertSame('/mixed/path', $decoded[2]['uris'][999], 'URI without trailing slash should remain unchanged');
    }

    public function testSetSitePagesDefaultsToTrimBranchWhenTrailingSlashSettingMissingAndUrisEmpty()
    {
        require_once PATH_ADDONS . 'structure/sql.structure.php';

        $captured = (object) ['data' => null, 'where' => null, 'queries' => []];

        ee()->setMock('db', new class($captured) extends FakeDb {
            private $cap;
            public function __construct($cap) { $this->cap = $cap; }
            public function escape_str($str) { return addslashes($str); }
            public function update_string($table, $data, $where)
            {
                $this->cap->data = $data;
                $this->cap->where = $where;
                return 'UPDATE exp_sites SET site_pages=... WHERE ' . $where;
            }
            public function query($sql)
            {
                $this->cap->queries[] = $sql;
                return new eeDbResultMock([]);
            }
        });

        ee()->setMock('addons_model', new class {
            public function module_installed($module) { return false; }
        });

        $sql = new class extends Sql_structure {
            public function get_settings()
            {
                return [];
            }

            public function get_site_id()
            {
                return 1;
            }
        };

        ee()->config->items['site_id'] = 2;
        $pages = ['url' => '/', 'uris' => []];
        $sql->set_site_pages(null, $pages);

        $decoded = unserialize(base64_decode($captured->data['site_pages']));
        $this->assertArrayHasKey(2, $decoded);
        $this->assertSame([], $decoded[2]['uris']);
        $this->assertSame("site_id='2'", $captured->where);
        $this->assertNotEmpty($captured->queries);
    }
}


