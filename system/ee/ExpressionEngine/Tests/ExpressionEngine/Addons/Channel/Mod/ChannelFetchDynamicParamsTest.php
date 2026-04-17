<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelFetchDynamicParamsTest extends ChannelTestBase
{
    public function testNoDynamicParametersNoInputNoOp()
    {
        $this->setMock('TMPL', new class {
            public $tagproper = 'channel:entries';
            public $tagparams = [];
            public $search_fields = [];
            public function fetch_param($key){ return false; }
        });
        $out = $this->channel->fetch_dynamic_params();
        $this->assertSame('', $out);
        $this->assertEmpty(ee()->TMPL->tagparams);
        $this->assertEmpty(ee()->TMPL->search_fields);
    }

    public function testAllowedParamsArrayJoinAndZeroPreserved()
    {
        // Stub TMPL and input
        $this->setMock('TMPL', new class {
            public $tagproper = 'channel:entries';
            public $tagparams = [];
            public $search_fields = [];
            public function fetch_param($key){ return $key==='dynamic_parameters' ? 'author_id|limit' : null; }
        });
        $this->setMock('input', new class {
            public function get_post($key){
                if ($key==='author_id') return ['0','1',''];
                if ($key==='limit') return '25';
                return null;
            }
            public function get($k){ return null; }
        });
        // Initialize required dynamic param whitelist
        $ref = new ReflectionClass(Channel::class);
        $prop = $ref->getProperty('_dynamic_parameters');
        \TestReflectionHelper::makePropertyAccessible($prop);
        $prop->setValue($this->channel, array('channel', 'entry_id', 'category', 'orderby',
            'sort', 'sticky', 'show_future_entries', 'show_expired', 'entry_id_from',
            'entry_id_to', 'not_entry_id', 'start_on', 'stop_before', 'year', 'month',
            'day', 'display_by', 'limit', 'username', 'status', 'group_id', 'primary_role_id', 'cat_limit',
            'month_limit', 'offset', 'author_id', 'url_title'));
        // Provide POST so method doesn't early-return
        $_POST = ['author_id' => ['0','1',''], 'limit' => '25'];
        $out = $this->channel->fetch_dynamic_params();
        $this->assertStringContainsString('author_id="0|1"', $out);
        $this->assertStringContainsString('limit="25"', $out);
        $this->assertEquals('0|1', ee()->TMPL->tagparams['author_id']);
        $this->assertEquals('25', ee()->TMPL->tagparams['limit']);
        $_POST = [];
        $_GET = [];
    }

    public function testSearchParamsDoubleAmpersand()
    {
        $this->setMock('TMPL', new class {
            public $tagproper = 'channel:entries';
            public $tagparams = [];
            public $search_fields = [];
            public function fetch_param($key){ return $key==='dynamic_parameters' ? 'search:title[*AMP*]' : null; }
        });
        $this->setMock('input', new class {
            public function get_post($key){
                if ($key==='search:title') return ['foo','bar'];
                return null;
            }
            public function get($k){ return null; }
        });
        $ref = new ReflectionClass(Channel::class);
        $prop = $ref->getProperty('_dynamic_parameters');
        \TestReflectionHelper::makePropertyAccessible($prop);
        $prop->setValue($this->channel, array('channel', 'entry_id', 'category', 'orderby',
            'sort', 'sticky', 'show_future_entries', 'show_expired', 'entry_id_from',
            'entry_id_to', 'not_entry_id', 'start_on', 'stop_before', 'year', 'month',
            'day', 'display_by', 'limit', 'username', 'status', 'group_id', 'primary_role_id', 'cat_limit',
            'month_limit', 'offset', 'author_id', 'url_title'));
        $_POST = ['search:title' => ['foo','bar']];
        $out = $this->channel->fetch_dynamic_params();
        $this->assertStringContainsString('title="foo&&bar"', $out);
        $this->assertEquals('foo&&bar', ee()->TMPL->search_fields['title']);
        $_POST = [];
        $_GET = [];
    }

    public function testNonWhitelistedParamsIgnored()
    {
        $this->setMock('TMPL', new class {
            public $tagproper = 'channel:entries';
            public $tagparams = [];
            public $search_fields = [];
            public function fetch_param($key){ return $key==='dynamic_parameters' ? 'not_a_param' : null; }
        });
        $ref = new ReflectionClass(Channel::class);
        $prop = $ref->getProperty('_dynamic_parameters');
        \TestReflectionHelper::makePropertyAccessible($prop);
        $prop->setValue($this->channel, array('channel', 'entry_id', 'category', 'orderby',
            'sort', 'sticky', 'show_future_entries', 'show_expired', 'entry_id_from',
            'entry_id_to', 'not_entry_id', 'start_on', 'stop_before', 'year', 'month',
            'day', 'display_by', 'limit', 'username', 'status', 'group_id', 'primary_role_id', 'cat_limit',
            'month_limit', 'offset', 'author_id', 'url_title'));
        $_POST = ['not_a_param' => 'x'];
        $this->setMock('input', new class {
            public function get_post($key){ return 'x'; }
            public function get($k){ return null; }
        });
        $out = $this->channel->fetch_dynamic_params();
        $this->assertSame('', $out);
        $this->assertArrayNotHasKey('not_a_param', ee()->TMPL->tagparams);
        $_POST = [];
        $_GET = [];
    }
}
