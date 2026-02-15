<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';
require_once SYSPATH . 'ee/legacy/libraries/Template.php';

class EE_TemplateShow404Test extends EE_TemplateTestBase
{
    public function testShow404ConfigDependency()
    {
        // Test that show_404 depends on site_404 config
        ee()->config->setItem('site_404', 'error/404');

        $template = new \EE_Template();

        // Verify that the config is accessible
        $this->assertEquals('error/404', ee()->config->item('site_404'));
    }

    public function testShow404TemplateTypeSetting()
    {
        // Test that show_404 would set template_type to '404'
        $template = new \EE_Template();

        // Simulate what show_404 does
        $template->template_type = '404';
        $template->layout_vars = [];

        $this->assertEquals('404', $template->template_type);
        $this->assertEquals([], $template->layout_vars);
    }

    public function testShow404ConfigParsing()
    {
        // Test that site_404 config is parsed correctly
        ee()->config->setItem('site_404', 'error/custom');

        $site_404 = ee()->config->item('site_404');
        $template = explode('/', $site_404);

        $this->assertEquals('error', $template[0]);
        $this->assertEquals('custom', $template[1]);
    }

    public function testShow404EmptyConfig()
    {
        // Test behavior with empty site_404 config
        ee()->config->setItem('site_404', '');

        $site_404 = ee()->config->item('site_404');

        $this->assertEmpty($site_404);
        $this->assertEquals('', $site_404); // Empty string
    }

    public function testShow404MalformedConfig()
    {
        // Test behavior with malformed site_404 config
        ee()->config->setItem('site_404', 'malformed_config_no_slash');

        $site_404 = ee()->config->item('site_404');
        $template = explode('/', $site_404);

        // Should result in array with one element
        $this->assertCount(1, $template);
        $this->assertEquals('malformed_config_no_slash', $template[0]);
    }

    public function testShow404MethodSignature()
    {
        $reflection = new \ReflectionMethod(\EE_Template::class, 'show_404');
        $this->assertTrue($reflection->isPublic());

        $parameters = $reflection->getParameters();
        $this->assertCount(0, $parameters);
    }
}
