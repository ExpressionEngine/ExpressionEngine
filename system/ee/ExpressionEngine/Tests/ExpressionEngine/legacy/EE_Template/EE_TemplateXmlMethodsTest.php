<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';

class EE_TemplateXmlMethodsTest extends EE_TemplateTestBase
{
    /**
     * Test convert_xml_declaration method exists
     */
    public function testConvertXmlDeclarationMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'convert_xml_declaration'));
        $this->assertTrue(is_callable([$this->template, 'convert_xml_declaration']));
    }

    /**
     * Test convert_xml_declaration with valid XML declaration
     */
    public function testConvertXmlDeclarationValidXml()
    {
        $input = '<?xml version="1.0" encoding="UTF-8"?><root>content</root>';
        $expected = '<XXML version="1.0" encoding="UTF-8"/XXML><root>content</root>';

        $result = $this->template->convert_xml_declaration($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test convert_xml_declaration with XML declaration in middle of content
     */
    public function testConvertXmlDeclarationXmlInMiddle()
    {
        $input = '<html><?xml version="1.0"?></html>';
        $expected = '<html><XXML version="1.0"/XXML></html>';

        $result = $this->template->convert_xml_declaration($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test convert_xml_declaration with no XML declaration
     */
    public function testConvertXmlDeclarationNoXml()
    {
        $input = '<html><body>content</body></html>';
        $expected = '<html><body>content</body></html>';

        $result = $this->template->convert_xml_declaration($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test convert_xml_declaration with complex XML declaration
     */
    public function testConvertXmlDeclarationComplexXml()
    {
        $input = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><data>content</data>';
        $expected = '<XXML version="1.0" encoding="UTF-8" standalone="yes"/XXML><data>content</data>';

        $result = $this->template->convert_xml_declaration($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test convert_xml_declaration with empty string
     */
    public function testConvertXmlDeclarationEmptyString()
    {
        $input = '';
        $expected = '';

        $result = $this->template->convert_xml_declaration($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test restore_xml_declaration method exists
     */
    public function testRestoreXmlDeclarationMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'restore_xml_declaration'));
        $this->assertTrue(is_callable([$this->template, 'restore_xml_declaration']));
    }

    /**
     * Test restore_xml_declaration with converted XML declaration
     */
    public function testRestoreXmlDeclarationValidXml()
    {
        $input = '<XXML version="1.0" encoding="UTF-8"/XXML><root>content</root>';
        $expected = '<?xml version="1.0" encoding="UTF-8"?><root>content</root>';

        $result = $this->template->restore_xml_declaration($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test restore_xml_declaration with XML declaration in middle of content
     */
    public function testRestoreXmlDeclarationXmlInMiddle()
    {
        $input = '<html><XXML version="1.0"/XXML></html>';
        $expected = '<html><?xml version="1.0"?></html>';

        $result = $this->template->restore_xml_declaration($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test restore_xml_declaration with no converted XML declaration
     */
    public function testRestoreXmlDeclarationNoConvertedXml()
    {
        $input = '<html><body>content</body></html>';
        $expected = '<html><body>content</body></html>';

        $result = $this->template->restore_xml_declaration($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test restore_xml_declaration with complex XML declaration
     */
    public function testRestoreXmlDeclarationComplexXml()
    {
        $input = '<XXML version="1.0" encoding="UTF-8" standalone="yes"/XXML><data>content</data>';
        $expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><data>content</data>';

        $result = $this->template->restore_xml_declaration($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test restore_xml_declaration with empty string
     */
    public function testRestoreXmlDeclarationEmptyString()
    {
        $input = '';
        $expected = '';

        $result = $this->template->restore_xml_declaration($input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test round-trip conversion: convert then restore
     */
    public function testXmlDeclarationRoundTrip()
    {
        $original = '<?xml version="1.0" encoding="UTF-8"?><root><content>Test</content></root>';

        $converted = $this->template->convert_xml_declaration($original);
        $restored = $this->template->restore_xml_declaration($converted);

        $this->assertEquals($original, $restored);
        $this->assertNotEquals($original, $converted);
    }
}
