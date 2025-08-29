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
                return new FakeDbResult([]);
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
            public function query($sql){ return new FakeDbResult([]); }
        });
        $pages = ['url' => '/', 'uris' => [9 => '/x']];
        $this->structure->set_site_pages(5, $pages);
        $decoded = unserialize(base64_decode($captured->data['site_pages']));
        $this->assertArrayHasKey(5, $decoded);
        $this->assertStringContainsString("site_id = '5'", $captured->where);
    }
}



