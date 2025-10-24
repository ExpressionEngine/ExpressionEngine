<?php

require_once __DIR__ . '/../StructureTestBase.php';
require_once __DIR__ . '/../../../../../Addons/structure/libraries/Structure_nav_parser.php';

use ExpressionEngine\Addons\Structure\Libraries\Structure_core_nav_parser as NavParser;

class StructureNavParserAddEntryVarsStartTest extends StructureTestBase
{
    public function testAddEntryVarsStartCallsEe4Path()
    {
        // Seed parser state directly to avoid constructing Channel/Structure
        $parser = (new ReflectionClass(NavParser::class))->newInstanceWithoutConstructor();
        $parser->entry_ids = ['300'];
        $parser->rows_by_entry = [ '300' => ['__prefix' => 'root:'] ];
        ee()->TMPL->var_single = [];
        ee()->TMPL->var_pair = [];
        ee()->TMPL->tagdata = '';

        // Provide minimal DB rows used by ee4 method
        ee()->db->setRows([
            ['entry_id' => 300, 'parent_id' => 0, 'structure_url_title' => 'y', 'template_id' => 3, 'hidden' => 'n', 'listing_cid' => null],
        ]);

        // Build ChannelEntry and Channel proxies
        $channelProxy = new class {
            public function getId(){ return 1; }
            public function __get($name){ if ($name === 'channel_name') return 'news'; if ($name === 'channel_title') return 'News'; return null; }
        };
        $entry300 = new class($channelProxy) {
            public $entry_id = 300; public $url_title = 'y'; private $channel;
            public function __construct($c){ $this->channel = $c; }
            public function getFields(){ return []; }
            public function __get($name){ if ($name === 'Channel') return $this->channel; return null; }
        };

        // Provide ee('Model') shim returning entries and channels
        if (!function_exists('eeModelMock')) {
            function eeModelMock($entries, $channels)
            {
                return new class($entries, $channels) implements IteratorAggregate {
                    private $entries; private $channels; private $mode = 'entries';
                    public function __construct($e,$c){ $this->entries=$e; $this->channels=$c; }
                    public function get($name, $ids = null){ $this->mode = ($name==='Channel')?'channels':'entries'; return $this; }
                    public function filter(){ return $this; }
                    public function with(){ return $this; }
                    public function all(){
                        if ($this->mode === 'channels') {
                            return new class($this->channels) implements IteratorAggregate { private $c; public function __construct($c){$this->c=$c;} public function getIterator(){ return new ArrayIterator($this->c); } };
                        }
                        return new class($this->entries) implements IteratorAggregate {
                            private $e; public function __construct($e){$this->e=$e;}
                            public function __get($name){ if ($name==='Channel') return new class { public function getIds(){ return [1]; } }; return null; }
                            public function getIterator(){ return new ArrayIterator($this->e); }
                        };
                    }
                    public function getIterator(){ return new ArrayIterator($this->entries); }
                };
            }
        }
        $channelModel = new class { public function getId(){ return 1; } public function getAllCustomFields(){ return []; } };
        if (method_exists(ee(), 'setMock')) {
            ee()->setMock('Model', eeModelMock([$entry300], [$channelModel]));
        }

        // Invoke
        $ref = new ReflectionClass($parser);
        $method = $ref->getMethod('add_entry_vars_start');
        \TestReflectionHelper::makeMethodAccessible($method);
        $method->invoke($parser);

        $rowsProp = $ref->getProperty('rows_by_entry');
        \TestReflectionHelper::makePropertyAccessible($rowsProp);
        $rows = $rowsProp->getValue($parser);
        $this->assertArrayHasKey('300', $rows);
        unset($parser);
    }
}


