<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\ChannelSet;

use ExpressionEngine\Service\ChannelSet\ImportResult;
use PHPUnit\Framework\TestCase;

class ImportResultTest extends TestCase
{
    private $result;

    public function setUp(): void
    {
        $this->result = new ImportResult();
    }

    public function tearDown(): void
    {
        $this->result = null;
    }

    public function testAddErrorMakesImportInvalid()
    {
        $this->assertTrue($this->result->isValid());
        $this->assertTrue($this->result->isRecoverable());

        $this->result->addError('Test error');

        $this->assertFalse($this->result->isValid());
        $this->assertFalse($this->result->isRecoverable());
        $this->assertEquals(['Test error'], $this->result->getErrors());
    }

    public function testAddModelErrorWithNonRecoverableField()
    {
        $this->assertTrue($this->result->isValid());

        $mockModel = $this->getMockBuilder('stdClass')->addMethods(['getName'])->getMock();
        $mockModel->method('getName')->willReturn('ee:ChannelField');

        $this->result->addModelError('Channel Field', $mockModel, 'invalid_field', ['required' => 'Field is required']);

        $this->assertFalse($this->result->isValid());
        $this->assertFalse($this->result->isRecoverable());
        $this->assertEquals([], $this->result->getRecoverableErrors());
        $this->assertNotEmpty($this->result->getModelErrors());
    }

    public function testAddModelErrorWithRecoverableChannelField()
    {
        $this->assertTrue($this->result->isValid());

        $mockModel = $this->getMockBuilder('stdClass')->addMethods(['getName'])->getMock();
        $mockModel->method('getName')->willReturn('ee:ChannelField');
        $mockModel->field_name = 'test_field';

        $this->result->addModelError('Channel Field', $mockModel, 'field_name', ['required' => 'Field name is required']);

        $this->assertFalse($this->result->isValid());
        $this->assertTrue($this->result->isRecoverable());
        $this->assertNotEmpty($this->result->getRecoverableErrors());
        $this->assertEmpty($this->result->getModelErrors());
    }

    public function testAddModelErrorWithRecoverableChannel()
    {
        $this->assertTrue($this->result->isValid());

        $mockModel = $this->getMockBuilder('stdClass')->addMethods(['getName'])->getMock();
        $mockModel->method('getName')->willReturn('ee:Channel');
        $mockModel->channel_name = 'test_channel';

        $this->result->addModelError('Channel', $mockModel, 'channel_title', ['required' => 'Channel title is required']);

        $this->assertFalse($this->result->isValid());
        $this->assertTrue($this->result->isRecoverable());
        $this->assertNotEmpty($this->result->getRecoverableErrors());
        $this->assertEmpty($this->result->getModelErrors());
    }

    public function testGetRecoverableErrorsStructure()
    {
        $mockModel = $this->getMockBuilder('stdClass')->addMethods(['getName'])->getMock();
        $mockModel->method('getName')->willReturn('ee:Channel');
        $mockModel->channel_name = 'test_channel';

        $this->result->addModelError('Channel', $mockModel, 'channel_title', ['required' => 'Channel title is required']);

        $recoverableErrors = $this->result->getRecoverableErrors();

        $this->assertArrayHasKey('Channel', $recoverableErrors);
        $this->assertCount(1, $recoverableErrors['Channel']);
        $this->assertEquals([$mockModel, 'channel_title', 'test_channel', ['required' => 'Channel title is required']], $recoverableErrors['Channel'][0]);
    }

    public function testGetModelErrorsStructure()
    {
        $mockModel = $this->getMockBuilder('stdClass')->addMethods(['getName'])->getMock();
        $mockModel->method('getName')->willReturn('ee:ChannelField');

        $this->result->addModelError('Channel Field', $mockModel, 'invalid_field', ['required' => 'Field is required']);

        $modelErrors = $this->result->getModelErrors();

        $this->assertArrayHasKey('Channel Field', $modelErrors);
        $this->assertCount(1, $modelErrors['Channel Field']);
        $this->assertEquals([$mockModel, 'invalid_field', ['required' => 'Field is required']], $modelErrors['Channel Field'][0]);
    }

    public function testMultipleErrors()
    {
        // Add recoverable error
        $mockModel1 = $this->getMockBuilder('stdClass')->addMethods(['getName'])->getMock();
        $mockModel1->method('getName')->willReturn('ee:Channel');
        $mockModel1->channel_name = 'test_channel';
        $this->result->addModelError('Channel', $mockModel1, 'channel_title', ['required' => 'Channel title is required']);

        // Add non-recoverable error
        $mockModel2 = $this->getMockBuilder('stdClass')->addMethods(['getName'])->getMock();
        $mockModel2->method('getName')->willReturn('ee:ChannelField');
        $this->result->addModelError('Channel Field', $mockModel2, 'invalid_field', ['required' => 'Field is required']);

        // Add general error
        $this->result->addError('General error');

        $this->assertFalse($this->result->isValid());
        $this->assertFalse($this->result->isRecoverable());
        $this->assertCount(1, $this->result->getRecoverableErrors());
        $this->assertCount(1, $this->result->getModelErrors());
        $this->assertCount(1, $this->result->getErrors());
    }

    public function testIsRecoverableReturnsTrueWhenOnlyRecoverableErrors()
    {
        $mockModel = $this->getMockBuilder('stdClass')->addMethods(['getName'])->getMock();
        $mockModel->method('getName')->willReturn('ee:Channel');
        $mockModel->channel_name = 'test_channel';

        $this->result->addModelError('Channel', $mockModel, 'channel_title', ['required' => 'Channel title is required']);

        $this->assertFalse($this->result->isValid());
        $this->assertTrue($this->result->isRecoverable());
    }

    public function testIsRecoverableReturnsFalseWhenGeneralErrorsExist()
    {
        $this->result->addError('General error');

        $this->assertFalse($this->result->isValid());
        $this->assertFalse($this->result->isRecoverable());
    }

    public function testProxyMethodsToStructure()
    {
        $mockModel = $this->getMockBuilder('stdClass')->addMethods(['getName'])->getMock();
        $mockModel->method('getName')->willReturn('ee:Channel');

        // Test getLongFieldIfShortened proxy
        // This would normally call Structure::getLongFieldIfShortened, but we're testing the proxy exists
        $this->assertNull($this->result->getLongFieldIfShortened($mockModel, 'title'));

        // Test getTitleFieldFor proxy
        // This would normally call Structure::getTitleFieldFor, but we're testing the proxy exists
        try {
            $this->result->getTitleFieldFor($mockModel);
        } catch (\Exception $e) {
            // Expected to throw if model doesn't have title field defined
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }
}
