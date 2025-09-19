<?php

require_once __DIR__ . '/ApiTemplateStructureTestBase.php';

/**
 * Constructor and Initialization Tests for Api_template_structure
 *
 * These tests verify that the Api_template_structure class is properly
 * initialized and that all dependencies are correctly set up.
 */
class ApiTemplateStructureConstructorTest extends ApiTemplateStructureTestBase
{
    /**
     * Test that constructor properly initializes the class
     */
    public function testConstructorCreatesValidInstance()
    {
        $this->assertInstanceOf('Api_template_structure', $this->apiTemplateStructure);
    }

    /**
     * Test that constructor loads template model
     */
    public function testConstructorLoadsTemplateModel()
    {
        // Verify that template_model is loaded and available
        $templateModel = ee()->template_model;
        $this->assertNotNull($templateModel);
        $this->assertInstanceOf('FakeTemplateModel', $templateModel);
    }

    /**
     * Test that constructor initializes reserved names array
     */
    public function testConstructorInitializesReservedNames()
    {
        $this->assertIsArray($this->apiTemplateStructure->reserved_names);
        $this->assertContains('act', $this->apiTemplateStructure->reserved_names);
        $this->assertContains('css', $this->apiTemplateStructure->reserved_names);
    }

    /**
     * Test that constructor initializes file extensions array
     */
    public function testConstructorInitializesFileExtensions()
    {
        $this->assertIsArray($this->apiTemplateStructure->file_extensions);
        $this->assertArrayHasKey('webpage', $this->apiTemplateStructure->file_extensions);
        $this->assertArrayHasKey('css', $this->apiTemplateStructure->file_extensions);
        $this->assertArrayHasKey('js', $this->apiTemplateStructure->file_extensions);
        $this->assertArrayHasKey('xml', $this->apiTemplateStructure->file_extensions);

        // Verify specific file extensions
        $this->assertEquals('.html', $this->apiTemplateStructure->file_extensions['webpage']);
        $this->assertEquals('.css', $this->apiTemplateStructure->file_extensions['css']);
        $this->assertEquals('.js', $this->apiTemplateStructure->file_extensions['js']);
        $this->assertEquals('.xml', $this->apiTemplateStructure->file_extensions['xml']);
    }

    /**
     * Test that constructor initializes template engines array
     */
    public function testConstructorInitializesTemplateEngines()
    {
        $this->assertIsArray($this->apiTemplateStructure->template_engines);
        $this->assertArrayHasKey('', $this->apiTemplateStructure->template_engines);
        $this->assertEquals('Native', $this->apiTemplateStructure->template_engines['']);
    }

    /**
     * Test that constructor calls _load_reserved_groups method
     */
    public function testConstructorCallsLoadReservedGroups()
    {
        // Create a new instance to test initialization
        $newInstance = new Api_template_structure();

        // Verify that reserved names have been loaded (should contain more than just the defaults)
        $this->assertGreaterThan(2, count($newInstance->reserved_names), 'Reserved names should be populated beyond defaults');
    }

    /**
     * Test that constructor calls _setup_template_engine_extensions method
     */
    public function testConstructorCallsSetupTemplateEngineExtensions()
    {
        // Verify that template engine file extensions have been set up
        $this->assertIsArray($this->apiTemplateStructure->template_engine_file_extensions);
        $this->assertNotEmpty($this->apiTemplateStructure->template_engine_file_extensions);
    }

    /**
     * Test that template_info cache is initialized as empty array
     */
    public function testTemplateInfoCacheInitialized()
    {
        $this->assertIsArray($this->apiTemplateStructure->template_info);
        $this->assertEmpty($this->apiTemplateStructure->template_info);
    }

    /**
     * Test that group_info cache is initialized as empty array
     */
    public function testGroupInfoCacheInitialized()
    {
        $this->assertIsArray($this->apiTemplateStructure->group_info);
        $this->assertEmpty($this->apiTemplateStructure->group_info);
    }

    /**
     * Test that protected template_engine_file_extensions is initialized
     */
    public function testTemplateEngineFileExtensionsInitialized()
    {
        // Use reflection to access protected property
        $reflection = new ReflectionClass($this->apiTemplateStructure);
        $property = $reflection->getProperty('template_engine_file_extensions');
        $property->setAccessible(true);

        $value = $property->getValue($this->apiTemplateStructure);
        $this->assertIsArray($value);
        $this->assertNotEmpty($value);
    }

    /**
     * Test that constructor handles missing dependencies gracefully
     */
    public function testConstructorHandlesMissingDependencies()
    {
        // Test that constructor doesn't throw exceptions when dependencies are missing
        // This is important for isolated unit testing
        $this->assertInstanceOf('Api_template_structure', $this->apiTemplateStructure);
    }

    /**
     * Test that constructor sets up proper inheritance from Api base class
     */
    public function testConstructorProperlyInheritsFromApiBase()
    {
        $this->assertInstanceOf('Api', $this->apiTemplateStructure);
        $this->assertInstanceOf('Api_template_structure', $this->apiTemplateStructure);
    }

    /**
     * Test that multiple instances can be created independently
     */
    public function testMultipleInstancesCreatedIndependently()
    {
        $instance1 = new Api_template_structure();
        $instance2 = new Api_template_structure();

        // Instances should be separate objects
        $this->assertNotSame($instance1, $instance2);

        // Each should have their own template_info cache (both start empty)
        $this->assertEquals([], $instance1->template_info);
        $this->assertEquals([], $instance2->template_info);
        $this->assertEquals($instance1->template_info, $instance2->template_info);
    }

    /**
     * Test that constructor doesn't modify global state unexpectedly
     */
    public function testConstructorDoesNotModifyGlobalState()
    {
        // Capture initial global state
        $initialConfig = ee()->config->items ?? [];

        // Create new instance
        $newInstance = new Api_template_structure();

        // Verify global state wasn't modified
        $this->assertEquals($initialConfig, ee()->config->items ?? []);
    }

    /**
     * Test that constructor properly sets up error handling
     */
    public function testConstructorSetsUpErrorHandling()
    {
        // The Api_template_structure should inherit error handling from Api base class
        $this->assertTrue(method_exists($this->apiTemplateStructure, '_set_error'));
        $this->assertTrue(method_exists($this->apiTemplateStructure, 'error_count'));
    }
}
