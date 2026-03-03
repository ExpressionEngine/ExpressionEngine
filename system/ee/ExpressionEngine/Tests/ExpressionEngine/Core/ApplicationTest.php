<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Core;

use ExpressionEngine\Core\Application;
use ExpressionEngine\Core\Autoloader;
use ExpressionEngine\Core\Provider;
use ExpressionEngine\Core\ProviderRegistry;
use ExpressionEngine\Core\Request;
use ExpressionEngine\Core\Response;
use ExpressionEngine\Service\Dependency\ServiceProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ApplicationTest extends TestCase
{
    private $autoloader;
    private $dependencies;
    private $registry;
    private $application;
    private $tempDirectories = array();

    protected function setUp(): void
    {
        $this->autoloader = $this->createMock(Autoloader::class);
        $this->dependencies = $this->createMock(ServiceProvider::class);
        $this->registry = $this->createMock(ProviderRegistry::class);

        $this->application = new Application(
            $this->autoloader,
            $this->dependencies,
            $this->registry
        );
    }

    protected function tearDown(): void
    {
        foreach ($this->tempDirectories as $directory) {
            $this->removeDirectory($directory);
        }
    }

    public function testSetAndGetRequestRoundTrip()
    {
        $request = $this->createMock(Request::class);

        $this->application->setRequest($request);

        $this->assertSame($request, $this->application->getRequest());
    }

    public function testSetAndGetResponseRoundTrip()
    {
        $response = $this->createMock(Response::class);

        $this->application->setResponse($response);

        $this->assertSame($response, $this->application->getResponse());
    }

    public function testGetDependenciesReturnsInjectedServiceProvider()
    {
        $this->assertSame($this->dependencies, $this->application->getDependencies());
    }

    public function testHasDelegatesToRegistry()
    {
        $this->registry
            ->expects($this->once())
            ->method('has')
            ->with('sample')
            ->willReturn(true);

        $this->assertTrue($this->application->has('sample'));
    }

    public function testGetDelegatesToRegistry()
    {
        $provider = $this->createMock(Provider::class);

        $this->registry
            ->expects($this->once())
            ->method('get')
            ->with('sample')
            ->willReturn($provider);

        $this->assertSame($provider, $this->application->get('sample'));
    }

    public function testGetPrefixesUsesRegistryKeys()
    {
        $this->registry
            ->expects($this->once())
            ->method('all')
            ->willReturn(array('first' => new \stdClass(), 'second' => new \stdClass()));

        $this->assertSame(array('first', 'second'), $this->application->getPrefixes());
    }

    public function testGetProvidersReturnsRegistryValues()
    {
        $providers = array('first' => new \stdClass());

        $this->registry
            ->expects($this->once())
            ->method('all')
            ->willReturn($providers);

        $this->assertSame($providers, $this->application->getProviders());
    }

    public function testGetNamespacesForwardsGetNamespace()
    {
        $application = $this->buildForwardMock('getNamespace', array('one' => 'Example\\One'));

        $this->assertSame(array('one' => 'Example\\One'), $application->getNamespaces());
    }

    public function testGetProductsForwardsGetProduct()
    {
        $application = $this->buildForwardMock('getProduct', array('one' => 'Example Product'));

        $this->assertSame(array('one' => 'Example Product'), $application->getProducts());
    }

    public function testGetModelsForwardsGetModels()
    {
        $application = $this->buildForwardMock('getModels', array('one:model' => 'ModelClass'));

        $this->assertSame(array('one:model' => 'ModelClass'), $application->getModels());
    }

    public function testSetClassAliasesForwardsSetClassAliases()
    {
        $application = $this->buildForwardMock('setClassAliases', array());

        $application->setClassAliases();

        $this->assertTrue(true);
    }

    public function testGetVendorsReturnsUniqueForwardedKeys()
    {
        $application = $this->buildForwardMock('getVendor', array('vendor_a' => 'Vendor A', 'vendor_b' => 'Vendor B'));

        $this->assertSame(array('vendor_a', 'vendor_b'), $application->getVendors());
    }

    public function testForwardFlattensArrayAndScalarProviderResults()
    {
        $providerA = new class {
            public function values()
            {
                return array('first' => 'alpha', 'second' => 'beta');
            }
        };

        $providerB = new class {
            public function values()
            {
                return 'gamma';
            }
        };

        $this->registry
            ->expects($this->once())
            ->method('all')
            ->willReturn(array('a' => $providerA, 'b' => $providerB));

        $this->assertSame(
            array('a:first' => 'alpha', 'a:second' => 'beta', 'b' => 'gamma'),
            $this->application->forward('values')
        );
    }

    public function testSetupAddonsAddsOnlyDirectoriesContainingSetupFile()
    {
        $directory = $this->createTempDirectory();
        $firstAddon = $directory . '/addon_one';
        $secondAddon = $directory . '/addon_two';

        mkdir($firstAddon);
        mkdir($secondAddon);
        file_put_contents($directory . '/README.txt', 'root file');
        $this->writeAddonSetupFile($firstAddon);

        $application = new ApplicationSetupHarness(
            $this->autoloader,
            $this->dependencies,
            $this->registry
        );

        $application->setupAddons($directory);

        $this->assertSame(array($firstAddon), $application->addedPaths);
    }

    public function testAddProviderThrowsWhenSetupFileDoesNotExist()
    {
        $path = $this->createTempDirectory() . '/missing-addon';
        mkdir($path);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Cannot read setup file: {$path}");

        $this->application->addProvider($path);
    }

    public function testAddProviderReturnsExistingProLevelupsProviderWhenPrefixAlreadyRegistered()
    {
        $path = $this->createTempDirectory() . '/sample';
        mkdir($path);
        $this->writeAddonSetupFile($path);

        $existingProvider = $this->createMock(Provider::class);
        $existingProvider->method('getPath')->willReturn('/tmp/ExpressionEngine/Addons/pro/levelups/sample');

        $this->registry
            ->expects($this->once())
            ->method('has')
            ->with('sample')
            ->willReturn(true);

        $this->registry
            ->expects($this->once())
            ->method('get')
            ->with('sample')
            ->willReturn($existingProvider);

        $this->registry->expects($this->never())->method('register');

        $result = $this->application->addProvider($path);

        $this->assertSame($existingProvider, $result);
    }

    public function testAddProviderReturnsExistingFirstPartyProviderWhenPrefixAlreadyRegistered()
    {
        $path = $this->createTempDirectory() . '/sample';
        mkdir($path);
        $this->writeAddonSetupFile($path);

        $existingProvider = $this->createMock(Provider::class);
        $existingProvider->method('getPath')->willReturn('/tmp/system/ee/ExpressionEngine/Addons/sample');

        $this->registry
            ->expects($this->once())
            ->method('has')
            ->with('sample')
            ->willReturn(true);

        $this->registry
            ->expects($this->once())
            ->method('get')
            ->with('sample')
            ->willReturn($existingProvider);

        $this->registry->expects($this->never())->method('register');

        $result = $this->application->addProvider($path);

        $this->assertSame($existingProvider, $result);
    }

    public function testAddProviderBuildsAndRegistersProviderUsingDefaultPrefix()
    {
        $path = $this->createTempDirectory() . '/sample-addon';
        mkdir($path);
        $this->writeAddonSetupFile($path, 'ExpressionEngine\\Addons\\SampleAddon');

        $this->registry
            ->expects($this->once())
            ->method('has')
            ->with('sample-addon')
            ->willReturn(false);

        $this->registry
            ->expects($this->once())
            ->method('register')
            ->with(
                'sample-addon',
                $this->callback(function ($provider) use ($path) {
                    return $provider instanceof Provider
                        && $provider->getPrefix() === 'sample-addon'
                        && $provider->getPath() === $path;
                })
            );

        $this->autoloader
            ->expects($this->once())
            ->method('addPrefix')
            ->with('ExpressionEngine\\Addons\\SampleAddon', $path);

        $provider = $this->application->addProvider($path . '/');

        $this->assertInstanceOf(Provider::class, $provider);
        $this->assertSame('sample-addon', $provider->getPrefix());
        $this->assertSame($path, $provider->getPath());
    }

    public function testAddProviderUsesExplicitPrefixWhenProvided()
    {
        $path = $this->createTempDirectory() . '/sample-addon';
        mkdir($path);
        $this->writeAddonSetupFile($path, 'ExpressionEngine\\Addons\\CustomNamedAddon');

        $this->registry
            ->expects($this->once())
            ->method('has')
            ->with('custom-prefix')
            ->willReturn(false);

        $this->registry
            ->expects($this->once())
            ->method('register')
            ->with(
                'custom-prefix',
                $this->callback(function ($provider) {
                    return $provider instanceof Provider
                        && $provider->getPrefix() === 'custom-prefix';
                })
            );

        $this->autoloader
            ->expects($this->once())
            ->method('addPrefix')
            ->with('ExpressionEngine\\Addons\\CustomNamedAddon', $path);

        $provider = $this->application->addProvider($path, 'addon.setup.php', 'custom-prefix');

        $this->assertInstanceOf(Provider::class, $provider);
        $this->assertSame('custom-prefix', $provider->getPrefix());
    }

    private function buildForwardMock($forwardedMethod, $returnValue)
    {
        $application = $this->getMockBuilder(Application::class)
            ->setConstructorArgs(array($this->autoloader, $this->dependencies, $this->registry))
            ->onlyMethods(array('forward'))
            ->getMock();

        $application->expects($this->once())
            ->method('forward')
            ->with($forwardedMethod)
            ->willReturn($returnValue);

        return $application;
    }

    private function createTempDirectory()
    {
        $directory = rtrim(sys_get_temp_dir(), '/') . '/ee-application-test-' . uniqid('', true);
        mkdir($directory, 0777, true);
        $this->tempDirectories[] = $directory;

        return $directory;
    }

    private function writeAddonSetupFile($directory, $namespace = 'ExpressionEngine\\Addons\\Sample')
    {
        $content = sprintf(
            "<?php\nreturn array(\n    'name' => 'Sample Addon',\n    'namespace' => '%s',\n);\n",
            addslashes($namespace)
        );

        file_put_contents($directory . '/addon.setup.php', $content);
    }

    private function removeDirectory($directory)
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($directory);
    }
}

class ApplicationSetupHarness extends Application
{
    public $addedPaths = array();

    public function addProvider($path, $file = 'addon.setup.php', $prefix = null)
    {
        $this->addedPaths[] = $path;
    }
}

