<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\TemplateGenerator;

use Mockery as m;
use ExpressionEngine\Service\TemplateGenerator\Factory;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    private $autoloader;
    private $dependencies;
    private $registry;

    public function setUp(): void
    {
        ee()->setMock('TemplateGenerator', new Factory());

        $this->autoloader = new \ExpressionEngine\Core\Autoloader();
        $this->dependencies = new \ExpressionEngine\Service\Dependency\InjectionContainer();
        $this->registry = new \ExpressionEngine\Core\ProviderRegistry($this->dependencies);
        ee()->setMock('App', new \ExpressionEngine\Core\Application($this->autoloader, $this->dependencies, $this->registry));
        $this->dependencies->register('ee:CookieRegistry', new \ExpressionEngine\Service\Consent\CookieRegistry());
        $path = SYSPATH . 'ee/ExpressionEngine/Addons/channel/';
        $provider = new \ExpressionEngine\Core\Provider(
            $this->dependencies,
            $path,
            require $path . 'addon.setup.php'
        );
        $provider->setPrefix('channel');
        $provider->setAutoloader($this->autoloader);
        $this->registry->register('channel', $provider);
    }

    public function tearDown(): void
    {
        m::close();
        ee()->resetMocks();
    }

    public function testSetOptionValues()
    {
        $this->assertEquals(1, ee('TemplateGenerator')->site_id);
        $this->assertNull(ee('TemplateGenerator')->templateEngine);

        ee('TemplateGenerator')->setOptionValues([
            'foo' => 'bar',
            'baz' => 'qux',
            'site_id' => 5,
            'template_engine' => 'twig'
        ]);

        $this->assertEquals([
            'foo' => 'bar',
            'baz' => 'qux',
            'site_id' => 5,
            'template_engine' => 'twig'
        ], ee('TemplateGenerator')->getOptionValues());
        $this->assertEquals(5, ee('TemplateGenerator')->site_id);
        $this->assertEquals('twig', ee('TemplateGenerator')->templateEngine);
    }

    public function testSetGenerator()
    {
        ee('TemplateGenerator')->setGenerator('channel:entries');

        $this->assertInstanceOf('ExpressionEngine\Service\TemplateGenerator\RegisteredGenerator', ee('TemplateGenerator')->getGenerator());

        $this->assertEquals('channel', ee('TemplateGenerator')->getGenerator()->prefix);
        $this->assertEquals('Entries', ee('TemplateGenerator')->getGenerator()->className);
        $this->assertEquals('ExpressionEngine\Addons\Channel\TemplateGenerators\Entries', ee('TemplateGenerator')->getGenerator()->fqcn);
    }

    public function testFailsSetGenerator()
    {
        $this->expectException(\Exception::class);
        ee('TemplateGenerator')->setGenerator('something:fake');
    }

    public function testListGenerators()
    {
        $this->assertIsArray(ee('TemplateGenerator')->listGenerators());
        $this->assertArrayHasKey('channel:entries', ee('TemplateGenerator')->listGenerators());
    }

    public function testFailGetValidationRules()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Template Generator is required');
        ee('TemplateGenerator')->getValidationRules();
    }

    public function testGetValidationRules()
    {
        ee('TemplateGenerator')->setGenerator('channel:entries');
        $this->assertIsArray(ee('TemplateGenerator')->getValidationRules());
        $this->assertArrayHasKey('template_group', ee('TemplateGenerator')->getValidationRules());
    }

    public function testGetTemplateEnginesList()
    {
        require_once APPPATH . 'libraries/api/Api_template_structure.php';
        $reflection = new \ReflectionClass(\Api_template_structure::class);
        ee()->setMock('api_template_structure', $reflection->newInstanceWithoutConstructor());
        $list = ee('TemplateGenerator')->getTemplateEnginesList();
        $this->assertIsArray($list);
        $this->assertContains('Native', $list);
    }

    public function testFailGetTemplatesList()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Template Generator is required');
        ee('TemplateGenerator')->getTemplatesList();
    }


    public function testGetTemplatesList()
    {
        ee('TemplateGenerator')->setGenerator('channel:entries');
        $this->assertIsArray(ee('TemplateGenerator')->getTemplatesList());
        $this->assertArrayHasKey('all', ee('TemplateGenerator')->getTemplatesList());
        $this->assertArrayHasKey('index', ee('TemplateGenerator')->getTemplatesList());
        $this->assertArrayHasKey('entry', ee('TemplateGenerator')->getTemplatesList());
    }

    public function testGenerate()
    {
        ee()->setMock('Addon', new \ExpressionEngine\Service\Addon\Factory(new \ExpressionEngine\Core\Application($this->autoloader, $this->dependencies, $this->registry)));
        ee()->setMock('addons_model', new thisAddonsModelMock());
        ee()->setMock('Model', new thisModelMock());
        ee()->setMock('View', new thisViewFactoryMock());
        ee('TemplateGenerator')->setGenerator('channel:entries');
        ee('TemplateGenerator')->setOptionValues([
            'template_group' => 'foo',
            'channel' => 'news',
            'template_engine' => 'native',
            'site_id' => 1
        ]);
        $template = ee('TemplateGenerator')->generate('index');
        $this->assertIsString($template);
    }

}

class thisAddonsModelMock
{
    public function get_installed_modules()
    {
        return new \eeDbResultMock([
            [
                'module_name' => 'Channel',
                'module_version' => '2.11.0',
                'has_cp_backend' => 'y',
                'has_publish_fields' => 'y'
            ]
        ]);
    }
    public function get_installed_extensions()
    {
        return new \eeDbResultMock();
    }
}

class thisModelMock
{
    public function get($name)
    {
        $collection = m::mock('ExpressionEngine\Service\Model\Collection');
        $collection->shouldReceive('filter')->andReturnSelf()->atLeast()->once();
        $collection->shouldReceive('all')->atLeast()->once();
        return $collection;
    }
}

class thisViewFactoryMock
{
    public function makeStub($name)
    {
        $stub = m::mock('ExpressionEngine\Service\View\Stub');
        $stub->shouldReceive('render')->andReturn('rendered template')->once();
        return $stub;
    }
}