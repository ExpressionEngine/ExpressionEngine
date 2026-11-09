<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Addons {

    /**
     * Raised when the add-ons controller denies the request.
     */
    class AddonsShowErrorException extends \RuntimeException
    {
    }

    if (! function_exists(__NAMESPACE__ . '\show_error')) {
        /**
         * Replace the controller error response with an exception.
         *
         * @param string $message
         * @param int $status
         * @return void
         *
         * @throws AddonsShowErrorException
         */
        function show_error($message, $status = 500)
        {
            throw new AddonsShowErrorException((string) $message, $status);
        }
    }
}

namespace ExpressionEngine\Tests\Controllers\Addons {

use ExpressionEngine\Controller\Addons\Addons;
use ExpressionEngine\Controller\Addons\AddonsShowErrorException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

class AddonsTest extends TestCase
{
    private $renderedSettings;
    /**
     * Load the controller base class used by the add-ons controller.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        require_once(APPPATH . 'core/Controller.php');
        if (!defined('NBS')) {
            define('NBS', '&nbsp;');
        }
    }

    /**
     * Clear EE container mocks between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    /**
     * Verify add-on removal rejects non-POST requests before setup work.
     *
     * @return void
     *
     * @throws \ReflectionException
     */
    public function testRemoveRequiresPostRequest()
    {
        $this->mockAdminAddonPermission(true);
        $this->mockRequestMethod('GET');
        $this->mockNoInstallerSetup();

        $this->expectException(AddonsShowErrorException::class);
        $this->expectExceptionMessage('unauthorized_access');
        $this->expectExceptionCode(403);

        $this->makeController()->remove('placeholder');
    }

    public function testFieldtypeOnlyAddonDispatchesModernControlPanelRoute()
    {
        $this->mockAddonSettings();

        $body = $this->makeController()->settings('test_fieldtype', 'preview_data', 42);

        $this->assertSame('{"success":true,"id":42}', $body);
        $this->assertSame([42], Fixtures\ControlPanel\Routes\PreviewData::$processedIds);
        $this->assertNull($this->renderedSettings);
    }

    public function testModuleAddonStillDispatchesModernControlPanelRoute()
    {
        $this->mockAddonSettings(true);

        $body = $this->makeController()->settings('test_fieldtype', 'preview_data', 42);

        $this->assertSame('{"success":true,"id":42}', $body);
        $this->assertSame([42], Fixtures\ControlPanel\Routes\PreviewData::$processedIds);
    }

    /**
     * @dataProvider helperRouteRequests
     */
    public function testModuleRouteNamedTryRouteStillDispatches($method, $parameters, $id)
    {
        $this->mockAddonSettings(true);

        $body = $this->makeController()->settings('test_fieldtype', $method, ...$parameters);

        $this->assertSame(json_encode(['success' => true, 'id' => $id]), $body);
        $this->assertSame([$id], Fixtures\ControlPanel\Routes\PreviewData::$processedIds);
    }

    public function helperRouteRequests()
    {
        return [
            'underscore without parameters' => ['try_route', [], ''],
            'underscore with id' => ['try_route', [42], 42],
            'hyphenated with id' => ['try-route', [42], 42],
            'camel case with id' => ['tryRoute', [42], 42],
        ];
    }

    public function testAddonDefinedTryRouteMethodKeepsPrecedence()
    {
        $this->mockAddonSettings(true, true, true, Fixtures\CustomMethodMcp::class);

        $body = $this->makeController()->settings('test_fieldtype', 'try_route', 42);

        $this->assertSame('Custom method 42', $body);
        $this->assertSame([], Fixtures\ControlPanel\Routes\PreviewData::$processedIds);
    }

    /**
     * @dataProvider componentsWithRoutes
     */
    public function testComponentRoutesLoadTranslationsBeforeConstructingTheMcp($extensionInstalled)
    {
        $this->mockAddonSettings(false, !$extensionInstalled, true, Fixtures\LocalizedMcp::class, $extensionInstalled);
        require_once APPPATH . 'core/Lang.php';
        $language = $this->getMockBuilder(\EE_Lang::class)
            ->onlyMethods(['loadfile'])
            ->getMock();
        $language->expects($this->once())->method('loadfile')
            ->with('test_fieldtype', '', false)
            ->willReturnCallback(function () use ($language) {
                $language->language['test_fieldtype_heading'] = 'Preview data';
            });
        ee()->setMock('lang', $language);

        $this->makeController()->settings('test_fieldtype', 'localized');

        $this->assertSame('Preview data', Fixtures\LocalizedMcp::$constructorHeading);
        $this->assertSame('Preview data', ee()->view->cp_heading);
    }

    public function componentsWithRoutes()
    {
        return [
            'fieldtype' => [false],
            'extension' => [true],
        ];
    }

    public function testFieldtypeSettingsFallbackWithModernControlPanel()
    {
        $this->mockAddonSettings();

        $this->makeController()->settings('test_fieldtype', 'index');

        $this->assertSame('legacy fieldtype settings', $this->renderedSettings['_module_cp_body']);
        $this->assertSame([], Fixtures\ControlPanel\Routes\PreviewData::$processedIds);
    }

    public function testFieldtypeSettingsWithoutControlPanelStillRender()
    {
        $this->mockAddonSettings(false, true, false);

        $this->makeController()->settings('test_fieldtype', 'index');

        $this->assertSame('legacy fieldtype settings', $this->renderedSettings['_module_cp_body']);
    }

    public function testFieldtypeSettingsSaveStillUsesGlobalSettingsHandler()
    {
        $fieldtype = $this->mockAddonSettings();
        $settings = ['enabled' => false];
        $fields = $this->getMockBuilder(stdClass::class)
            ->addMethods(['fetch_installed_fieldtypes', 'setup_handler', 'apply'])
            ->getMock();
        $fields->expects($this->once())->method('fetch_installed_fieldtypes');
        $fields->expects($this->once())->method('setup_handler')
            ->with('test_fieldtype', true)->willReturn(new stdClass());
        $fields->expects($this->once())->method('apply')
            ->with('save_global_settings')->willReturn($settings);
        ee()->setMock('api_channel_fields', $fields);
        $fieldtype->expects($this->once())->method('save')->willReturnCallback(function () use ($fieldtype, $settings) {
            $this->assertSame($settings, $fieldtype->settings);
        });
        $functions = $this->getMockBuilder(stdClass::class)->addMethods(['redirect'])->getMock();
        $functions->expects($this->once())->method('redirect')->willReturnCallback(function () {
            throw new AddonsShowErrorException('redirect', 302);
        });
        ee()->setMock('functions', $functions);

        $this->expectException(AddonsShowErrorException::class);
        $this->expectExceptionMessage('redirect');
        $this->expectExceptionCode(302);

        $this->makeController()->settings('test_fieldtype', 'save');
    }

    public function testUninstalledFieldtypeCannotDispatchControlPanelRoute()
    {
        $this->mockAddonSettings(false, false);

        try {
            $this->makeController()->settings('test_fieldtype', 'preview_data', 42);
            $this->fail('An uninstalled add-on must not dispatch a route.');
        } catch (AddonsShowErrorException $e) {
            $this->assertStringContainsString('requested_module_not_installed', $e->getMessage());
        }

        $this->assertSame([], Fixtures\ControlPanel\Routes\PreviewData::$processedIds);
    }

    public function testFieldtypeControlPanelRouteRetainsAccessCheck()
    {
        $this->mockAddonSettings();
        $permission = $this->getMockBuilder(stdClass::class)->addMethods(['isSuperAdmin'])->getMock();
        $permission->method('isSuperAdmin')->willReturn(false);
        ee()->setMock('Permission', $permission);

        try {
            $this->makeController()->settings('test_fieldtype', 'preview_data', 42);
            $this->fail('A denied request must not dispatch a route.');
        } catch (AddonsShowErrorException $e) {
            $this->assertSame(403, $e->getCode());
            $this->assertSame('unauthorized_access', $e->getMessage());
        }

        $this->assertSame([], Fixtures\ControlPanel\Routes\PreviewData::$processedIds);
    }

    public function testFieldtypeRouteRendersItsBodyHeadingAndBreadcrumbs()
    {
        $this->mockAddonSettings();

        $this->makeController()->settings('test_fieldtype', 'details', 42);

        $this->assertSame('Details for 42', $this->renderedSettings['_module_cp_body']);
        $this->assertSame('Fieldtype details', ee()->view->cp_heading);
        $this->assertSame('Details', ee()->view->cp_breadcrumbs['/cp/details']);
    }

    public function testFieldtypeCanProvideAnIndexRoute()
    {
        $this->mockAddonSettings(false, true, true, Fixtures\IndexMcp::class);

        $this->makeController()->settings('test_fieldtype', 'index');

        $this->assertSame('Modern index', $this->renderedSettings['_module_cp_body']);
    }

    public function testUnknownFieldtypeRouteFallsBackToGlobalSettings()
    {
        $this->mockAddonSettings();

        $this->makeController()->settings('test_fieldtype', 'missing_route');

        $this->assertSame('legacy fieldtype settings', $this->renderedSettings['_module_cp_body']);
    }

    public function testLegacyFieldtypeControlPanelKeepsGlobalSettings()
    {
        $this->mockAddonSettings(false, true, true, Fixtures\LegacyMcp::class);

        $this->makeController()->settings('test_fieldtype', 'index');

        $this->assertSame('legacy fieldtype settings', $this->renderedSettings['_module_cp_body']);
    }

    public function testLegacyModuleMethodsStillDispatch()
    {
        $this->mockAddonSettings(true, true, true, Fixtures\LegacyMcp::class);

        $this->assertSame('legacy module response', $this->makeController()->settings('test_fieldtype', 'preview_data'));
    }

    public function testExtensionOnlyAddonDispatchesModernControlPanelRoute()
    {
        $this->mockAddonSettings(false, false, true, null, true);

        $body = $this->makeController()->settings('test_fieldtype', 'preview_data', 42);

        $this->assertSame('{"success":true,"id":42}', $body);
        $this->assertSame([42], Fixtures\ControlPanel\Routes\PreviewData::$processedIds);
    }

    public function testExtensionWithoutAnIndexRouteKeepsSettingsFallback()
    {
        $this->mockAddonSettings(false, false, true, null, true);

        $this->makeController()->settings('test_fieldtype', 'index');

        $this->assertSame('legacy extension settings', $this->renderedSettings['_module_cp_body']);
    }

    private function mockAddonSettings($moduleInstalled = false, $fieldtypeInstalled = true, $hasControlPanel = true, $controlPanelClass = null, $extensionInstalled = false)
    {
        require_once __DIR__ . '/Fixtures/ControlPanelRoutes.php';
        Fixtures\ControlPanel\Routes\PreviewData::$processedIds = [];
        $this->renderedSettings = null;

        $permission = $this->getMockBuilder(stdClass::class)->addMethods(['isSuperAdmin', 'can'])->getMock();
        $permission->method('isSuperAdmin')->willReturn(true);
        $permission->method('can')->willReturn(true);
        ee()->setMock('Permission', $permission);

        $provider = $this->getMockBuilder(stdClass::class)->addMethods(['get'])->getMock();
        $info = $this->getMockBuilder(\ExpressionEngine\Service\Addon\Addon::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasInstaller', 'hasModule', 'hasFieldtype', 'hasExtension', 'hasControlPanel', 'getControlPanelClass', 'getProvider', 'getName', 'checkCachedLicenseResponse'])
            ->addMethods(['getVersion', 'getAuthor', 'getPath', 'get'])
            ->getMock();
        $info->method('hasModule')->willReturn($moduleInstalled);
        $info->method('hasFieldtype')->willReturn(!$extensionInstalled);
        $info->method('hasExtension')->willReturn($extensionInstalled);
        $info->method('hasInstaller')->willReturn(false);
        $info->method('hasControlPanel')->willReturn($hasControlPanel);
        $info->method('getControlPanelClass')->willReturn($controlPanelClass ?: Fixtures\FieldtypeMcp::class);
        $info->method('getProvider')->willReturn($provider);
        $info->method('getName')->willReturn('Test Fieldtype');
        $info->method('getVersion')->willReturn('1.0.0');
        $info->method('getAuthor')->willReturn('Test Author');
        $info->method('getPath')->willReturn(__DIR__ . '/Fixtures');
        $info->method('get')->willReturnCallback(function ($key) {
            return $key === 'settings_exist' ? true : null;
        });
        $addons = $this->getMockBuilder(stdClass::class)->addMethods(['get'])->getMock();
        $addons->method('get')->with('test_fieldtype')->willReturn($info);
        ee()->setMock('Addon', $addons);

        $fieldtype = $this->getMockBuilder(stdClass::class)->addMethods(['save'])->getMock();
        $fieldtype->version = '1.0.0';
        $fieldtype->settings = ['enabled' => true];
        $extension = (object) ['version' => '1.0.0', 'enabled' => 'y', 'settings' => []];
        $module = (object) ['module_id' => 42, 'module_version' => '1.0.0'];
        $models = $this->getMockBuilder(stdClass::class)->addMethods(['get'])->getMock();
        $models->method('get')->willReturnCallback(function ($type) use ($fieldtype, $module, $moduleInstalled, $fieldtypeInstalled, $extension, $extensionInstalled) {
            $query = $this->getMockBuilder(stdClass::class)->addMethods(['filter', 'first'])->getMock();
            $query->method('filter')->willReturnSelf();
            $records = ['Module' => $moduleInstalled ? $module : null, 'Fieldtype' => $fieldtypeInstalled ? $fieldtype : null, 'Extension' => $extensionInstalled ? $extension : null];
            $query->method('first')->willReturn($records[$type]);
            return $query;
        });
        ee()->setMock('Model', $models);

        $urls = $this->getMockBuilder(stdClass::class)->addMethods(['make'])->getMock();
        $urls->method('make')->willReturnCallback(function ($path) {
            $url = $this->getMockBuilder(stdClass::class)->addMethods(['compile'])->getMock();
            $url->method('compile')->willReturn('/cp/' . $path);
            return $url;
        });
        ee()->setMock('CP/URL', $urls);
        ee()->setMock('view', new stdClass());
        $cp = $this->getMockBuilder(stdClass::class)->addMethods(['render'])->getMock();
        $cp->method('render')->willReturnCallback(function ($view, $vars) {
            $this->renderedSettings = $vars;
        });
        ee()->setMock('cp', $cp);

        $fields = $this->getMockBuilder(stdClass::class)->addMethods(['fetch_installed_fieldtypes', 'setup_handler', 'apply'])->getMock();
        $fields->method('setup_handler')->willReturn(new stdClass());
        $fields->method('apply')->willReturn([]);
        ee()->setMock('api_channel_fields', $fields);
        $views = $this->getMockBuilder(stdClass::class)->addMethods(['make', 'render'])->getMock();
        $views->method('make')->willReturnSelf();
        $views->method('render')->willReturn($extensionInstalled ? 'legacy extension settings' : 'legacy fieldtype settings');
        ee()->setMock('View', $views);

        $load = $this->getMockBuilder(stdClass::class)->addMethods(['add_package_path', 'remove_package_path'])->getMock();
        ee()->setMock('load', $load);
        $security = $this->getMockBuilder(stdClass::class)->addMethods(['sanitize_filename'])->getMock();
        $security->method('sanitize_filename')->willReturnArgument(0);
        ee()->setMock('security', $security);
        $alerts = $this->getMockBuilder(stdClass::class)->addMethods(['makeInline', 'asSuccess', 'withTitle', 'addToBody', 'defer'])->getMock();
        foreach (['makeInline', 'asSuccess', 'withTitle', 'addToBody', 'defer'] as $method) {
            $alerts->method($method)->willReturnSelf();
        }
        ee()->setMock('CP/Alert', $alerts);

        $config = $this->getMockBuilder(stdClass::class)->addMethods(['item'])->getMock();
        $config->method('item')->willReturnCallback(function ($key) use ($extensionInstalled) {
            return $key === 'allow_extensions' && $extensionInstalled ? 'y' : false;
        });
        ee()->setMock('config', $config);

        return $fieldtype;
    }

    /**
     * Create the controller without running its CP constructor.
     *
     * @return Addons
     *
     * @throws \ReflectionException
     */
    private function makeController()
    {
        return (new ReflectionClass(Addons::class))->newInstanceWithoutConstructor();
    }

    /**
     * Mock the permission service response for add-on administration.
     *
     * @param bool $allowed
     * @return void
     */
    private function mockAdminAddonPermission($allowed)
    {
        $permission = $this->getMockBuilder(stdClass::class)
            ->addMethods(['can'])
            ->getMock();
        $permission->method('can')->willReturn($allowed);

        ee()->setMock('Permission', $permission);
    }

    /**
     * Mock the request method seen by the controller guard.
     *
     * @param string $method
     * @return void
     */
    private function mockRequestMethod($method)
    {
        $request = $this->getMockBuilder(stdClass::class)
            ->addMethods(['method'])
            ->getMock();
        $request->expects($this->any())
            ->method('method')
            ->willReturn($method);

        ee()->setMock('Request', $request);
    }

    /**
     * Assert denied requests do not start installer setup.
     *
     * @return void
     */
    private function mockNoInstallerSetup()
    {
        $load = $this->getMockBuilder(stdClass::class)
            ->addMethods(['library'])
            ->getMock();
        $load->expects($this->never())->method('library');

        ee()->setMock('load', $load);
    }

    /**
     * Verify the controller exposes only the expected public routes.
     *
     * @return void
     */
    public function testRoutableMethods()
    {
        $controller_methods = array();

        foreach (get_class_methods('ExpressionEngine\Controller\Addons\Addons') as $method) {
            $method = strtolower($method);
            if (strncmp($method, '_', 1) != 0) {
                $controller_methods[] = $method;
            }
        }

        sort($controller_methods);

        $this->assertEquals(array('confirm', 'index', 'install', 'manual', 'remove', 'settings', 'update'), $controller_methods);
    }
}

}
