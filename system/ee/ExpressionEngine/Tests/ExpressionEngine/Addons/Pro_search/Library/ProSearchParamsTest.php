<?php

require_once __DIR__ . '/../ProSearchTestBase.php';
require_once PATH_ADDONS . 'pro_search/libraries/Pro_search_params.php';

class ProSearchParamsTest extends ProSearchTestBase
{
    protected $params;

    protected function setUp(): void
    {
        parent::setUp();
        $this->params = new Pro_search_params();
    }

    public function testSetAndGet()
    {
        $this->params->set('foo', 'bar');
        $this->assertEquals('bar', $this->params->get('foo'));
        
        $this->params->set(['baz' => 'qux']);
        $this->assertEquals('qux', $this->params->get('baz'));
        
        $this->assertEquals(['foo' => 'bar', 'baz' => 'qux'], $this->params->get());
    }

    public function testDelete()
    {
        $this->params->set('foo', 'bar');
        $this->params->delete('foo');
        $this->assertNull($this->params->get('foo'));
    }

    public function testReset()
    {
        $this->params->set('foo', 'bar');
        $this->params->reset();
        $this->assertEmpty($this->params->get());
    }

    public function testExplode()
    {
        list($ids, $in) = $this->params->explode('1|2|3');
        $this->assertEquals(['1', '2', '3'], $ids);
        $this->assertTrue($in);

        list($ids, $in) = $this->params->explode('not 4|5');
        $this->assertEquals(['4', '5'], $ids);
        $this->assertFalse($in);
    }

    public function testImplode()
    {
        $str = $this->params->implode(['1', '2'], true);
        $this->assertEquals('1|2', $str);

        $str = $this->params->implode(['4', '5'], false);
        $this->assertEquals('not 4|5', $str);
    }

    public function testMerge()
    {
        $merged = $this->params->merge('1|2|3', '2|3|4');
        $this->assertEquals(['2', '3'], array_values($merged)); // Intersect by default (both IN)

        $merged = $this->params->merge('1|2|3', 'not 2|3');
        $this->assertEquals(['1'], array_values($merged)); // Diff (second is NOT)
        
        $merged = $this->params->merge('not 1|2|3', '2|3|4');
        $this->assertEquals(['2', '3'], array_values($merged)); // Intersect (first is NOT treated as array of IDs, but wait...)
        // Let's check logic: explode('not 1|2|3') -> ids=[1,2,3], in=false.
        // merge logic:
        // prep haystack: explode -> [1,2,3]
        // prep needles: explode -> [2,3,4], in=true
        // method: array_intersect
        // intersect([1,2,3], [2,3,4]) -> [2,3]
        // It seems merge ignores the 'not ' on the haystack side effectively when exploding?
    }

    public function testInParam()
    {
        $this->params->set('foo', '1|2|3');
        $this->assertTrue($this->params->in_param('2', 'foo'));
        $this->assertFalse($this->params->in_param('4', 'foo'));
    }

    public function testPrep()
    {
        $this->params->set('gt', 'foo');
        // prep('foo', '5') -> '>5' because 'gt' param contains 'foo'
        $val = $this->params->prep('foo', '5');
        $this->assertEquals('>5', $val);
        
        $this->params->reset();
        $this->params->set('exclude', 'bar');
        $val = $this->params->prep('bar', 'test');
        $this->assertEquals('not test', $val);
    }

    public function testOverwrite()
    {
        $this->params->set('foo', 'bar');
        $this->params->overwrite(['a' => 'b']);
        $this->assertNull($this->params->get('foo'));
        $this->assertEquals('b', $this->params->get('a'));
    }

    public function testValidQuery()
    {
        // Initially query is null
        $this->assertFalse($this->params->query_given());
        // valid_query returns true for null because it's not strictly false or empty array, 
        // but typically we check query_given first.
        
        $this->params->overwrite([], true); // Sets query to []
        $this->assertFalse($this->params->valid_query());
        
        $this->params->overwrite(['q' => 'test'], true);
        $this->assertTrue($this->params->valid_query());
        $this->assertTrue($this->params->query_given());
    }

    public function testGetVars()
    {
        $this->params->set('foo', 'bar');
        $vars = $this->params->get_vars('prefix:');
        $this->assertArrayHasKey('prefix:foo', $vars);
        $this->assertEquals('bar', $vars['prefix:foo']);
        $this->assertArrayHasKey('prefix:foo:raw', $vars);
    }

    public function testMagicGet()
    {
        // _tagparams, _params etc are private, but magic getter adds underscore?
        // __get($key) -> return $this->{'_'.$key}
        // $this->params->tagparams should return $this->_tagparams
        
        // Setup tagparams via TMPL mock
        ee()->TMPL->tagparams = ['foo' => 'bar'];
        $this->params->set(); // triggers _set_all -> reads tagparams
        
        // $this->params->tagparams is accessing property 'tagparams' which doesn't exist public, triggers __get('tagparams')
        // __get access $this->_tagparams
        $this->assertEquals(['foo' => 'bar'], $this->params->tagparams);
    }

    public function testApply()
    {
        $this->params->apply('foo', 'bar');
        $this->assertEquals('bar', ee()->TMPL->tagparams['foo']);
        
        $this->params->apply('search:title', 'test');
        $this->assertEquals('test', ee()->TMPL->search_fields['title']);
    }
}
