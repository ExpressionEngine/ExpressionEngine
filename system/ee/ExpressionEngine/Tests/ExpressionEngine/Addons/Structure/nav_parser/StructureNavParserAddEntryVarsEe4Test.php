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
            public $field_id_55 = '';
            public function __construct($c){ $this->channel = $c; }
            public function getFields(){ return []; }
            public function __get($name){ if ($name === 'Channel') { return $this->channel; } return null; }
        };
        $entry101 = new class($channelProxy) {
            public $entry_id = 101; public $url_title = 'beta'; private $channel;
            public $field_id_55 = '';
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
        $entry100->field_id_55 = '{filedir_1}image.jpg';
        $entry101->field_id_55 = '{filedir_1}image2.jpg';

        // Ensure file_field library is available (StructureTestBase sets it on demand)
        $ref = new ReflectionClass($parser);
        $method = $ref->getMethod('add_entry_vars_start');
        \TestReflectionHelper::makeMethodAccessible($method);
        $method->invoke($parser);

        // Rows by entry should now include structure__ fields and path variables
        $rowsProp = $ref->getProperty('rows_by_entry');
        \TestReflectionHelper::makePropertyAccessible($rowsProp);
        $rows = $rowsProp->getValue($parser);

        $this->assertArrayHasKey('100', $rows);
        $this->assertArrayHasKey('101', $rows);
        $this->assertArrayHasKey('root:structure__uri', $rows['100']);
        $this->assertArrayHasKey('root:structure__template_id', $rows['101']);
        // Confirm the file field was parsed (value is echoed back by test file_field mock)
        $this->assertArrayHasKey('root:hero_image', $rows['100']);
        unset($parser);
    }

    public function testAddEntryVarsEe4UsesHookAndAppliesMissingStructureDefaults()
    {
        $parser = (new ReflectionClass(NavParser::class))->newInstanceWithoutConstructor();
        $parser->entry_ids = ['300'];
        $parser->rows_by_entry = [
            '300' => [
                '__prefix' => 'root:',
                'root:edit_date' => new DateTimeImmutable('@1704067200'),
            ],
        ];
        ee()->TMPL->var_single = [];
        ee()->TMPL->var_pair = [];
        ee()->TMPL->tagdata = '';

        ee()->db->setRows([]);

        $channelProxy = new class {
            public function getId(){ return 1; }
            public function __get($name){
                if ($name === 'channel_name') { return 'blog'; }
                if ($name === 'channel_title') { return 'Blog'; }
                return null;
            }
        };

        $entry = new class($channelProxy) {
            public $entry_id = 300;
            public $url_title = 'gamma';
            public $title = 'Gamma';
            public $field_id_55 = '{filedir_1}hero.jpg';
            public $field_id_56 = 'Summary';
            private $channel;

            public function __construct($channel){ $this->channel = $channel; }
            public function getFields(){ return ['title']; }
            public function __get($name){
                if ($name === 'Channel') {
                    return $this->channel;
                }
                return property_exists($this, $name) ? $this->$name : null;
            }
        };

        $channelEntries = new class([$entry]) implements IteratorAggregate {
            private $entries;
            public $Channel;

            public function __construct($entries)
            {
                $this->entries = $entries;
                $this->Channel = new class {
                    public function getIds(){ return [1]; }
                };
            }

            #[ReturnTypeWillChange]
            public function getIterator()
            {
                return new ArrayIterator($this->entries);
            }
        };

        $channelModel = new class {
            public function getId(){ return 1; }
            public function getAllCustomFields()
            {
                return [
                    new class {
                        public $field_name = 'hero_image';
                        public $field_type = 'file';
                        public function getId(){ return 55; }
                    },
                    new class {
                        public $field_name = 'summary';
                        public $field_type = 'text';
                        public function getId(){ return 56; }
                    },
                ];
            }
        };

        $modelMock = new class([$channelModel]) {
            private $channels;

            public function __construct($channels)
            {
                $this->channels = $channels;
            }

            public function get($name, $ids = null){ return $this; }
            public function with($name){ return $this; }
            public function all(){ return $this->channels; }
        };
        ee()->setMock('Model', $modelMock);

        ee()->extensions->hooks['structure_get_custom_variables'] = [
            'active' => true,
            'return' => $channelEntries,
        ];

        $ref = new ReflectionClass($parser);
        $method = $ref->getMethod('add_entry_vars_ee4');
        \TestReflectionHelper::makeMethodAccessible($method);
        $method->invoke($parser);

        $row = $parser->rows_by_entry['300'];
        $this->assertSame('Gamma', $row['root:title']);
        $this->assertSame('1704067200', $row['root:edit_date']);
        $this->assertSame('{filedir_1}hero.jpg', $row['root:hero_image']);
        $this->assertSame('Summary', $row['root:summary']);
        $this->assertSame([0], $row['root:structure__parent_id']);
        $this->assertSame('', $row['root:structure__uri']);
        $this->assertSame([0], $row['root:structure__template_id']);
        $this->assertSame('n', $row['root:structure__hidden']);
        $this->assertNull($row['root:structure__listing_channel']);
        $this->assertSame('Blog', $row['channel']);
        $this->assertSame('blog', $row['channel_short_name']);
        $this->assertSame([300, ['path_variable' => true]], $row['entry_id_path']);
        $this->assertSame(['gamma', ['path_variable' => true]], $row['url_title_path']);
        $this->assertSame(['gamma', ['path_variable' => true]], $row['title_permalink']);
        unset($parser);
    }
}
