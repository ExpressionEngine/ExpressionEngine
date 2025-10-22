<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelParseChannelEntriesTest extends ChannelTestBase
{
    public function testPerRowCallbackInvokedAndOutputAggregated()
    {
        // DB rows returned by initial query
        $rows = [
            ['entry_id' => 1, 'title' => 'First'],
            ['entry_id' => 2, 'title' => 'Second'],
        ];
        $this->setDbRows($rows);

        // Provide a simple parser that applies callbacks and concatenates tagdata
        $this->setMock('channel_entries_parser', new class {
            public function create($tagdata) { return new class($tagdata) {
                private $tagdata; public function __construct($t){ $this->tagdata = $t; }
                public function parse($ctx, $data, $config) {
                    $out = '';
                    foreach ($data['entries'] as $row) {
                        $td = $this->tagdata;
                        if (isset($config['callbacks']['tagdata_loop_end'])) {
                            $td = call_user_func($config['callbacks']['tagdata_loop_end'], $td, $row);
                        }
                        $out .= $td;
                    }
                    return $out;
                }
            }; }
        });

        // Tagdata with a marker to help us detect callback effects
        $this->setTemplateTagdata('X');

        // Simulate Channel having a DB query result already executed
        $this->channel->query = new class($rows) {
            private $r; public function __construct($r){ $this->r = $r; }
            public function result_array(){ return $this->r; }
            public function free_result() { /* no-op */ }
        };

        // Per-row callback appends row title
        $result = $this->channel->parse_channel_entries(function ($tagdata, $row) {
            return $tagdata . $row['title'] . '|';
        });

        $this->assertNull($result); // method sets return_data, does not return a value
        $this->assertEquals('First|Second|', $this->channel->return_data);
    }

    public function testEmptyResultReturnsNoResults()
    {
        $this->setDbRows([]); // no rows
        $this->setTemplateTagdata('X');

        // Minimal parser stub not used because early no-results path triggers
        $this->setMock('channel_entries_parser', new class {
            public function create($t){ return new class { public function parse(){ return 'UNUSED'; } }; }
        });

        $this->channel->parse_channel_entries();
        $this->assertEquals('NO_RESULTS', $this->channel->return_data);
    }

    public function testLivePreviewConditionsIncludeNewEntryWhenConditionPasses()
    {
        // No DB rows; rely on preview add path
        $this->setDbRows([]);
        $this->setTemplateTagdata('OK');

        // Mock LivePreview service
        $previewData = [
            'entry_id' => 123,
            'url_title' => 'foo',
            'status' => 'open',
            'expiration_date' => 0,
            'channel_name' => 'news',
        ];
        // Make URL title match preview data to trigger inclusion
        $this->channel->query_string = 'foo';
        $this->setTemplateParams(['url_title' => 'foo', 'channel' => 'news']);
        // Set protected preview_conditions via reflection
        $ref = new ReflectionClass('Channel');
        $prop = $ref->getProperty('preview_conditions');
        $prop->setAccessible(true);
        $prop->setValue($this->channel, ["(t.status = 'open')"]);
        $this->setMock('LivePreview', new class($previewData) {
            private $d; public function __construct($d){ $this->d = $d; }
            public function hasEntryData(){ return true; }
            public function getEntryData(){ return $this->d; }
        });

        // Parser returns tagdata as-is
        $this->setMock('channel_entries_parser', new class {
            public function create($tagdata) { return new class($tagdata) {
                private $tagdata; public function __construct($t){ $this->tagdata = $t; }
                public function parse(){ return $this->tagdata; }
            }; }
        });

        $this->channel->parse_channel_entries();
        // Live preview should bypass early NO_RESULTS
        $this->assertNotEquals('NO_RESULTS', $this->channel->return_data);
    }
}