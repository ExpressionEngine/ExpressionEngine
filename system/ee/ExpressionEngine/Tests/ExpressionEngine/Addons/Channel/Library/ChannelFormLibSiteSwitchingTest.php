<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibSiteSwitchingTest extends ChannelFormLibTestBase
{
    public function testSwitchSiteSetsSiteIdConfiguration()
    {
        // Setup config mock to track method calls
        $configMock = new class {
            public $setItemCalls = [];
            public $getCachedSitePrefsCalls = [];

            public function set_item($key, $value) {
                $this->setItemCalls[] = ['key' => $key, 'value' => $value];
            }

            public function item($key) {
                // Return null/false since no site_id is set initially
                return null;
            }

            public function get_cached_site_prefs($site_id) {
                $this->getCachedSitePrefsCalls[] = $site_id;
                // Return mock site preferences
                return [
                    'site_name' => 'Test Site',
                    'site_url' => 'https://example.com/',
                    'site_index' => '',
                    'template_group' => 'default',
                    'template' => 'index',
                    'site_pages' => []
                ];
            }
        };

        $this->setMock('config', $configMock);

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('switch_site');
        \TestReflectionHelper::makeMethodAccessible($method);

        // Call with site_id = 2
        $result = $method->invoke($this->channelFormLib, 2);

        // Verify method returned successfully
        $this->assertNull($result);

        // Verify config->set_item was called twice: once for site_id, once for site_pages
        $this->assertCount(2, $configMock->setItemCalls);
        $this->assertEquals('site_id', $configMock->setItemCalls[0]['key']);
        $this->assertEquals(2, $configMock->setItemCalls[0]['value']);
        $this->assertEquals('site_pages', $configMock->setItemCalls[1]['key']);
        $this->assertEquals([], $configMock->setItemCalls[1]['value']); // empty array from mock

        // Verify config->get_cached_site_prefs was called twice: once for current site_id (null), once for new site_id (2)
        $this->assertCount(2, $configMock->getCachedSitePrefsCalls);
        $this->assertEquals(null, $configMock->getCachedSitePrefsCalls[0]); // current site_id
        $this->assertEquals(2, $configMock->getCachedSitePrefsCalls[1]); // new site_id
    }

    public function testSwitchSiteHandlesSiteIdZero()
    {
        // Setup config mock
        $configMock = new class {
            public $setItemCalls = [];
            public $getCachedSitePrefsCalls = [];

            public function set_item($key, $value) {
                $this->setItemCalls[] = ['key' => $key, 'value' => $value];
            }

            public function get_cached_site_prefs($site_id) {
                $this->getCachedSitePrefsCalls[] = $site_id;
                return ['site_name' => 'Default Site'];
            }
        };

        $this->setMock('config', $configMock);

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('switch_site');
        \TestReflectionHelper::makeMethodAccessible($method);

        // Call with site_id = 0
        $result = $method->invoke($this->channelFormLib, 0);

        // Verify method returned successfully
        $this->assertNull($result);

        // Verify config methods were called with site_id = 0
        $this->assertCount(1, $configMock->setItemCalls);
        $this->assertEquals(0, $configMock->setItemCalls[0]['value']);
        $this->assertCount(1, $configMock->getCachedSitePrefsCalls);
        $this->assertEquals(0, $configMock->getCachedSitePrefsCalls[0]);
    }

    public function testSwitchSiteHandlesLargeSiteId()
    {
        // Setup config mock
        $configMock = new class {
            public $setItemCalls = [];
            public $getCachedSitePrefsCalls = [];

            public function set_item($key, $value) {
                $this->setItemCalls[] = ['key' => $key, 'value' => $value];
            }

            public function get_cached_site_prefs($site_id) {
                $this->getCachedSitePrefsCalls[] = $site_id;
                return ['site_name' => 'Large Site ID'];
            }
        };

        $this->setMock('config', $configMock);

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('switch_site');
        \TestReflectionHelper::makeMethodAccessible($method);

        // Call with large site_id
        $largeSiteId = 999999;
        $result = $method->invoke($this->channelFormLib, $largeSiteId);

        // Verify method returned successfully
        $this->assertNull($result);

        // Verify config methods were called with large site_id
        $this->assertCount(1, $configMock->setItemCalls);
        $this->assertEquals($largeSiteId, $configMock->setItemCalls[0]['value']);
        $this->assertCount(1, $configMock->getCachedSitePrefsCalls);
        $this->assertEquals($largeSiteId, $configMock->getCachedSitePrefsCalls[0]);
    }

    public function testSwitchSiteHandlesNegativeSiteId()
    {
        // Setup config mock
        $configMock = new class {
            public $setItemCalls = [];
            public $getCachedSitePrefsCalls = [];

            public function set_item($key, $value) {
                $this->setItemCalls[] = ['key' => $key, 'value' => $value];
            }

            public function get_cached_site_prefs($site_id) {
                $this->getCachedSitePrefsCalls[] = $site_id;
                return ['site_name' => 'Negative Site ID'];
            }
        };

        $this->setMock('config', $configMock);

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('switch_site');
        \TestReflectionHelper::makeMethodAccessible($method);

        // Call with negative site_id
        $result = $method->invoke($this->channelFormLib, -1);

        // Verify method returned successfully
        $this->assertNull($result);

        // Verify config methods were called with negative site_id
        $this->assertCount(1, $configMock->setItemCalls);
        $this->assertEquals(-1, $configMock->setItemCalls[0]['value']);
        $this->assertCount(1, $configMock->getCachedSitePrefsCalls);
        $this->assertEquals(-1, $configMock->getCachedSitePrefsCalls[0]);
    }

    public function testSwitchSiteCallsMethodsInCorrectOrder()
    {
        // Setup config mock to track call order
        $callOrder = [];
        $configMock = new class($callOrder) {
            public $callOrder;

            public function __construct(&$callOrder) {
                $this->callOrder = &$callOrder;
            }

            public function set_item($key, $value) {
                $this->callOrder[] = 'set_item';
            }

            public function get_cached_site_prefs($site_id) {
                $this->callOrder[] = 'get_cached_site_prefs';
                return ['site_name' => 'Order Test Site'];
            }
        };

        $this->setMock('config', $configMock);

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('switch_site');
        \TestReflectionHelper::makeMethodAccessible($method);

        // Call with site_id = 5
        $result = $method->invoke($this->channelFormLib, 5);

        // Verify method returned successfully
        $this->assertNull($result);

        // Verify methods were called in correct order
        $this->assertEquals(['set_item', 'get_cached_site_prefs'], $callOrder);
    }

    public function testSwitchSiteHandlesNullConfigObject()
    {
        // Setup config mock that returns null from get_cached_site_prefs
        $configMock = new class {
            public $setItemCalls = [];
            public $getCachedSitePrefsCalls = [];

            public function set_item($key, $value) {
                $this->setItemCalls[] = ['key' => $key, 'value' => $value];
            }

            public function get_cached_site_prefs($site_id) {
                $this->getCachedSitePrefsCalls[] = $site_id;
                return null; // Simulate no cached preferences
            }
        };

        $this->setMock('config', $configMock);

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('switch_site');
        \TestReflectionHelper::makeMethodAccessible($method);

        // Call with site_id = 3
        $result = $method->invoke($this->channelFormLib, 3);

        // Verify method returned successfully even with null return
        $this->assertNull($result);

        // Verify both methods were still called
        $this->assertCount(1, $configMock->setItemCalls);
        $this->assertCount(1, $configMock->getCachedSitePrefsCalls);
    }

    public function testSwitchSiteHandlesStringSiteId()
    {
        // Setup config mock
        $configMock = new class {
            public $setItemCalls = [];
            public $getCachedSitePrefsCalls = [];

            public function set_item($key, $value) {
                $this->setItemCalls[] = ['key' => $key, 'value' => $value];
            }

            public function get_cached_site_prefs($site_id) {
                $this->getCachedSitePrefsCalls[] = $site_id;
                return ['site_name' => 'String Site ID'];
            }
        };

        $this->setMock('config', $configMock);

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('switch_site');
        \TestReflectionHelper::makeMethodAccessible($method);

        // Call with string site_id (PHP will handle type juggling)
        $result = $method->invoke($this->channelFormLib, '7');

        // Verify method returned successfully
        $this->assertNull($result);

        // Verify config methods were called (PHP may convert string to int)
        $this->assertCount(1, $configMock->setItemCalls);
        $this->assertCount(1, $configMock->getCachedSitePrefsCalls);
    }

    public function testSwitchSiteMultipleCallsUpdatesConfiguration()
    {
        // Setup config mock to track all calls
        $configMock = new class {
            public $setItemCalls = [];
            public $getCachedSitePrefsCalls = [];

            public function set_item($key, $value) {
                $this->setItemCalls[] = ['key' => $key, 'value' => $value];
            }

            public function get_cached_site_prefs($site_id) {
                $this->getCachedSitePrefsCalls[] = $site_id;
                return ['site_name' => "Site {$site_id}"];
            }
        };

        $this->setMock('config', $configMock);

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('switch_site');
        \TestReflectionHelper::makeMethodAccessible($method);

        // Call multiple times with different site_ids
        $result1 = $method->invoke($this->channelFormLib, 1);
        $result2 = $method->invoke($this->channelFormLib, 2);
        $result3 = $method->invoke($this->channelFormLib, 1); // Back to site 1

        // Verify all calls returned successfully
        $this->assertNull($result1);
        $this->assertNull($result2);
        $this->assertNull($result3);

        // Verify all config method calls were tracked
        $this->assertCount(3, $configMock->setItemCalls);
        $this->assertCount(3, $configMock->getCachedSitePrefsCalls);

        // Verify the site_id values were set correctly
        $this->assertEquals(1, $configMock->setItemCalls[0]['value']);
        $this->assertEquals(2, $configMock->setItemCalls[1]['value']);
        $this->assertEquals(1, $configMock->setItemCalls[2]['value']);

        // Verify get_cached_site_prefs was called with correct site_ids
        $this->assertEquals(1, $configMock->getCachedSitePrefsCalls[0]);
        $this->assertEquals(2, $configMock->getCachedSitePrefsCalls[1]);
        $this->assertEquals(1, $configMock->getCachedSitePrefsCalls[2]);
    }

    public function testSwitchSiteHandlesBooleanSiteId()
    {
        // Setup config mock
        $configMock = new class {
            public $setItemCalls = [];
            public $getCachedSitePrefsCalls = [];

            public function set_item($key, $value) {
                $this->setItemCalls[] = ['key' => $key, 'value' => $value];
            }

            public function get_cached_site_prefs($site_id) {
                $this->getCachedSitePrefsCalls[] = $site_id;
                return ['site_name' => 'Boolean Site ID'];
            }
        };

        $this->setMock('config', $configMock);

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('switch_site');
        \TestReflectionHelper::makeMethodAccessible($method);

        // Call with boolean true (PHP will convert to int 1)
        $result = $method->invoke($this->channelFormLib, true);

        // Verify method returned successfully
        $this->assertNull($result);

        // Verify config methods were called (PHP converts true to 1)
        $this->assertCount(1, $configMock->setItemCalls);
        $this->assertEquals(1, $configMock->setItemCalls[0]['value']);
        $this->assertCount(1, $configMock->getCachedSitePrefsCalls);
        $this->assertEquals(1, $configMock->getCachedSitePrefsCalls[0]);
    }

    public function testSwitchSiteHandlesNullConfigReference()
    {
        // Setup config as null
        $this->setMock('config', null);

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('switch_site');
        \TestReflectionHelper::makeMethodAccessible($method);

        // Should throw an error when trying to access config methods
        $this->expectException(\Error::class);
        $method->invoke($this->channelFormLib, 1);
    }

    public function testSwitchSiteHandlesConfigSetItemException()
    {
        // Setup config mock that throws exception in set_item
        $configMock = new class {
            public function set_item($key, $value) {
                throw new \Exception('Config write failed');
            }
            public function get_cached_site_prefs($site_id) {
                return ['site_name' => 'Test Site'];
            }
        };

        $this->setMock('config', $configMock);

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('switch_site');
        \TestReflectionHelper::makeMethodAccessible($method);

        // Should propagate the exception from set_item
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Config write failed');
        $method->invoke($this->channelFormLib, 1);
    }

    public function testSwitchSiteHandlesConfigGetCachedSitePrefsException()
    {
        // Setup config mock that throws exception in get_cached_site_prefs
        $configMock = new class {
            public $setItemCalls = [];
            public function set_item($key, $value) {
                $this->setItemCalls[] = ['key' => $key, 'value' => $value];
            }
            public function get_cached_site_prefs($site_id) {
                throw new \Exception('Cache read failed');
            }
        };

        $this->setMock('config', $configMock);

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('switch_site');
        \TestReflectionHelper::makeMethodAccessible($method);

        // Should propagate the exception from get_cached_site_prefs
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cache read failed');
        $method->invoke($this->channelFormLib, 1);
    }

    public function testSwitchSiteHandlesFloatSiteId()
    {
        // Setup config mock
        $configMock = new class {
            public $setItemCalls = [];
            public $getCachedSitePrefsCalls = [];

            public function set_item($key, $value) {
                $this->setItemCalls[] = ['key' => $key, 'value' => $value];
            }

            public function get_cached_site_prefs($site_id) {
                $this->getCachedSitePrefsCalls[] = $site_id;
                return ['site_name' => 'Float Site ID'];
            }
        };

        $this->setMock('config', $configMock);

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('switch_site');
        \TestReflectionHelper::makeMethodAccessible($method);

        // Call with float site_id
        $result = $method->invoke($this->channelFormLib, 2.7);

        // Verify method returned successfully
        $this->assertNull($result);

        // PHP keeps float as float, so should get 2.0 (or close to it)
        $this->assertCount(1, $configMock->setItemCalls);
        $this->assertEquals(2.7, $configMock->setItemCalls[0]['value']);
        $this->assertCount(1, $configMock->getCachedSitePrefsCalls);
        $this->assertEquals(2.7, $configMock->getCachedSitePrefsCalls[0]);
    }

    public function testSwitchSiteHandlesExtremelyLargeSiteId()
    {
        // Setup config mock
        $configMock = new class {
            public $setItemCalls = [];
            public $getCachedSitePrefsCalls = [];

            public function set_item($key, $value) {
                $this->setItemCalls[] = ['key' => $key, 'value' => $value];
            }

            public function get_cached_site_prefs($site_id) {
                $this->getCachedSitePrefsCalls[] = $site_id;
                return ['site_name' => 'Large Site ID'];
            }
        };

        $this->setMock('config', $configMock);

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('switch_site');
        \TestReflectionHelper::makeMethodAccessible($method);

        // Call with extremely large site_id (beyond normal range)
        $largeSiteId = 999999999999999;
        $result = $method->invoke($this->channelFormLib, $largeSiteId);

        // Verify method returned successfully
        $this->assertNull($result);

        // Verify config methods were called with large site_id
        $this->assertCount(1, $configMock->setItemCalls);
        $this->assertEquals($largeSiteId, $configMock->setItemCalls[0]['value']);
        $this->assertCount(1, $configMock->getCachedSitePrefsCalls);
        $this->assertEquals($largeSiteId, $configMock->getCachedSitePrefsCalls[0]);
    }

    public function testSwitchSiteHandlesConfigWithoutSetItemMethod()
    {
        // Setup config mock without set_item method
        $configMock = new class {
            public function get_cached_site_prefs($site_id) {
                return ['site_name' => 'Test Site'];
            }
            // Missing set_item method
        };

        $this->setMock('config', $configMock);

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('switch_site');
        \TestReflectionHelper::makeMethodAccessible($method);

        // Should throw an error when trying to call non-existent set_item method
        $this->expectException(\Error::class);
        $method->invoke($this->channelFormLib, 1);
    }

    public function testSwitchSiteHandlesConfigWithoutGetCachedSitePrefsMethod()
    {
        // Setup config mock without get_cached_site_prefs method
        $configMock = new class {
            public $setItemCalls = [];
            public function set_item($key, $value) {
                $this->setItemCalls[] = ['key' => $key, 'value' => $value];
            }
            // Missing get_cached_site_prefs method
        };

        $this->setMock('config', $configMock);

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('switch_site');
        \TestReflectionHelper::makeMethodAccessible($method);

        // Should throw an error when trying to call non-existent get_cached_site_prefs method
        $this->expectException(\Error::class);
        $method->invoke($this->channelFormLib, 1);
    }

    // Removed testSwitchSiteHandlesObjectSiteId as objects wouldn't realistically be passed as site_id

    public function testSwitchSiteHandlesResourceSiteId()
    {
        // Setup config mock
        $configMock = new class {
            public $setItemCalls = [];
            public $getCachedSitePrefsCalls = [];

            public function set_item($key, $value) {
                $this->setItemCalls[] = ['key' => $key, 'value' => $value];
            }

            public function get_cached_site_prefs($site_id) {
                $this->getCachedSitePrefsCalls[] = $site_id;
                return ['site_name' => 'Resource Site ID'];
            }
        };

        $this->setMock('config', $configMock);

        // Call the private method using reflection
        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('switch_site');
        \TestReflectionHelper::makeMethodAccessible($method);

        // Create a temporary file resource
        $tempFile = tmpfile();
        $resourceId = intval($tempFile);

        try {
            // Call with resource site_id (PHP will convert to int)
            $result = $method->invoke($this->channelFormLib, $tempFile);

            // Verify method returned successfully
            $this->assertNull($result);

            // Resources are passed as-is, so we should get the resource
            $this->assertCount(1, $configMock->setItemCalls);
            $this->assertIsResource($configMock->setItemCalls[0]['value']);
            $this->assertCount(1, $configMock->getCachedSitePrefsCalls);
            $this->assertIsResource($configMock->getCachedSitePrefsCalls[0]);
        } finally {
            // Clean up the temporary file
            fclose($tempFile);
        }
    }
}
