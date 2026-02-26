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

use ExpressionEngine\Service\Model\Configuration;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testConfigurationAccessorsAndPrefixChecks()
    {
        $config = new Configuration();

        $this->assertNull($config->getDefaultPrefix());
        $this->assertSame(array(), $config->getEnabledPrefixes());
        $this->assertSame(array(), $config->getModelDependencies());
        $this->assertSame(array(), $config->getModelAliases());

        $config->setDefaultPrefix('ee');
        $this->assertSame('ee', $config->getDefaultPrefix());

        $config->setEnabledPrefixes(array('ee', 'addon'));
        $this->assertSame(array('ee', 'addon'), $config->getEnabledPrefixes());
        $this->assertTrue($config->isEnabledPrefix('ee'));
        $this->assertFalse($config->isEnabledPrefix('missing'));

        $dependencies = array(
            'ChannelEntry' => array('Channel'),
            'Template' => array('TemplateGroup'),
        );
        $config->setModelDependencies($dependencies);
        $this->assertSame($dependencies, $config->getModelDependencies());

        $aliases = array(
            'entry' => 'ChannelEntry',
            'member' => 'Member',
        );
        $config->setModelAliases($aliases);
        $this->assertSame($aliases, $config->getModelAliases());
    }
}

// EOF
