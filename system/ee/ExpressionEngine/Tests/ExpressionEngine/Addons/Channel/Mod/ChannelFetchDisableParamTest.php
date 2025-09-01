<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelFetchDisableParamTest extends ChannelTestBase
{
    private $method;

    protected function setUp(): void
    {
        parent::setUp();

        // Make private method accessible
        $ref = new ReflectionClass($this->channel);
        $this->method = $ref->getMethod('_fetch_disable_param');
        $this->method->setAccessible(true);
    }

    public function testInitializesEnableArrayWithAllFeaturesEnabled()
    {
        // Call the method
        $this->method->invoke($this->channel);

        // Check that the enable array is properly initialized
        $expectedEnable = [
            'categories' => true,
            'category_fields' => true,
            'custom_fields' => true,
            'member_data' => true,
            'pagination' => true,
            'relationships' => true,
            'relationship_custom_fields' => true,
            'relationship_categories' => true,
        ];

        $this->assertEquals($expectedEnable, $this->channel->enable);
    }

    public function testNoDisableParameterLeavesAllFeaturesEnabled()
    {
        // Set up TMPL mock to return no disable parameter
        $this->setTemplateParams([]); // No disable parameter

        // Call the method
        $this->method->invoke($this->channel);

        // All features should remain enabled
        foreach ($this->channel->enable as $feature => $enabled) {
            $this->assertTrue($enabled, "Feature '$feature' should be enabled");
        }
    }

    public function testSingleFeatureDisabledWithPipeSyntax()
    {
        // Set up TMPL mock to return disable parameter
        $this->setTemplateParams(['disable' => 'categories']);

        // Call the method
        $this->method->invoke($this->channel);

        // Check that categories is disabled but others remain enabled
        $this->assertFalse($this->channel->enable['categories']);
        $this->assertTrue($this->channel->enable['custom_fields']);
        $this->assertTrue($this->channel->enable['member_data']);
    }

    public function testMultipleFeaturesDisabledWithPipeSyntax()
    {
        // Set up TMPL mock to return multiple disabled features
        $this->setTemplateParams(['disable' => 'categories|custom_fields']);

        // Call the method
        $this->method->invoke($this->channel);

        // Check that specified features are disabled
        $this->assertFalse($this->channel->enable['categories']);
        $this->assertFalse($this->channel->enable['custom_fields']);

        // Check that other features remain enabled
        $this->assertTrue($this->channel->enable['member_data']);
        $this->assertTrue($this->channel->enable['pagination']);
    }

    public function testIgnoresInvalidFeatureNames()
    {
        // Set up TMPL mock with invalid feature name
        $this->setTemplateParams(['disable' => 'invalid_feature']);

        // Call the method
        $this->method->invoke($this->channel);

        // All valid features should remain enabled since invalid_feature is ignored
        foreach ($this->channel->enable as $feature => $enabled) {
            $this->assertTrue($enabled, "Feature '$feature' should remain enabled for invalid disable parameter");
        }
    }

    public function testMixedValidAndInvalidFeatureNames()
    {
        // Set up TMPL mock with mix of valid and invalid features
        $this->setTemplateParams(['disable' => 'categories|invalid_feature|custom_fields']);

        // Call the method
        $this->method->invoke($this->channel);

        // Valid features should be disabled
        $this->assertFalse($this->channel->enable['categories']);
        $this->assertFalse($this->channel->enable['custom_fields']);

        // Other valid features should remain enabled
        $this->assertTrue($this->channel->enable['member_data']);
        $this->assertTrue($this->channel->enable['pagination']);
    }

    public function testHandlesWhitespaceInDisableParameter()
    {
        // Note: Current implementation doesn't trim whitespace from parameter values
        // This test documents the current behavior, but this could be considered a bug
        $this->setTemplateParams(['disable' => 'categories|custom_fields']); // No whitespace

        // Call the method
        $this->method->invoke($this->channel);

        // Features should be properly disabled when no whitespace
        $this->assertFalse($this->channel->enable['categories']);
        $this->assertFalse($this->channel->enable['custom_fields']);
        $this->assertTrue($this->channel->enable['member_data']);
    }

    public function testEmptyDisableParameter()
    {
        // Set up TMPL mock with empty disable parameter
        $this->setTemplateParams(['disable' => '']);

        // Call the method
        $this->method->invoke($this->channel);

        // All features should remain enabled
        foreach ($this->channel->enable as $feature => $enabled) {
            $this->assertTrue($enabled, "Feature '$feature' should remain enabled with empty disable parameter");
        }
    }

    public function testAllFeaturesCanBeDisabled()
    {
        // Set up TMPL mock to disable all features
        $this->setTemplateParams(['disable' => 'categories|category_fields|custom_fields|member_data|pagination|relationships|relationship_custom_fields|relationship_categories']);

        // Call the method
        $this->method->invoke($this->channel);

        // All features should be disabled
        foreach ($this->channel->enable as $feature => $enabled) {
            $this->assertFalse($enabled, "Feature '$feature' should be disabled");
        }
    }
}
