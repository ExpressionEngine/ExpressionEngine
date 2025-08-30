<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelFetchDynamicParamsTest extends ChannelTestBase
{
    public function testFetchDynamicParamsReturnsEmptyStringWhenNoTagData()
    {
        // Mock template with no tagdata
        $this->setMock('TMPL', new class {
            public $tagdata = '';
            public $tagproper = '';
            public function fetch_param($key, $default = null) {
                return $default;
            }
        });

        $result = $this->channel->fetch_dynamic_params();

        $this->assertEquals('', $result);
    }

    public function testFetchDynamicParamsReturnsEmptyStringWhenNoTagProper()
    {
        // Mock template with tagdata but no tagproper
        $this->setMock('TMPL', new class {
            public $tagdata = 'some_tagdata';
            public $tagproper = '';
            public function fetch_param($key, $default = null) {
                return $default;
            }
        });

        $result = $this->channel->fetch_dynamic_params();

        $this->assertEquals('', $result);
    }

    public function testFetchDynamicParamsReturnsEmptyStringWhenNoChannelParam()
    {
        // Mock template with tagdata and tagproper but no channel param
        $this->setMock('TMPL', new class {
            public $tagdata = 'some_tagdata';
            public $tagproper = 'channel:entries';
            public function fetch_param($key) {
                return ($key === 'channel') ? null : 'some_value';
            }
        });

        $result = $this->channel->fetch_dynamic_params();

        $this->assertEquals('', $result);
    }

    public function testFetchDynamicParamsConstructsParamsString()
    {
        // Mock template with complete setup including dynamic_parameters
        $this->setMock('TMPL', new class {
            public $tagdata = 'some_tagdata';
            public $tagproper = 'channel:entries';
            public function fetch_param($key) {
                $params = [
                    'channel' => 'news',
                    'entry_id' => '123',
                    'category' => 'sports',
                    'orderby' => 'date',
                    'sort' => 'desc',
                    'dynamic_parameters' => 'channel|entry_id|category|orderby|sort'
                ];
                return $params[$key] ?? null;
            }
        });

        // Mock GET/POST data
        $this->setMock('input', new class {
            public function get_post($key) {
                $data = [
                    'channel' => 'news',
                    'entry_id' => '123',
                    'category' => 'sports',
                    'orderby' => 'date',
                    'sort' => 'desc'
                ];
                return $data[$key] ?? null;
            }
        });

        // Mock global POST array
        $_POST = [
            'channel' => 'news',
            'entry_id' => '123',
            'category' => 'sports',
            'orderby' => 'date',
            'sort' => 'desc'
        ];
        $_GET = [];

        // Initialize dynamic parameters array since constructor wasn't called
        $reflection = new ReflectionClass($this->channel);
        $property = $reflection->getProperty('_dynamic_parameters');
        $property->setAccessible(true);
        $property->setValue($this->channel, array('channel', 'entry_id', 'category', 'orderby',
            'sort', 'sticky', 'show_future_entries', 'show_expired', 'entry_id_from',
            'entry_id_to', 'not_entry_id', 'start_on', 'stop_before', 'year', 'month',
            'day', 'display_by', 'limit', 'username', 'status', 'group_id', 'primary_role_id', 'cat_limit',
            'month_limit', 'offset', 'author_id', 'url_title'));

        $result = $this->channel->fetch_dynamic_params();

        // Clean up globals
        $_POST = [];
        $_GET = [];

        // Should return a params string
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertTrue(strpos($result, 'channel=') !== false);
        $this->assertTrue(strpos($result, 'entry_id=') !== false);
        $this->assertTrue(strpos($result, 'category=') !== false);
    }

    public function testFetchDynamicParamsHandlesMultipleDynamicParameters()
    {
        // Mock template with multiple dynamic parameters
        $this->setMock('TMPL', new class {
            public $tagdata = 'some_tagdata';
            public $tagproper = 'channel:entries';
            public function fetch_param($key) {
                $params = [
                    'channel' => 'news',
                    'entry_id' => '123',
                    'category' => 'sports',
                    'orderby' => 'date',
                    'sort' => 'desc',
                    'sticky' => 'yes',
                    'show_future_entries' => 'no',
                    'dynamic_parameters' => 'channel|entry_id|category|orderby|sort|sticky|show_future_entries'
                ];
                return $params[$key] ?? null;
            }
        });

        // Mock GET/POST data
        $this->setMock('input', new class {
            public function get_post($key) {
                $data = [
                    'channel' => 'news',
                    'entry_id' => '123',
                    'category' => 'sports',
                    'orderby' => 'date',
                    'sort' => 'desc',
                    'sticky' => 'yes',
                    'show_future_entries' => 'no'
                ];
                return $data[$key] ?? null;
            }
        });

        // Mock global POST array
        $_POST = [
            'channel' => 'news',
            'entry_id' => '123',
            'category' => 'sports',
            'orderby' => 'date',
            'sort' => 'desc',
            'sticky' => 'yes',
            'show_future_entries' => 'no'
        ];
        $_GET = [];

        // Initialize dynamic parameters array since constructor wasn't called
        $reflection = new ReflectionClass($this->channel);
        $property = $reflection->getProperty('_dynamic_parameters');
        $property->setAccessible(true);
        $property->setValue($this->channel, array('channel', 'entry_id', 'category', 'orderby',
            'sort', 'sticky', 'show_future_entries', 'show_expired', 'entry_id_from',
            'entry_id_to', 'not_entry_id', 'start_on', 'stop_before', 'year', 'month',
            'day', 'display_by', 'limit', 'username', 'status', 'group_id', 'primary_role_id', 'cat_limit',
            'month_limit', 'offset', 'author_id', 'url_title'));

        $result = $this->channel->fetch_dynamic_params();

        // Clean up globals
        $_POST = [];
        $_GET = [];

        // Should return a params string with multiple parameters
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertTrue(strpos($result, 'channel=') !== false);
        $this->assertTrue(strpos($result, 'entry_id=') !== false);
        $this->assertTrue(strpos($result, 'category=') !== false);
        $this->assertTrue(strpos($result, 'orderby=') !== false);
        $this->assertTrue(strpos($result, 'sort=') !== false);
    }

    public function testFetchDynamicParamsIgnoresNonDynamicParameters()
    {
        // Mock template with dynamic and non-dynamic parameters
        $this->setMock('TMPL', new class {
            public $tagdata = 'some_tagdata';
            public $tagproper = 'channel:entries';
            public function fetch_param($key) {
                $params = [
                    'channel' => 'news',
                    'entry_id' => '123',
                    'limit' => '10', // This is dynamic
                    'cache' => 'yes', // This is NOT dynamic
                    'refresh' => '60', // This is NOT dynamic
                    'dynamic_parameters' => 'channel|entry_id|limit|cache|refresh'
                ];
                return $params[$key] ?? null;
            }
        });

        // Mock GET/POST data
        $this->setMock('input', new class {
            public function get_post($key) {
                $data = [
                    'channel' => 'news',
                    'entry_id' => '123',
                    'limit' => '10',
                    'cache' => 'yes',
                    'refresh' => '60'
                ];
                return $data[$key] ?? null;
            }
        });

        // Mock global POST array
        $_POST = [
            'channel' => 'news',
            'entry_id' => '123',
            'limit' => '10',
            'cache' => 'yes',
            'refresh' => '60'
        ];
        $_GET = [];

        // Initialize dynamic parameters array since constructor wasn't called
        $reflection = new ReflectionClass($this->channel);
        $property = $reflection->getProperty('_dynamic_parameters');
        $property->setAccessible(true);
        $property->setValue($this->channel, array('channel', 'entry_id', 'category', 'orderby',
            'sort', 'sticky', 'show_future_entries', 'show_expired', 'entry_id_from',
            'entry_id_to', 'not_entry_id', 'start_on', 'stop_before', 'year', 'month',
            'day', 'display_by', 'limit', 'username', 'status', 'group_id', 'primary_role_id', 'cat_limit',
            'month_limit', 'offset', 'author_id', 'url_title'));

        $result = $this->channel->fetch_dynamic_params();

        // Clean up globals
        $_POST = [];
        $_GET = [];

        // Should include dynamic parameters but not non-dynamic ones
        $this->assertIsString($result);
        $this->assertTrue(strpos($result, 'channel=') !== false);
        $this->assertTrue(strpos($result, 'entry_id=') !== false);
        $this->assertTrue(strpos($result, 'limit=') !== false);
        // Non-dynamic parameters should not be included
        $this->assertFalse(strpos($result, 'cache=') !== false);
        $this->assertFalse(strpos($result, 'refresh=') !== false);
    }

    public function testFetchDynamicParamsReturnsCleanString()
    {
        // Mock template with complete setup
        $this->setMock('TMPL', new class {
            public $tagdata = 'some_tagdata';
            public $tagproper = 'channel:entries';
            public function fetch_param($key) {
                $params = [
                    'channel' => 'news',
                    'entry_id' => '123',
                    'dynamic_parameters' => 'channel|entry_id'
                ];
                return $params[$key] ?? null;
            }
        });

        // Mock GET/POST data
        $this->setMock('input', new class {
            public function get_post($key) {
                $data = [
                    'channel' => 'news',
                    'entry_id' => '123'
                ];
                return $data[$key] ?? null;
            }
        });

        // Mock global POST array
        $_POST = [
            'channel' => 'news',
            'entry_id' => '123'
        ];
        $_GET = [];

        // Initialize dynamic parameters array since constructor wasn't called
        $reflection = new ReflectionClass($this->channel);
        $property = $reflection->getProperty('_dynamic_parameters');
        $property->setAccessible(true);
        $property->setValue($this->channel, array('channel', 'entry_id', 'category', 'orderby',
            'sort', 'sticky', 'show_future_entries', 'show_expired', 'entry_id_from',
            'entry_id_to', 'not_entry_id', 'start_on', 'stop_before', 'year', 'month',
            'day', 'display_by', 'limit', 'username', 'status', 'group_id', 'primary_role_id', 'cat_limit',
            'month_limit', 'offset', 'author_id', 'url_title'));

        $result = $this->channel->fetch_dynamic_params();

        // Clean up globals
        $_POST = [];
        $_GET = [];

        // Should return a clean params string without extra formatting
        $this->assertIsString($result);
        $this->assertStringStartsWith('channel=', $result);
        // Should not have leading/trailing whitespace or other artifacts
        $this->assertEquals(trim($result), $result);
    }
}
