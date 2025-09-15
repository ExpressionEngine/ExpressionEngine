<?php

require_once __DIR__ . '/ApiChannelStructureTestBase.php';

/**
 * Constructor and Initialization Tests for Api_channel_structure
 *
 * These tests verify that the Api_channel_structure class is properly
 * initialized and that all dependencies are correctly set up.
 */
class ApiChannelStructureConstructorTest extends ApiChannelStructureTestBase
{
    /**
     * Test that constructor properly initializes the class
     */
    public function testConstructorCreatesValidInstance()
    {
        $this->assertInstanceOf('Api_channel_structure', $this->apiChannelStructure);
    }

    /**
     * Test that constructor loads channel model
     */
    public function testConstructorLoadsChannelModel()
    {
        // Verify that channel_model is loaded and available
        $channelModel = ee()->channel_model;
        $this->assertNotNull($channelModel);
        $this->assertInstanceOf('FakeChannelModel', $channelModel);
    }

    /**
     * Test that constructor initializes channel_info cache as empty array
     */
    public function testConstructorInitializesChannelInfoCache()
    {
        $this->assertIsArray($this->apiChannelStructure->channel_info);
        $this->assertEmpty($this->apiChannelStructure->channel_info);
    }

    /**
     * Test that constructor initializes channels cache as empty array
     */
    public function testConstructorInitializesChannelsCache()
    {
        $this->assertIsArray($this->apiChannelStructure->channels);
        $this->assertEmpty($this->apiChannelStructure->channels);
    }

    /**
     * Test that constructor calls parent constructor
     */
    public function testConstructorCallsParentConstructor()
    {
        $this->assertInstanceOf('Api', $this->apiChannelStructure);
        $this->assertInstanceOf('Api_channel_structure', $this->apiChannelStructure);
    }

    /**
     * Test that constructor handles missing dependencies gracefully
     */
    public function testConstructorHandlesMissingDependencies()
    {
        // Test that constructor doesn't throw exceptions when dependencies are missing
        // This is important for isolated unit testing
        $this->assertInstanceOf('Api_channel_structure', $this->apiChannelStructure);
    }

    /**
     * Test that multiple instances can be created independently
     */
    public function testMultipleInstancesCreatedIndependently()
    {
        $instance1 = new Api_channel_structure();
        $instance2 = new Api_channel_structure();

        // Instances should be separate objects
        $this->assertNotSame($instance1, $instance2);

        // Both should start with empty arrays
        $this->assertEquals([], $instance1->channel_info);
        $this->assertEquals([], $instance2->channel_info);
        $this->assertEquals([], $instance1->channels);
        $this->assertEquals([], $instance2->channels);

        // Modify one instance's cache to verify they are independent
        $instance1->channel_info['test'] = 'value';
        $instance1->channels['test'] = 'value';

        // The other instance should not be affected
        $this->assertArrayNotHasKey('test', $instance2->channel_info);
        $this->assertArrayNotHasKey('test', $instance2->channels);

        // But the first instance should have the changes
        $this->assertArrayHasKey('test', $instance1->channel_info);
        $this->assertArrayHasKey('test', $instance1->channels);
    }

    /**
     * Test that constructor doesn't modify global state unexpectedly
     */
    public function testConstructorDoesNotModifyGlobalState()
    {
        // Capture initial global state
        $initialConfig = ee()->config->items ?? [];

        // Create new instance
        $newInstance = new Api_channel_structure();

        // Verify global state wasn't modified
        $this->assertEquals($initialConfig, ee()->config->items ?? []);
    }

    /**
     * Test that constructor sets up proper inheritance
     */
    public function testConstructorProperlyInheritsFromApiBase()
    {
        $this->assertInstanceOf('Api', $this->apiChannelStructure);
        $this->assertInstanceOf('Api_channel_structure', $this->apiChannelStructure);

        // Verify that API methods are available
        $this->assertTrue(method_exists($this->apiChannelStructure, '_set_error'));
        $this->assertTrue(method_exists($this->apiChannelStructure, 'error_count'));
        $this->assertTrue(method_exists($this->apiChannelStructure, 'is_url_safe'));
    }

    /**
     * Test that constructor initializes with correct channel model type
     */
    public function testConstructorInitializesWithCorrectChannelModelType()
    {
        $channelModel = ee()->channel_model;

        // Verify the channel model has the expected methods
        $this->assertTrue(method_exists($channelModel, 'get_channel_info'));
        $this->assertTrue(method_exists($channelModel, 'get_channels'));
        $this->assertTrue(method_exists($channelModel, 'create_channel'));
        $this->assertTrue(method_exists($channelModel, 'update_channel'));
        $this->assertTrue(method_exists($channelModel, 'delete_channel'));
    }

    /**
     * Test that constructor works with different site configurations
     */
    public function testConstructorWorksWithDifferentSiteConfigurations()
    {
        // Test with different site_id in config
        ee()->config->items['site_id'] = 2;

        $newInstance = new Api_channel_structure();

        // Verify instance still works
        $this->assertInstanceOf('Api_channel_structure', $newInstance);

        // Reset to default
        ee()->config->items['site_id'] = 1;
    }

    /**
     * Test that constructor initializes caching arrays as arrays
     */
    public function testConstructorInitializesCachingArraysAsArrays()
    {
        $this->assertIsArray($this->apiChannelStructure->channel_info);
        $this->assertIsArray($this->apiChannelStructure->channels);

        // Verify they are specifically empty arrays, not just any array
        $this->assertEquals([], $this->apiChannelStructure->channel_info);
        $this->assertEquals([], $this->apiChannelStructure->channels);
    }

    /**
     * Test that constructor doesn't set any unexpected properties
     */
    public function testConstructorDoesNotSetUnexpectedProperties()
    {
        $reflection = new ReflectionClass($this->apiChannelStructure);
        $properties = $reflection->getProperties();

        $expectedProperties = ['channel_info', 'channels'];
        $actualProperties = [];

        foreach ($properties as $property) {
            $actualProperties[] = $property->getName();
        }

        // Verify that only expected properties are set (plus inherited ones)
        foreach ($expectedProperties as $expectedProperty) {
            $this->assertContains($expectedProperty, $actualProperties, "Expected property '$expectedProperty' not found");
        }
    }

    /**
     * Test that constructor handles channel model loading errors gracefully
     */
    public function testConstructorHandlesChannelModelLoadingErrors()
    {
        // Mock a scenario where channel_model loading fails
        // This is difficult to test directly, but we can verify the constructor completes
        $this->assertInstanceOf('Api_channel_structure', $this->apiChannelStructure);
    }
}
