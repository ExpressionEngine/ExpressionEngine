<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

/**
 * Comprehensive tests for EE_Template::parse_encode_email() method
 */
class EE_TemplateParseEncodeEmailTest extends EE_TemplateTestBase
{
    /**
     * Test parse_encode_email method exists
     */
    public function testParseEncodeEmailMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'parse_encode_email'));
        $this->assertTrue(is_callable([$this->template, 'parse_encode_email']));
    }

    /**
     * Test parse_encode_email with simple email
     */
    public function testParseEncodeEmailWithSimpleEmail()
    {
        $tagdata = '{encode="test@example.com"}';

        $result = $this->template->parse_encode_email($tagdata);

        $this->assertTrue(strpos($result, '{encode=') === false); // Original tag should be replaced
        $this->assertTrue(strpos($result, '[email]') !== false); // Should contain encoded email
    }

    /**
     * Test parse_encode_email with multiple emails
     */
    public function testParseEncodeEmailWithMultipleEmails()
    {
        $tagdata = 'Contact us: {encode="info@example.com"} or {encode="support@example.com"}';

        $result = $this->template->parse_encode_email($tagdata);

        $this->assertTrue(strpos($result, '{encode=') === false); // All tags should be replaced
        $this->assertTrue(strpos($result, '[email]') !== false); // Should contain encoded emails
        $this->assertTrue(substr_count($result, '[email]') === 2); // Should have two encoded emails
    }

    /**
     * Test parse_encode_email with invalid emails
     */
    public function testParseEncodeEmailWithInvalidEmails()
    {
        $tagdata = '{encode="invalid-email"} {encode="also-invalid@"} {encode="@domain.com"}';

        $result = $this->template->parse_encode_email($tagdata);

        $this->assertTrue(strpos($result, '{encode=') === false); // All tags should be replaced
        $this->assertTrue(strpos($result, '[email]') !== false); // Should still encode invalid emails
    }

    /**
     * Test parse_encode_email with parameters
     */
    public function testParseEncodeEmailWithParameters()
    {
        $tagdata = '{encode="john.doe+tag@example.com" title="Contact John"}';

        $result = $this->template->parse_encode_email($tagdata);

        $this->assertTrue(strpos($result, '{encode=') === false); // Tag should be replaced
        $this->assertTrue(strpos($result, '[email]') !== false); // Should contain encoded email
        // Parameters are typically stripped during encoding, so we just verify the email is encoded
    }

    /**
     * Test parse_encode_email with no encode tags
     */
    public function testParseEncodeEmailWithNoEncodeTags()
    {
        $tagdata = 'Regular content with no encode tags. Email: test@example.com';

        $result = $this->template->parse_encode_email($tagdata);

        $this->assertEquals($tagdata, $result); // Should return unchanged
    }

    /**
     * Test parse_encode_email with empty string
     */
    public function testParseEncodeEmailWithEmptyString()
    {
        $tagdata = '';

        $result = $this->template->parse_encode_email($tagdata);

        $this->assertEquals('', $result);
    }

    /**
     * Test parse_encode_email with special characters in email
     */
    public function testParseEncodeEmailWithSpecialCharacters()
    {
        $tagdata = '{encode="test+filter@example.com"} {encode="user_name@example-domain.co.uk"}';

        $result = $this->template->parse_encode_email($tagdata);

        $this->assertTrue(strpos($result, '{encode=') === false); // All tags should be replaced
        $this->assertTrue(strpos($result, '[email]') !== false); // Should contain encoded emails
    }

    /**
     * Test parse_encode_email with mixed content
     */
    public function testParseEncodeEmailWithMixedContent()
    {
        $tagdata = 'Please contact {encode="admin@example.com"} for support. Also try {encode="help@example.com"}.';

        $result = $this->template->parse_encode_email($tagdata);

        $this->assertStringContainsString('Please contact', $result);
        $this->assertStringContainsString('for support', $result);
        $this->assertStringContainsString('Also try', $result);
        $this->assertTrue(strpos($result, '{encode=') === false); // All tags should be replaced
        $this->assertTrue(substr_count($result, '[email]') === 2); // Should have two encoded emails
    }

    /**
     * Test parse_encode_email processes all encode tags regardless of nesting
     */
    public function testParseEncodeEmailProcessesAllEncodeTags()
    {
        $tagdata = 'Start {encode="test@example.com"} and {if condition}more {encode="other@example.com"}{/if} end';

        $result = $this->template->parse_encode_email($tagdata);

        // All encode tags should be processed
        $this->assertStringNotContainsString('{encode=', $result);
        $this->assertStringContainsString('[email]', $result);
    }

    /**
     * Test parse_encode_email preserves surrounding content
     */
    public function testParseEncodeEmailPreservesSurroundingContent()
    {
        $tagdata = '<p>Contact: {encode="contact@example.com"}</p><div>Support: {encode="support@example.com"}</div>';

        $result = $this->template->parse_encode_email($tagdata);

        $this->assertStringStartsWith('<p>Contact: ', $result);
        $this->assertStringEndsWith('</div>', $result);
        $this->assertStringContainsString('</p><div>Support: ', $result);
        $this->assertTrue(strpos($result, '{encode=') === false); // All tags should be replaced
    }

    /**
     * Test parse_encode_email with JavaScript-like email construction
     */
    public function testParseEncodeEmailCreatesJavaScriptEmailLink()
    {
        $tagdata = '{encode="mailto:test@example.com"}';

        $result = $this->template->parse_encode_email($tagdata);

        $this->assertTrue(strpos($result, '{encode=') === false); // Tag should be replaced
        $this->assertTrue(strpos($result, '[email]') !== false); // Should contain encoded email
        // The actual encoding creates JavaScript that reconstructs the email
    }

    /**
     * Test parse_encode_email with long email addresses
     */
    public function testParseEncodeEmailWithLongEmailAddresses()
    {
        $longEmail = 'very.long.email.address.that.goes.on.for.a.while@subdomain.example.co.uk';
        $tagdata = '{encode="' . $longEmail . '"}';

        $result = $this->template->parse_encode_email($tagdata);

        $this->assertTrue(strpos($result, '{encode=') === false); // Tag should be replaced
        $this->assertTrue(strpos($result, '[email]') !== false); // Should contain encoded email
    }
}
