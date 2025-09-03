<?php

require_once __DIR__ . '/../StructureTestBase.php';
require_once __DIR__ . '/../../../../../Addons/structure/libraries/Structure_nav_parser.php';

use ExpressionEngine\Addons\Structure\Libraries\Structure_core_nav_parser as NavParser;

class StructureNavParserAddEntryVarsEe4Test extends StructureTestBase
{
    private function makeParserWithRows(): array
    {
        $parser = (new ReflectionClass(NavParser::class))->newInstanceWithoutConstructor();
        // Emulate parse_ul results
        $parser->entry_ids = ['100', '101'];
        $parser->rows_by_entry = [
            '100' => ['__prefix' => 'root:'],
            '101' => ['__prefix' => 'root:'],
        ];
        // Ensure template vars are available for any parsing helpers
        ee()->TMPL->var_single = [];
        ee()->TMPL->var_pair = [];
        ee()->TMPL->tagdata = '';
        return [$parser, []];
    }

    public function testAddEntryVarsEe4PopulatesBasicFields()
    {
        list($parser, $vars) = $this->makeParserWithRows();

        // Provide DB rows for structure table
        ee()->db->setRows([
            ['entry_id' => 100, 'parent_id' => 0, 'structure_url_title' => 'alpha', 'template_id' => 1, 'hidden' => 'n', 'listing_cid' => null],
            ['entry_id' => 101, 'parent_id' => 0, 'structure_url_title' => 'beta', 'template_id' => 2, 'hidden' => 'y', 'listing_cid' => 5],
        ]);

        // Fake Model collection for ChannelEntry with Channel relationship
        $channelProxy = new class {
            public function getId(){ return 1; }
            public function __get($name){
                if ($name === 'channel_name') { return 'news'; }
                if ($name === 'channel_title') { return 'News'; }
                return null;
            }
            public function getIds(){ return [1]; }
        };

        $entry100 = new class($channelProxy) {
            public $entry_id = 100; public $url_title = 'alpha'; private $channel;
            public function __construct($c){ $this->channel = $c; }
            public function getFields(){ return []; }
            public function __get($name){ if ($name === 'Channel') { return $this->channel; } return null; }
        };
        $entry101 = new class($channelProxy) {
            public $entry_id = 101; public $url_title = 'beta'; private $channel;
            public function __construct($c){ $this->channel = $c; }
            public function getFields(){ return []; }
            public function __get($name){ if ($name === 'Channel') { return $this->channel; } return null; }
        };

        // Build minimal ee('Model') mocks through a static accessor
        if (!function_exists('eeModelMock')) {
            function eeModelMock($entries, $channels)
            {
                return new class($entries, $channels) implements IteratorAggregate {
                    private $entries;
                    private $channels;
                    private $mode = 'entries';
                    public function __construct($e, $c){ $this->entries = $e; $this->channels = $c; }
                    public function get($name, $ids = null){
                        $this->mode = ($name === 'Channel') ? 'channels' : 'entries';
                        return $this;
                    }
                    public function filter(){ return $this; }
                    public function with(){ return $this; }
                    public function all(){
                        if ($this->mode === 'channels') {
                                                    return new class($this->channels) implements IteratorAggregate {
                            private $channels;
                            public function __construct($c){ $this->channels = $c; }
                            #[ReturnTypeWillChange]
                            public function getIterator(){ return new ArrayIterator($this->channels); }
                        };
                        }
                        // Wrap array in a proxy that exposes Channel property methods used
                        return new class($this->entries) implements IteratorAggregate {
                            private $entries; public function __construct($e){$this->entries=$e;}
                            public function __get($name){
                                if ($name === 'Channel') {
                                    return new class($this->entries) {
                                        private $entries; public function __construct($e){$this->entries=$e;}
                                        public function getIds(){ return [1]; }
                                    };
                                }
                                return null;
                            }
                            #[ReturnTypeWillChange]
                            public function getIterator(){ return new ArrayIterator($this->entries); }
                        };
                    }
                    #[ReturnTypeWillChange]
                    public function getIterator(){ return new ArrayIterator($this->entries); }
                };
            }
        }

        // Provide global ee('Model') shim
        if (!function_exists('ee')) {
            $this->markTestSkipped('ee() shim not available');
        }

        // Channel model object(s) for the 'Channel' lookup
        $channelModel = new class {
            public function getId(){ return 1; }
            public function getAllCustomFields(){
                // Return a single file-type custom field with method getId()
                return [ new class {
                    public $field_name = 'hero_image';
                    public $field_type = 'file';
                    public function getId(){ return 55; }
                } ];
            }
        };

        // Monkey-patch ee('Model') via ee()->setMock if supported
        if (method_exists(ee(), 'setMock')) {
            ee()->setMock('Model', eeModelMock([$entry100, $entry101], [$channelModel]));
        }

        // Seed file field value for the custom field path
        // Use magic property access in add_entry_vars_ee4: field_id_55
        @$entry100->field_id_55 = '{filedir_1}image.jpg';
        @$entry101->field_id_55 = '{filedir_1}image2.jpg';

        // Ensure file_field library is available (StructureTestBase sets it on demand)
        $ref = new ReflectionClass($parser);
        $method = $ref->getMethod('add_entry_vars_start');
        $method->setAccessible(true);
        $method->invoke($parser);

        // Rows by entry should now include structure__ fields and path variables
        $rowsProp = $ref->getProperty('rows_by_entry');
        $rowsProp->setAccessible(true);
        $rows = $rowsProp->getValue($parser);

        $this->assertArrayHasKey('100', $rows);
        $this->assertArrayHasKey('101', $rows);
        $this->assertArrayHasKey('root:structure__uri', $rows['100']);
        $this->assertArrayHasKey('root:structure__template_id', $rows['101']);
        // Confirm the file field was parsed (value is echoed back by test file_field mock)
        $this->assertArrayHasKey('root:hero_image', $rows['100']);
        unset($parser);
    }
}


