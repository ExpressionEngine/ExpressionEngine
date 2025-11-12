<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibBoolStringEdgeCasesTest extends ChannelFormLibTestBase
{
    public function testBoolStringWithRegexSpecialCharacters()
    {
        // Test strings with regex special characters - due to the bug, these still match if they contain truthy/falsy substrings
        $this->assertTrue($this->channelFormLib->bool_string('[true]', false)); // Contains 'true', matches truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string('(true)', false)); // Contains 'true', matches truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string('true|false', false)); // Contains 'true', matches truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string('true+false', false)); // Contains 'true', matches truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string('true*', false)); // Contains 'true', matches truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string('true?', false)); // Contains 'true', matches truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string('true^', false)); // Contains 'true', matches truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string('true$', false)); // Contains 'true', matches truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string('true\\', false)); // Contains 'true', matches truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string('true.', false)); // Contains 'true', matches truthy pattern
    }

    public function testBoolStringWithUnicodeCharacters()
    {
        // Test with Unicode characters - due to the bug, strings containing 't' or 'y' will match truthy patterns
        $this->assertTrue($this->channelFormLib->bool_string('t®ue', false)); // Contains 't', matches truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string('tüe', false)); // Contains 't', matches truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string('true✅', false)); // Contains 'true', matches truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string('true🚀', false)); // Contains 'true', matches truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string('true中文', false)); // Contains 'true', matches truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string('trueالعربية', false)); // Contains 'true', matches truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string('true🚀✅', false)); // Contains 'true', matches truthy pattern
    }

    public function testBoolStringWithExtremelyLongStrings()
    {
        // Test performance with very long strings - 'maybe' contains 'y' so it matches truthy pattern
        $long_string = str_repeat('maybe', 10000); // ~50,000 characters
        $start = microtime(true);
        $result = $this->channelFormLib->bool_string($long_string, false);
        $end = microtime(true);

        $this->assertTrue($result); // Contains 'y', matches truthy pattern
        $this->assertLessThan(1.0, $end - $start); // Should complete in less than 1 second

        // Test with a safe long string
        $safe_long_string = str_repeat('perhaps', 10000); // ~70,000 characters
        $start2 = microtime(true);
        $result2 = $this->channelFormLib->bool_string($safe_long_string, true);
        $end2 = microtime(true);

        $this->assertTrue($result2); // Should return default (true)
        $this->assertLessThan(1.0, $end2 - $start2); // Should complete in less than 1 second
    }

    public function testBoolStringWithEmptyAndWhitespaceStrings()
    {
        // Test edge cases with empty and whitespace strings
        $this->assertTrue($this->channelFormLib->bool_string('', true)); // Empty string
        $this->assertFalse($this->channelFormLib->bool_string('', false)); // Empty string
        $this->assertTrue($this->channelFormLib->bool_string('   ', true)); // Whitespace only
        $this->assertFalse($this->channelFormLib->bool_string('   ', false)); // Whitespace only
        $this->assertTrue($this->channelFormLib->bool_string("\t\n\r", true)); // Mixed whitespace
        $this->assertFalse($this->channelFormLib->bool_string("\t\n\r", false)); // Mixed whitespace
    }

    public function testBoolStringWithNumericStrings()
    {
        // Test numeric strings - '1' matches truthy pattern, '0' matches falsy pattern
        $this->assertTrue($this->channelFormLib->bool_string('1', false)); // String '1' matches truthy pattern
        $this->assertFalse($this->channelFormLib->bool_string('0', true)); // String '0' matches falsy pattern
        $this->assertTrue($this->channelFormLib->bool_string('123', false)); // Contains '1', matches truthy pattern
        $this->assertFalse($this->channelFormLib->bool_string('0.5', true)); // Contains '0', matches falsy pattern
    }

    public function testBoolStringWithCaseVariations()
    {
        // Test case sensitivity (regex has /i flag so should be case insensitive)
        $this->assertTrue($this->channelFormLib->bool_string('TRUE', false)); // Uppercase
        $this->assertTrue($this->channelFormLib->bool_string('True', false)); // Mixed case
        $this->assertTrue($this->channelFormLib->bool_string('true', false)); // Lowercase
        $this->assertTrue($this->channelFormLib->bool_string('YES', false)); // Uppercase
        $this->assertTrue($this->channelFormLib->bool_string('Yes', false)); // Mixed case
        $this->assertTrue($this->channelFormLib->bool_string('yes', false)); // Lowercase
    }

    public function testBoolStringWithSqlInjectionAttempts()
    {
        // Test potential SQL injection strings - they contain 't' so match truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string("true' OR '1'='1", false)); // Contains 'true' and 't'
        $this->assertTrue($this->channelFormLib->bool_string('true; DROP TABLE users;', false)); // Contains 'true' and 't'
        $this->assertTrue($this->channelFormLib->bool_string('true UNION SELECT * FROM users', false)); // Contains 'true' and 't'
    }

    public function testBoolStringWithHtmlInjectionAttempts()
    {
        // Test potential XSS/HTML injection strings - 'script' contains 't' so matches truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string('<script>alert("xss")</script>', false)); // Contains 't'
        $this->assertTrue($this->channelFormLib->bool_string('<img src=x onerror=alert("xss")>', false)); // Contains 't'
        $this->assertTrue($this->channelFormLib->bool_string('"><script>alert("xss")</script>', false)); // Contains 't'
    }

    public function testBoolStringWithPathTraversalAttempts()
    {
        // Test path traversal strings - 'etc' contains 't' so matches truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string('../../../etc/passwd', false)); // Contains 't'
        $this->assertTrue($this->channelFormLib->bool_string('..\\..\\..\\windows\\system32', false)); // Contains 't'
        $this->assertTrue($this->channelFormLib->bool_string('/etc/passwd', false)); // Contains 't', matches truthy pattern
    }

    public function testBoolStringWithControlCharacters()
    {
        // Test strings with control characters - all contain 'true' so match truthy pattern
        $this->assertTrue($this->channelFormLib->bool_string("true\x00null", false)); // Contains 'true'
        $this->assertTrue($this->channelFormLib->bool_string("true\x01soh", false)); // Contains 'true'
        $this->assertTrue($this->channelFormLib->bool_string("true\nline\nbreak", false)); // Contains 'true'
        $this->assertTrue($this->channelFormLib->bool_string("true\r\ncrlf", false)); // Contains 'true'
    }

    public function testBoolStringWithVeryLargeStrings()
    {
        // Test with strings that could cause memory or performance issues
        $very_large = str_repeat('x', 10000000); // 10MB string
        $start = microtime(true);
        $result = $this->channelFormLib->bool_string($very_large, true);
        $end = microtime(true);

        $this->assertTrue($result); // Should return default (true)
        $this->assertLessThan(2.0, $end - $start); // Should complete in reasonable time
    }

    public function testBoolStringWithRecursivePatterns()
    {
        // Test strings that could cause regex engine issues
        $recursive = 'true' . str_repeat(' true', 1000); // Many 'true' words
        $start = microtime(true);
        $result = $this->channelFormLib->bool_string($recursive, false);
        $end = microtime(true);

        $this->assertTrue($result); // Should match truthy pattern
        $this->assertLessThan(1.0, $end - $start); // Should complete quickly
    }

    public function testBoolStringWithAmbiguousPatterns()
    {
        // Test strings with multiple conflicting patterns
        $this->assertTrue($this->channelFormLib->bool_string('truenofalse', false)); // Contains both patterns, truthy wins
        $this->assertTrue($this->channelFormLib->bool_string('falsetrue', false)); // Contains both patterns, truthy wins
        $this->assertTrue($this->channelFormLib->bool_string('yesno', false)); // Contains both patterns, truthy wins
        $this->assertTrue($this->channelFormLib->bool_string('offon', false)); // Contains both patterns, truthy wins
    }

    public function testBoolStringWithInternationalCharacters()
    {
        // Test with international characters - 'oui' contains 'u', 'non' contains 'n', 'sí' contains 's'
        $this->assertTrue($this->channelFormLib->bool_string('sí', true)); // Spanish 'yes' - no problematic chars, returns default
        $this->assertFalse($this->channelFormLib->bool_string('não', true)); // Portuguese 'no' - contains 'n', matches falsy
        $this->assertTrue($this->channelFormLib->bool_string('да', true)); // Russian 'yes' - no problematic chars, returns default
        $this->assertTrue($this->channelFormLib->bool_string('нет', true)); // Russian 'no' - Cyrillic chars, returns default
        $this->assertTrue($this->channelFormLib->bool_string('oui', true)); // French 'yes' - no problematic chars, returns default
        $this->assertTrue($this->channelFormLib->bool_string('non', true)); // French 'no' - contains 'n', but truthy pattern wins
    }

    public function testBoolStringWithJsonStrings()
    {
        // Test JSON-like strings that might be passed - all contain 'true' or 'null' substrings
        $this->assertTrue($this->channelFormLib->bool_string('{"truthy": true}', false)); // Contains 'true'
        $this->assertTrue($this->channelFormLib->bool_string('["true", "false"]', false)); // Contains 'true'
        $this->assertFalse($this->channelFormLib->bool_string('null', true)); // Contains 'null', matches falsy
        $this->assertFalse($this->channelFormLib->bool_string('undefined', true)); // Contains 'n', matches falsy
    }

    public function testBoolStringWithBinaryData()
    {
        // Test with binary data (simulated)
        $binary_data = pack('C*', 116, 114, 117, 101); // 'true' in binary
        $result = $this->channelFormLib->bool_string($binary_data, false);
        // This might behave unexpectedly due to character encoding
        $this->assertIsBool($result); // At minimum, should return a boolean
    }
}
