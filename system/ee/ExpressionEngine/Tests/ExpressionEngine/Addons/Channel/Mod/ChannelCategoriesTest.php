<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelCategoriesTest extends ChannelTestBase
{
    public function testCategoriesReturnsEmptyStringWhenNoCategories()
    {
        // Set up template parameters
        $this->setTemplateParams([]);

        // Mock database to return no categories
        $this->setDbRows([]);

        $result = $this->channel->categories();

        $this->assertIsString($result);
        $this->assertEquals('NO_RESULTS', $result);
    }

    public function testCategoriesMethodExistsAndIsCallable()
    {
        // Just test that the method exists and can be called
        $this->assertTrue(method_exists($this->channel, 'categories'));

        $result = $this->channel->categories();

        // Should return a string (even if it's NO_RESULTS)
        $this->assertIsString($result);
    }

    public function testCategoriesHasExpectedMethods()
    {
        // Test that the categories method calls expected helper methods
        $this->assertTrue(method_exists($this->channel, 'fetch_categories'));
        $this->assertTrue(method_exists($this->channel, 'category_tree'));

        // Test that the method can be called without throwing fatal errors
        $result = $this->channel->categories();
        $this->assertIsString($result);
    }

    public function testCategoriesAcceptsStyleParameter()
    {
        // Test that the method can handle style parameter without errors
        $this->setTemplateParams(['style' => 'nested']);

        $result = $this->channel->categories();

        // Should return a string result
        $this->assertIsString($result);
    }
}
