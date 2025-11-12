<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';

class EE_TemplateFetchAddonsTest extends EE_TemplateTestBase
{
    public function testFetchAddonsMethodExists()
    {
        $this->assertTrue(method_exists($this->template, 'fetch_addons'));
        $this->assertTrue(is_callable([$this->template, 'fetch_addons']));
    }

    public function testFetchAddonsPopulatesModulesAndPluginsArrays()
    {
        // Mock addons with mixed module/plugin combinations
        $addon1 = $this->getMockBuilder('stdClass')
            ->setMethods(['hasModule', 'hasPlugin', 'isInstalled'])
            ->getMock();
        $addon1->method('hasModule')->willReturn(true);
        $addon1->method('hasPlugin')->willReturn(false);
        $addon1->method('isInstalled')->willReturn(true);

        $addon2 = $this->getMockBuilder('stdClass')
            ->setMethods(['hasModule', 'hasPlugin', 'isInstalled'])
            ->getMock();
        $addon2->method('hasModule')->willReturn(false);
        $addon2->method('hasPlugin')->willReturn(true);
        $addon2->method('isInstalled')->willReturn(true);

        $addon3 = $this->getMockBuilder('stdClass')
            ->setMethods(['hasModule', 'hasPlugin', 'isInstalled'])
            ->getMock();
        $addon3->method('hasModule')->willReturn(true);
        $addon3->method('hasPlugin')->willReturn(true);
        $addon3->method('isInstalled')->willReturn(true);

        $addon4 = $this->getMockBuilder('stdClass')
            ->setMethods(['hasModule', 'hasPlugin', 'isInstalled'])
            ->getMock();
        $addon4->method('hasModule')->willReturn(true);
        $addon4->method('hasPlugin')->willReturn(false);
        $addon4->method('isInstalled')->willReturn(false);

        $addons = [
            'channel' => $addon1,
            'plugin_example' => $addon2,
            'mixed_addon' => $addon3,
            'uninstalled_module' => $addon4
        ];

        $addonMock = $this->getMockBuilder('stdClass')
            ->setMethods(['all'])
            ->getMock();
        $addonMock->method('all')->willReturn($addons);
        ee()->setMock('Addon', $addonMock);

        // Reset arrays to ensure clean state
        $this->template->modules = [];
        $this->template->plugins = [];
        $this->template->module_data = [];

        $this->template->fetch_addons();

        // Check modules array - ALL modules are added regardless of installation status
        $this->assertContains('channel', $this->template->modules);
        $this->assertContains('mixed_addon', $this->template->modules);
        $this->assertContains('uninstalled_module', $this->template->modules); // Even uninstalled modules go here
        $this->assertNotContains('plugin_example', $this->template->modules);

        // Check plugins array
        $this->assertContains('plugin_example', $this->template->plugins);
        $this->assertContains('mixed_addon', $this->template->plugins);
        $this->assertNotContains('channel', $this->template->plugins);

        // Check module_data array
        $this->assertArrayHasKey('Channel', $this->template->module_data);
        $this->assertArrayHasKey('Mixed_addon', $this->template->module_data);
        $this->assertArrayNotHasKey('Uninstalled_module', $this->template->module_data);
        $this->assertEquals('channel', $this->template->module_data['Channel']);
        $this->assertEquals('mixed_addon', $this->template->module_data['Mixed_addon']);
    }

    public function testFetchAddonsHandlesEmptyAddons()
    {
        $addonMock = $this->getMockBuilder('stdClass')
            ->setMethods(['all'])
            ->getMock();
        $addonMock->method('all')->willReturn([]);
        ee()->setMock('Addon', $addonMock);

        $this->template->modules = [];
        $this->template->plugins = [];
        $this->template->module_data = [];

        $this->template->fetch_addons();

        $this->assertEmpty($this->template->modules);
        $this->assertEmpty($this->template->plugins);
        $this->assertEmpty($this->template->module_data);
    }

    public function testFetchAddonsHandlesModulesOnly()
    {
        $addon1 = $this->getMockBuilder('stdClass')
            ->setMethods(['hasModule', 'hasPlugin', 'isInstalled'])
            ->getMock();
        $addon1->method('hasModule')->willReturn(true);
        $addon1->method('hasPlugin')->willReturn(false);
        $addon1->method('isInstalled')->willReturn(true);

        $addon2 = $this->getMockBuilder('stdClass')
            ->setMethods(['hasModule', 'hasPlugin', 'isInstalled'])
            ->getMock();
        $addon2->method('hasModule')->willReturn(true);
        $addon2->method('hasPlugin')->willReturn(false);
        $addon2->method('isInstalled')->willReturn(true);

        $addons = [
            'channel' => $addon1,
            'member' => $addon2
        ];

        $addonMock = $this->getMockBuilder('stdClass')
            ->setMethods(['all'])
            ->getMock();
        $addonMock->method('all')->willReturn($addons);
        ee()->setMock('Addon', $addonMock);

        $this->template->modules = [];
        $this->template->plugins = [];
        $this->template->module_data = [];

        $this->template->fetch_addons();

        $this->assertCount(2, $this->template->modules);
        $this->assertContains('channel', $this->template->modules);
        $this->assertContains('member', $this->template->modules);
        $this->assertEmpty($this->template->plugins);
        $this->assertCount(2, $this->template->module_data);
    }

    public function testFetchAddonsHandlesPluginsOnly()
    {
        $addon1 = $this->getMockBuilder('stdClass')
            ->setMethods(['hasModule', 'hasPlugin', 'isInstalled'])
            ->getMock();
        $addon1->method('hasModule')->willReturn(false);
        $addon1->method('hasPlugin')->willReturn(true);
        $addon1->method('isInstalled')->willReturn(true);

        $addon2 = $this->getMockBuilder('stdClass')
            ->setMethods(['hasModule', 'hasPlugin', 'isInstalled'])
            ->getMock();
        $addon2->method('hasModule')->willReturn(false);
        $addon2->method('hasPlugin')->willReturn(true);
        $addon2->method('isInstalled')->willReturn(true);

        $addons = [
            'plugin1' => $addon1,
            'plugin2' => $addon2
        ];

        $addonMock = $this->getMockBuilder('stdClass')
            ->setMethods(['all'])
            ->getMock();
        $addonMock->method('all')->willReturn($addons);
        ee()->setMock('Addon', $addonMock);

        $this->template->modules = [];
        $this->template->plugins = [];
        $this->template->module_data = [];

        $this->template->fetch_addons();

        $this->assertEmpty($this->template->modules);
        $this->assertCount(2, $this->template->plugins);
        $this->assertContains('plugin1', $this->template->plugins);
        $this->assertContains('plugin2', $this->template->plugins);
        $this->assertEmpty($this->template->module_data);
    }

    public function testFetchAddonsHandlesUninstalledAddons()
    {
        $addon1 = $this->getMockBuilder('stdClass')
            ->setMethods(['hasModule', 'hasPlugin', 'isInstalled'])
            ->getMock();
        $addon1->method('hasModule')->willReturn(true);
        $addon1->method('hasPlugin')->willReturn(false);
        $addon1->method('isInstalled')->willReturn(false);

        $addon2 = $this->getMockBuilder('stdClass')
            ->setMethods(['hasModule', 'hasPlugin', 'isInstalled'])
            ->getMock();
        $addon2->method('hasModule')->willReturn(false);
        $addon2->method('hasPlugin')->willReturn(true);
        $addon2->method('isInstalled')->willReturn(false);

        $addons = [
            'uninstalled_module' => $addon1,
            'uninstalled_plugin' => $addon2
        ];

        $addonMock = $this->getMockBuilder('stdClass')
            ->setMethods(['all'])
            ->getMock();
        $addonMock->method('all')->willReturn($addons);
        ee()->setMock('Addon', $addonMock);

        $this->template->modules = [];
        $this->template->plugins = [];
        $this->template->module_data = [];

        $this->template->fetch_addons();

        // Uninstalled modules are still added to modules array, just not to module_data
        $this->assertContains('uninstalled_module', $this->template->modules);
        $this->assertEmpty($this->template->plugins);
        $this->assertEmpty($this->template->module_data);
    }
}
