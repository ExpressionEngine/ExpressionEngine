<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelFetchCacheTest extends ChannelTestBase
{
    public function testIdentifierChangesCacheKey()
    {
        ee()->uri->uri_string = '/alpha';
        $spy = new class {
            public $keys = [];
            public function get($key){ $this->keys[] = $key; return false; }
            public function save($k,$v,$t=0){ return true; }
        };
        $this->setMock('cache', $spy);

        $this->channel->fetch_cache();
        $this->channel->fetch_cache('chunks');

        $this->assertCount(2, $spy->keys);
        $this->assertNotSame($spy->keys[0], $spy->keys[1]);
    }

    public function testDynamicParamsAffectCacheKeyAndMutateTemplate()
    {
        // Prepare input
        $this->setMock('input', new class {
            public function get_post($key){
                if ($key === 'author_id') { return ['1','2','']; }
                if ($key === 'search:title') { return ['foo','bar']; }
                return null;
            }
            public function get($k){ return null; }
        });
        ee()->uri->uri_string = '/beta';

        $spy = new class {
            public $keys = [];
            public function get($key){ $this->keys[] = $key; return false; }
            public function save($k,$v,$t=0){ return true; }
        };
        $this->setMock('cache', $spy);

        // Ensure _dynamic_parameters is initialized since constructor was skipped
        $ref = new ReflectionClass($this->channel);
        $prop = $ref->getProperty('_dynamic_parameters');
        $prop->setAccessible(true);
        $prop->setValue($this->channel, array('channel', 'entry_id', 'category', 'orderby',
            'sort', 'sticky', 'show_future_entries', 'show_expired', 'entry_id_from',
            'entry_id_to', 'not_entry_id', 'start_on', 'stop_before', 'year', 'month',
            'day', 'display_by', 'limit', 'username', 'status', 'group_id', 'primary_role_id', 'cat_limit',
            'month_limit', 'offset', 'author_id', 'url_title'));

        // First without dynamic params (clear)
        $this->setMock('TMPL', new class {
            public $tagproper = 'channel:entries';
            public $tagparams = [];
            public $search_fields = [];
            public function fetch_param($key){ return false; }
        });
        $this->channel->fetch_cache();
        $keyWithout = end($spy->keys);

        // Now with dynamic params (must also populate $_POST/$_GET to avoid early return)
        $this->setMock('TMPL', new class {
            public $tagproper = 'channel:entries';
            public $tagparams = [];
            public $search_fields = [];
            public function fetch_param($key){ return $key==='dynamic_parameters' ? 'author_id|search:title[*AMP*]' : null; }
        });
        $_POST = ['author_id' => ['1','2',''], 'search:title' => ['foo','bar']];
        $this->channel->fetch_cache();
        $keyWith = end($spy->keys);

        $this->assertNotSame($keyWithout, $keyWith);
        $this->assertEquals('1|2', ee()->TMPL->tagparams['author_id']);
        $this->assertEquals('foo&&bar', ee()->TMPL->search_fields['title']);
        $_POST = [];
        $_GET = [];
    }

    public function testUriStringImpactsCacheKey()
    {
        $spy = new class {
            public $keys = [];
            public function get($key){ $this->keys[] = $key; return false; }
            public function save($k,$v,$t=0){ return true; }
        };
        $this->setMock('cache', $spy);

        ee()->uri->uri_string = '/one';
        $this->channel->fetch_cache();
        $first = end($spy->keys);

        ee()->uri->uri_string = '/two';
        $this->channel->fetch_cache();
        $second = end($spy->keys);

        $this->assertNotSame($first, $second);
    }

    public function testFetchCacheReturnsFalseWhenCacheMiss()
    {
        $this->setMock('cache', new class {
            public function get($key){ return false; }
        });
        $result = $this->channel->fetch_cache();
        $this->assertFalse($result);
    }

    public function testFetchCacheReturnsCachedDataWhenHit()
    {
        $cachedData = 'cached sql data';
        $this->setMock('cache', new class($cachedData) {
            private $data;
            public function __construct($data) { $this->data = $data; }
            public function get($key){ return $this->data; }
        });
        $result = $this->channel->fetch_cache();
        $this->assertEquals($cachedData, $result);
    }

    public function testFetchCacheUsesCorrectCacheKeyPrefix()
    {
        $captured = null;
        $this->setMock('cache', new class($captured) {
            private $ref;
            public function __construct(&$ref){ $this->ref = &$ref; }
            public function get($key){ $this->ref = $key; return false; }
        });
        $this->channel->fetch_cache();
        $this->assertStringStartsWith('/sql_cache/', $captured);
        $this->assertEquals(43, strlen($captured));
    }

    public function testFetchCacheWithIdentifierAffectsKey()
    {
        $captured = null;
        $this->setMock('cache', new class($captured) {
            private $ref;
            public function __construct(&$ref){ $this->ref = &$ref; }
            public function get($key){ $this->ref = $key; return false; }
        });
        $this->channel->fetch_cache('test_identifier');
        $this->assertStringStartsWith('/sql_cache/', $captured);
        $this->assertEquals(43, strlen($captured));
    }
}




