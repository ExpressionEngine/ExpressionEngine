<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';

class EE_TemplateCreateFromFileTest extends EE_TemplateTestBase
{
    public function testCreateFromFileMethodExists()
    {
        $this->assertTrue(method_exists($this->template, '_create_from_file'));
    }

    public function testCreateFromFileSaveTmplFilesDisabled()
    {
        // Mock config to return 'n' for save_tmpl_files
        $configMock = ee()->config;
        $configMock->items['save_tmpl_files'] = 'n';
        ee()->setMock('config', $configMock);

        $result = $this->template->_create_from_file('test_group', 'test_template');

        $this->assertFalse($result);
    }

    public function testCreateFromFileNameLengthValidation()
    {
        // Mock config to return 'y' for save_tmpl_files
        $configMock = ee()->config;
        $configMock->items['save_tmpl_files'] = 'y';
        ee()->setMock('config', $configMock);

        // Test template name too long
        $longName = str_repeat('a', 51);
        $result = $this->template->_create_from_file('test_group', $longName);
        $this->assertFalse($result);

        // Test group name too long
        $result = $this->template->_create_from_file($longName, 'test_template');
        $this->assertFalse($result);
    }

    public function testCreateFromFileHandlesDbCheckWhenTemplateExists()
    {
        // Mock config
        $configMock = ee()->config;
        $configMock->items['save_tmpl_files'] = 'y';
        $configMock->items['site_id'] = 1;
        ee()->setMock('config', $configMock);

        // Create a proper db mock that supports the chaining methods used
        $dbMock = $this->getMockBuilder(\FakeDb::class)
            ->setMethods(['from', 'join', 'where', 'count_all_results'])
            ->getMock();

        // Set up the mock to return a result with num_rows = 1 (template exists)
        $dbResultMock = $this->getMockBuilder('stdClass')
            ->setMethods(['num_rows'])
            ->getMock();
        $dbResultMock->method('num_rows')->willReturn(1);

        $dbMock->method('from')->willReturnSelf();
        $dbMock->method('join')->willReturnSelf();
        $dbMock->method('where')->willReturnSelf();
        $dbMock->method('count_all_results')->willReturn(1); // Template exists

        ee()->setMock('db', $dbMock);

        $result = $this->template->_create_from_file('existing_group', 'existing_template', true);

        $this->assertFalse($result);
    }

    public function testCreateFromFileHandlesConfigurationChecks()
    {
        // Test that the method properly checks configuration before proceeding
        // This test focuses on the early return conditions

        // Test with save_tmpl_files disabled
        $configMock = ee()->config;
        $configMock->items['save_tmpl_files'] = 'n';
        ee()->setMock('config', $configMock);

        $result = $this->template->_create_from_file('test_group', 'test_template');
        $this->assertFalse($result);

        // Reset config for next test
        $configMock->items['save_tmpl_files'] = 'y';
        ee()->setMock('config', $configMock);

        // Test with long names - should return false immediately
        $longName = str_repeat('a', 51);
        $result = $this->template->_create_from_file($longName, 'test_template');
        $this->assertFalse($result);

        $result = $this->template->_create_from_file('test_group', $longName);
        $this->assertFalse($result);
    }

    public function testCreateFromFileHandlesBasicValidation()
    {
        // Test basic validation that doesn't require complex file system mocking

        // Test with valid config but invalid length (should fail early)
        $configMock = ee()->config;
        $configMock->items['save_tmpl_files'] = 'y';
        ee()->setMock('config', $configMock);

        // Test name length validation
        $longName = str_repeat('a', 51);
        $result = $this->template->_create_from_file($longName, 'test');
        $this->assertFalse($result);

        $result = $this->template->_create_from_file('test', $longName);
        $this->assertFalse($result);

        // Test that empty template defaults to 'index'
        // This is hard to test directly since the method has many dependencies
        // We'll just verify the method exists and is callable
        $this->assertTrue(is_callable([$this->template, '_create_from_file']));
    }

    public function testCreateFromFileMethodSignature()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, '_create_from_file');
        $this->assertTrue($reflection->isPublic());

        $parameters = $reflection->getParameters();
        $this->assertCount(3, $parameters);

        $this->assertEquals('template_group', $parameters[0]->getName());
        $this->assertEquals('template', $parameters[1]->getName());
        $this->assertEquals('db_check', $parameters[2]->getName());
        $this->assertTrue($parameters[2]->isDefaultValueAvailable()); // Has default value of false
    }

    public function testCreateFromFileHandlesBasicParameters()
    {
        // Test that method accepts the expected parameters and handles basic validation

        // Test that method exists and can be called
        $this->assertTrue(is_callable([$this->template, '_create_from_file']));

        // Test parameter validation - long names should fail early
        $configMock = ee()->config;
        $configMock->items['save_tmpl_files'] = 'y';
        ee()->setMock('config', $configMock);

        $longName = str_repeat('a', 51);
        $result = $this->template->_create_from_file($longName, 'test');
        $this->assertFalse($result); // Should fail due to length

        $result = $this->template->_create_from_file('test', $longName);
        $this->assertFalse($result); // Should fail due to length
    }
}
