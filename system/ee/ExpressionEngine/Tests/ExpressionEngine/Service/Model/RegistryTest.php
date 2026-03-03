<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Model;

use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Service\Model\Registry;
use ExpressionEngine\Service\Model\RegistryClassExistsShim;
use PHPUnit\Framework\TestCase;

class RegistryTest extends TestCase
{
    public function testModelExistsIsEnabledExpandAliasAndPrefix()
    {
        $aliases = array(
            'ee:test' => RegistryModelStub::class,
            'addon:test' => RegistryModelStub::class,
        );

        $registry = new Registry($aliases, 'ee', array('ee', 'addon'));

        $this->assertTrue($registry->modelExists('ee:test'));
        $this->assertFalse($registry->modelExists('ee:missing'));

        $this->assertTrue($registry->isEnabled('ee:test'));
        $this->assertTrue($registry->isEnabled('addon:test'));
        $this->assertTrue($registry->isEnabled('test'), 'default prefix should be used');

        $disabled = new Registry($aliases, 'ee', array('addon'));
        $this->assertFalse($disabled->isEnabled('ee:test'));

        $this->assertSame(RegistryModelStub::class, $registry->expandAlias('test'));
        $this->assertSame(RegistryModelStub::class, $registry->expandAlias('ee:test'));

        $this->assertSame('addon', $registry->getPrefix('addon:test'));
        $this->assertSame('ee', $registry->getPrefix('test'));
    }

    public function testExpandAliasThrowsForUnknownModel()
    {
        $registry = new Registry(array(), 'ee', array('ee'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown model: ee:missing');
        $registry->expandAlias('missing');
    }

    public function testExpandAliasReturnsGivenNameWhenAliasMissingAndClassExists()
    {
        $registry = new Registry(array(), 'ee', array('ee'));
        RegistryClassExistsShim::$existing['custom:model'] = true;

        $this->assertSame('custom:model', $registry->expandAlias('custom:model'));

        RegistryClassExistsShim::$existing = array();
    }

    public function testGetMetaDataReaderCachesPerClassAndName()
    {
        $aliases = array(
            'ee:test' => RegistryModelStub::class,
            'addon:test' => RegistryModelStub::class,
        );
        $registry = new Registry($aliases, 'ee', array('ee', 'addon'));

        $first = $registry->getMetaDataReader('ee:test');
        $second = $registry->getMetaDataReader('ee:test');
        $third = $registry->getMetaDataReader('addon:test');

        $this->assertSame($first, $second, 'same model name should reuse reader');
        $this->assertNotSame($first, $third, 'same class with different alias should refresh reader cache entry');
        $this->assertSame('addon:test', $third->getName());
    }
}

class RegistryModelStub extends Model
{
    protected static $_primary_key = 'id';

    protected $id;
}

namespace ExpressionEngine\Service\Model;

class RegistryClassExistsShim
{
    public static $existing = array();
}

function class_exists($name, $autoload = true)
{
    if (isset(RegistryClassExistsShim::$existing[$name])) {
        return RegistryClassExistsShim::$existing[$name];
    }

    return \class_exists($name, $autoload);
}

// EOF
