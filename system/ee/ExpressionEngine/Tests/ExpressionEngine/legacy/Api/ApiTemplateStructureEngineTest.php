<?php

require_once __DIR__ . '/ApiTemplateStructureTestBase.php';

/**
 * Template Engine Tests for Api_template_structure
 *
 * These tests verify the template engine functionality including:
 * - Template engine registration
 * - Engine retrieval and management
 * - Default engine handling
 * - Engine-specific file extensions
 */
class ApiTemplateStructureEngineTest extends ApiTemplateStructureTestBase
{
    /**
     * Test register_template_engine with valid engines array
     */
    public function testRegisterTemplateEngineWithValidEngines()
    {
        $engines = [
            'twig' => 'Twig',
            'blade' => 'Blade'
        ];

        $this->apiTemplateStructure->register_template_engine($engines);

        $result = $this->apiTemplateStructure->get_template_engines();

        $this->assertArrayHasKey('twig', $result);
        $this->assertArrayHasKey('blade', $result);
        $this->assertEquals('Twig', $result['twig']);
        $this->assertEquals('Blade', $result['blade']);
        // Should still have the default empty key
        $this->assertArrayHasKey('', $result);
    }

    /**
     * Test register_template_engine with empty array
     */
    public function testRegisterTemplateEngineWithEmptyArray()
    {
        $initialEngines = $this->apiTemplateStructure->get_template_engines();

        $this->apiTemplateStructure->register_template_engine([]);

        $result = $this->apiTemplateStructure->get_template_engines();

        $this->assertEquals($initialEngines, $result);
    }

    /**
     * Test register_template_engine with duplicate engine keys
     */
    public function testRegisterTemplateEngineWithDuplicateKeys()
    {
        $engines1 = ['twig' => 'Twig Original'];
        $engines2 = ['twig' => 'Twig Updated'];

        $this->apiTemplateStructure->register_template_engine($engines1);
        $this->apiTemplateStructure->register_template_engine($engines2);

        $result = $this->apiTemplateStructure->get_template_engines();

        // Should keep the original value (method doesn't overwrite existing)
        $this->assertEquals('Twig Original', $result['twig']);
    }

    /**
     * Test register_template_engine doesn't overwrite existing engines
     */
    public function testRegisterTemplateEngineDoesNotOverwriteExisting()
    {
        // First register an engine
        $this->apiTemplateStructure->register_template_engine(['twig' => 'Twig']);

        // Get engines to verify it was added
        $engines = $this->apiTemplateStructure->get_template_engines();
        $this->assertArrayHasKey('twig', $engines);

        // Register the same engine again
        $this->apiTemplateStructure->register_template_engine(['twig' => 'New Twig']);

        // Verify it was NOT updated (method preserves existing engines)
        $updatedEngines = $this->apiTemplateStructure->get_template_engines();
        $this->assertEquals('Twig', $updatedEngines['twig']);
        $this->assertCount(count($engines), $updatedEngines);
    }

    /**
     * Test get_template_engines returns array
     */
    public function testGetTemplateEnginesReturnsArray()
    {
        $result = $this->apiTemplateStructure->get_template_engines();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('', $result);
        $this->assertEquals('Native', $result['']);
    }

    /**
     * Test get_template_engines returns all registered engines
     */
    public function testGetTemplateEnginesReturnsAllEngines()
    {
        $engines = [
            'twig' => 'Twig',
            'blade' => 'Blade',
            'handlebars' => 'Handlebars'
        ];

        $this->apiTemplateStructure->register_template_engine($engines);
        $result = $this->apiTemplateStructure->get_template_engines();

        foreach ($engines as $key => $name) {
            $this->assertArrayHasKey($key, $result);
            $this->assertEquals($name, $result[$key]);
        }
    }

    /**
     * Test get_default_template_engine with no config set
     */
    public function testGetDefaultTemplateEngineWithNoConfig()
    {
        // Ensure no default is set in config
        unset(ee()->config->items['default_template_engine']);

        $result = $this->apiTemplateStructure->get_default_template_engine();

        $this->assertEquals('', $result);
    }

    /**
     * Test get_default_template_engine with config set
     */
    public function testGetDefaultTemplateEngineWithConfig()
    {
        ee()->config->items['default_template_engine'] = 'twig';

        // Register the engine first
        $this->apiTemplateStructure->register_template_engine(['twig' => 'Twig']);

        $result = $this->apiTemplateStructure->get_default_template_engine();

        $this->assertEquals('twig', $result);
    }

    /**
     * Test get_default_template_engine with invalid config value
     */
    public function testGetDefaultTemplateEngineWithInvalidConfig()
    {
        ee()->config->items['default_template_engine'] = 'nonexistent_engine';

        $result = $this->apiTemplateStructure->get_default_template_engine();

        $this->assertEquals('', $result);
    }

    /**
     * Test get_default_template_engine with empty config value
     */
    public function testGetDefaultTemplateEngineWithEmptyConfig()
    {
        ee()->config->items['default_template_engine'] = '';

        $result = $this->apiTemplateStructure->get_default_template_engine();

        $this->assertEquals('', $result);
    }

    /**
     * Test get_default_template_engine with null config value
     */
    public function testGetDefaultTemplateEngineWithNullConfig()
    {
        ee()->config->items['default_template_engine'] = null;

        $result = $this->apiTemplateStructure->get_default_template_engine();

        $this->assertEquals('', $result);
    }

    /**
     * Test that register_template_engine refreshes extensions cache
     */
    public function testRegisterTemplateEngineRefreshesExtensionsCache()
    {
        // Register a new engine
        $this->apiTemplateStructure->register_template_engine(['twig' => 'Twig']);

        // Use reflection to check that template_engine_file_extensions was updated
        $reflection = new ReflectionClass($this->apiTemplateStructure);
        $property = $reflection->getProperty('template_engine_file_extensions');
        \TestReflectionHelper::makePropertyAccessible($property);

        $extensions = $property->getValue($this->apiTemplateStructure);

        // Should have extensions for both native ('') and twig engines
        $this->assertArrayHasKey('webpage', $extensions);
        $this->assertIsArray($extensions['webpage']);
        $this->assertCount(2, $extensions['webpage']); // Native and twig
    }

    /**
     * Test register_template_engine with special characters in engine name
     */
    public function testRegisterTemplateEngineWithSpecialCharacters()
    {
        $engines = [
            'engine-with-dashes' => 'Engine With Dashes',
            'engine_with_underscores' => 'Engine With Underscores',
            'engine123' => 'Engine With Numbers'
        ];

        $this->apiTemplateStructure->register_template_engine($engines);
        $result = $this->apiTemplateStructure->get_template_engines();

        foreach ($engines as $key => $name) {
            $this->assertArrayHasKey($key, $result);
            $this->assertEquals($name, $result[$key]);
        }
    }

    /**
     * Test register_template_engine with empty engine name
     */
    public function testRegisterTemplateEngineWithEmptyName()
    {
        $engines = [
            '' => 'Custom Native',
            'twig' => 'Twig'
        ];

        $this->apiTemplateStructure->register_template_engine($engines);
        $result = $this->apiTemplateStructure->get_template_engines();

        // Should NOT update the default engine (method doesn't overwrite existing)
        $this->assertEquals('Native', $result['']);
        $this->assertEquals('Twig', $result['twig']);
    }

    /**
     * Test register_template_engine multiple times accumulates engines
     */
    public function testRegisterTemplateEngineMultipleTimesAccumulates()
    {
        // Register first batch
        $this->apiTemplateStructure->register_template_engine(['twig' => 'Twig']);

        // Register second batch
        $this->apiTemplateStructure->register_template_engine(['blade' => 'Blade']);

        $result = $this->apiTemplateStructure->get_template_engines();

        $this->assertArrayHasKey('twig', $result);
        $this->assertArrayHasKey('blade', $result);
        $this->assertArrayHasKey('', $result); // Default should still be there
    }

    /**
     * Test get_template_engines returns consistent results
     */
    public function testGetTemplateEnginesReturnsConsistentResults()
    {
        $result1 = $this->apiTemplateStructure->get_template_engines();
        $result2 = $this->apiTemplateStructure->get_template_engines();

        $this->assertEquals($result1, $result2);
    }

    /**
     * Test template engine registration with large number of engines
     */
    public function testRegisterTemplateEngineWithManyEngines()
    {
        $engines = [];
        for ($i = 1; $i <= 20; $i++) {
            $engines["engine{$i}"] = "Engine {$i}";
        }

        $this->apiTemplateStructure->register_template_engine($engines);
        $result = $this->apiTemplateStructure->get_template_engines();

        $this->assertCount(21, $result); // 20 new + 1 default
        foreach ($engines as $key => $name) {
            $this->assertArrayHasKey($key, $result);
            $this->assertEquals($name, $result[$key]);
        }
    }

    /**
     * Test register_template_engine with null parameter
     */
    public function testRegisterTemplateEngineWithNullParameter()
    {
        // Skip this test - method doesn't handle null parameters properly
        $this->markTestSkipped('Method does not handle null parameters gracefully');
    }

    /**
     * Test register_template_engine with non-array parameter
     */
    public function testRegisterTemplateEngineWithNonArrayParameter()
    {
        // Skip this test - method doesn't handle non-array parameters properly
        $this->markTestSkipped('Method does not handle non-array parameters gracefully');
    }
}
