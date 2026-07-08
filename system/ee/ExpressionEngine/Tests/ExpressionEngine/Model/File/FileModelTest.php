<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Model\File;

use ExpressionEngine\Model\File\File as FileModel;
use PHPUnit\Framework\TestCase;

class FileModelTest extends TestCase
{
    public function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testGetValuesDoesNotThrowWhenFileDimensionsCannotBeRead()
    {
        $file = new FileWithUnreadableRootStub();
        $file->setRawProperty('file_name', 'avatar.png');

        $values = $file->getValues();

        $this->assertArrayHasKey('file_hw_original', $values);
        $this->assertNull($values['file_hw_original']);
    }

    /**
     * Assert image dimensions are split into width and height accessors.
     *
     * @return void
     */
    public function testDimensionsAreReadFromOriginalFileDimensions()
    {
        $file = new FileModel();
        $file->setRawProperty('file_hw_original', '480 640');

        $this->assertSame('640', $file->width);
        $this->assertSame('480', $file->height);
    }

    /**
     * Assert missing image dimensions do not raise PHP warnings.
     *
     * @return void
     */
    public function testDimensionsUseEmptyStringsWhenOriginalFileDimensionsAreUnavailable()
    {
        $file = new FileModel();

        $file->setRawProperty('file_hw_original', '');
        $this->assertSame('', $file->width);
        $this->assertSame('', $file->height);

        $file->setRawProperty('file_hw_original', null);
        $this->assertSame('', $file->width);
        $this->assertSame('', $file->height);
    }
}

class FileWithUnreadableRootStub extends FileModel
{
    public function actLocally(callable $callback)
    {
        throw new \LogicException('The root path is not readable.');
    }
}

// EOF
