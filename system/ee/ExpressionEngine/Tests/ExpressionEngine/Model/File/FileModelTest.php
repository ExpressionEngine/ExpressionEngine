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
     * Assert unavailable or invalid dimensions return null as an atomic pair.
     *
     * @dataProvider invalidOriginalDimensionsProvider
     * @return void
     */
    public function testInvalidOriginalFileDimensionsReturnNull($dimensions)
    {
        $file = new FileModel();
        $file->setRawProperty('file_hw_original', $dimensions);

        $this->assertNull($file->width);
        $this->assertNull($file->height);
    }

    public static function invalidOriginalDimensionsProvider()
    {
        return array(
            'empty' => array(''),
            'null' => array(null),
            'height only' => array('480'),
            'non-numeric width' => array('480 wide'),
            'extra component' => array('480 640 extra'),
            'zero height' => array('0 640'),
            'zero width' => array('480 0'),
            'negative height' => array('-480 640'),
            'negative width' => array('480 -640'),
        );
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
