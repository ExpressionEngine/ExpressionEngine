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

use PHPUnit\Framework\TestCase;

class VariableColumnModelTest extends TestCase
{
    public function testHasPropertyRules()
    {
        $model = new VariableColumnModelTestStub();

        $this->assertTrue($model->hasProperty('id'));
        $this->assertTrue($model->hasProperty('dynamic'));
        $this->assertFalse($model->hasProperty(''));
        $this->assertFalse($model->hasProperty('_private'));
    }

    public function testFillPropertyHandlesDeclaredAndVariableColumns()
    {
        $model = new VariableColumnModelTestStub();
        $model->addFilter('fill', function ($value, $key) {
            return $key === 'dynamic' ? strtoupper($value) : $value;
        });

        $model->fillProperty('id', 7);
        $this->assertSame(7, $model->getRawProperty('id'));

        $model->fillProperty('dynamic', 'value');
        $this->assertSame('VALUE', $model->getRawProperty('dynamic'));

        $model->fillProperty('_private', 'ignored');
        $this->assertNull($model->getRawProperty('_private'));
    }

    public function testSetRawPropertyTracksChangesOnDeclaredAndVariableColumns()
    {
        $model = new VariableColumnModelTestStub();
        $this->assertFalse($model->isDirty());

        $model->setRawProperty('id', 10);
        $this->assertSame(10, $model->getRawProperty('id'));
        $this->assertTrue($model->isDirty('id'));

        $model->setRawProperty('dynamic', 'abc');
        $this->assertSame('abc', $model->getRawProperty('dynamic'));
        $this->assertTrue($model->isDirty('dynamic'));
    }

    public function testGetValuesMergesDeclaredAndVariableProperties()
    {
        $model = new VariableColumnModelTestStub();
        $model->setRawProperty('id', 22);
        $model->setRawProperty('dynamic', 'hello');

        $this->assertSame(
            array(
                'id' => 22,
                'dynamic' => 'hello',
            ),
            $model->getValues()
        );
    }
}

class VariableColumnModelTestStub extends \ExpressionEngine\Service\Model\VariableColumnModel
{
    protected static $_primary_key = 'id';

    protected $id;
}

// EOF
