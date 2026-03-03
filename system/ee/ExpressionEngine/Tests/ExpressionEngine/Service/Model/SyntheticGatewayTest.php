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
use ExpressionEngine\Service\Model\SyntheticGateway;
use PHPUnit\Framework\TestCase;

class SyntheticGatewayTest extends TestCase
{
    public function testSyntheticGatewayUsesModelMetadata()
    {
        $gateway = new SyntheticGateway('synthetic_table', SyntheticGatewayModelStub::class);

        $this->assertSame('synthetic_table', $gateway->getTableName());
        $this->assertSame(array('id', 'title'), $gateway->getFieldList());
        $this->assertSame('id', $gateway->getPrimaryKey());
    }
}

class SyntheticGatewayModelStub extends Model
{
    protected static $_primary_key = 'id';

    protected $id;
    protected $title;
}

// EOF
