<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

class EE_TemplateSwapVarSingleTest extends EE_TemplateTestBase
{
    public function testSwapVarSingleMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'swap_var_single'));
        $this->assertTrue(is_callable([$this->template, 'swap_var_single']));
    }

    public function testSwapVarSingleBasicReplacement()
    {
        $source = 'Hello {name}, welcome!';
        $result = $this->template->swap_var_single('name', 'World', $source);

        $this->assertEquals('Hello World, welcome!', $result);
    }

    public function testSwapVarSingleMultipleOccurrences()
    {
        $source = 'Hello {name}, {name} again!';
        $result = $this->template->swap_var_single('name', 'World', $source);

        $this->assertEquals('Hello World, World again!', $result);
    }

    public function testSwapVarSingleNoMatches()
    {
        $source = 'Hello world, welcome!';
        $result = $this->template->swap_var_single('name', 'World', $source);

        $this->assertEquals('Hello world, welcome!', $result);
    }

    public function testSwapVarSingleEmptyReplacement()
    {
        $source = 'Hello {name}, welcome!';
        $result = $this->template->swap_var_single('name', '', $source);

        $this->assertEquals('Hello , welcome!', $result);
    }

    public function testSwapVarSingleNullReplacement()
    {
        $source = 'Hello {name}, welcome!';
        $result = $this->template->swap_var_single('name', null, $source);

        $this->assertEquals('Hello , welcome!', $result);
    }

    public function testSwapVarSingleSpecialCharacters()
    {
        $source = 'Price: {amount}';
        $result = $this->template->swap_var_single('amount', '$99.99', $source);

        $this->assertEquals('Price: $99.99', $result);
    }

    public function testSwapVarSingleLargeStrings()
    {
        // Test performance with large strings
        $largeString = str_repeat('content ', 10000) . '{var}' . str_repeat(' more', 10000);
        $result = $this->template->swap_var_single('var', 'REPLACEMENT', $largeString);

        // Should handle large strings without memory issues
        $this->assertStringContainsString('REPLACEMENT', $result);
        $this->assertStringNotContainsString('{var}', $result);
    }

    public function testSwapVarSingleBinaryData()
    {
        // Test with null bytes and special characters
        $source = "content{var}more";
        $replacement = "data\x00with\x01null\x02bytes 🔥👨‍💻🚀";
        $result = $this->template->swap_var_single('var', $replacement, $source);

        $this->assertStringContainsString($replacement, $result);
        $this->assertStringNotContainsString('{var}', $result);
    }

    public function testSwapVarSingleVariableBoundaries()
    {
        // Test variables at string boundaries
        $result = $this->template->swap_var_single('var', 'replacement', '{var}content');
        $this->assertEquals('replacementcontent', $result);

        $result = $this->template->swap_var_single('var', 'replacement', 'content{var}');
        $this->assertEquals('contentreplacement', $result);

        $result = $this->template->swap_var_single('var', 'replacement', '{var}{var}');
        $this->assertEquals('replacementreplacement', $result);
    }

    public function testSwapVarSingleDelimiterConflicts()
    {
        // Test content containing LD/RD characters
        $source = 'content{var}more{notvar}content';
        $result = $this->template->swap_var_single('var', 'replacement', $source);

        $this->assertEquals('contentreplacementmore{notvar}content', $result);
    }

    public function testSwapVarSingleMemoryLimitStress()
    {
        // Test with large input approaching memory limits
        // Use a reasonable size that won't actually exhaust memory
        $size = 1024 * 1024; // 1MB of content
        $largeString = str_repeat('x', $size) . '{marker}' . str_repeat('y', $size);

        $result = $this->template->swap_var_single('marker', 'REPLACEMENT', $largeString);

        // Should handle large strings without memory issues
        $this->assertStringContainsString('REPLACEMENT', $result);
        $this->assertStringNotContainsString('{marker}', $result);
        $this->assertEquals(strlen($largeString) - 8 + 11, strlen($result)); // {marker} (8 chars) -> REPLACEMENT (11 chars)
    }

    public function testSwapVarSingleUnicodeHandling()
    {
        // Test Unicode characters and different encodings
        $unicodeString = 'café{var}🚀'; // UTF-8 characters
        $replacement = 'naïve_ñuño'; // More Unicode

        $result = $this->template->swap_var_single('var', $replacement, $unicodeString);

        // Should handle Unicode correctly
        $this->assertEquals('café' . $replacement . '🚀', $result);
        $this->assertStringNotContainsString('{var}', $result);
    }

    public function testSwapVarSingleMethodSignature()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, 'swap_var_single');
        $this->assertTrue($reflection->isPublic());

        $parameters = $reflection->getParameters();
        $this->assertCount(3, $parameters);

        $this->assertEquals('search', $parameters[0]->getName());
        $this->assertEquals('replace', $parameters[1]->getName());
        $this->assertEquals('source', $parameters[2]->getName());
    }
}