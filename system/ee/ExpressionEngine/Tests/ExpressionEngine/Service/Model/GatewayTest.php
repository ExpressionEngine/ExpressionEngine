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

use ExpressionEngine\Service\Model\Gateway;
use PHPUnit\Framework\TestCase;

class GatewayTest extends TestCase
{
    public function testGatewayMetadataAndFieldListCaching()
    {
        $gateway = new GatewayTestStub();

        $this->assertSame('exp_test_table', $gateway->getTableName());
        $this->assertSame('id', $gateway->getPrimaryKey());
        $this->assertSame('TestModel', $gateway->getGatewayModel());

        $fields = $gateway->getFieldList();
        $this->assertContains('id', $fields);
        $this->assertContains('title', $fields);
        $this->assertNotContains('_internal', $fields);

        // second call uses cached field list
        $this->assertSame($fields, $gateway->getFieldList());
    }

    public function testHasFieldSetFieldFillAndGetValues()
    {
        $gateway = new GatewayTestStub();

        $this->assertTrue($gateway->hasField('title'));
        $this->assertFalse($gateway->hasField('missing'));

        $gateway->setField('title', 'Original Title');
        $this->assertSame(array('title' => 'Original Title'), $gateway->getValues());

        $gateway->fill(array(
            'id' => 42,
            'title' => 'Updated Title',
            'missing' => 'ignore me',
        ));

        $this->assertSame(42, $gateway->getId());
        $this->assertSame(
            array(
                'title' => 'Updated Title',
                'id' => 42,
            ),
            $gateway->getValues()
        );
    }
}

class GatewayTestStub extends Gateway
{
    protected static $_table_name = 'exp_test_table';
    protected static $_primary_key = 'id';
    protected static $_gateway_model = 'TestModel';

    public $id;
    public $title;
    protected $_internal;
}

// EOF
