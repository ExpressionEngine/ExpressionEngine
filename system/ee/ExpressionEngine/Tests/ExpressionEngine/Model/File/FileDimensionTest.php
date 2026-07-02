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

use ExpressionEngine\Model\File\FileDimension;
use ExpressionEngine\Service\Validation\Validator;
use PHPUnit\Framework\TestCase;

class FileDimensionTest extends TestCase
{
    public function testReservedShortNameFailsThroughModelValidationRule()
    {
        $dimension = new FileDimension();
        $dimension->setValidator(new Validator());
        $dimension->setProperty('short_name', 'thumbs');
        $dimension->setProperty('resize_type', 'constrain');
        $dimension->setProperty('width', 100);
        $dimension->setProperty('height', 100);
        $dimension->setProperty('quality', 100);

        $result = $dimension->validate();

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors('short_name'));
    }

    /**
     * @dataProvider reservedShortNameProvider
     */
    public function testBuiltInManipulationFolderNamesAreReserved($shortName)
    {
        $dimension = new FileDimension();

        $result = $dimension->validateShortNameIsNotReserved('short_name', $shortName, [], null);

        $this->assertSame(lang('invalid_short_name'), $result);
    }

    public function reservedShortNameProvider()
    {
        return [
            ['thumbs'],
            ['Thumbs'],
            ['resize'],
            ['crop'],
            ['rotate'],
            ['webp'],
            ['avif'],
        ];
    }

    public function testCustomManipulationFolderNamesAreAllowed()
    {
        $dimension = new FileDimension();

        $this->assertTrue($dimension->validateShortNameIsNotReserved('short_name', 'hero_small', [], null));
    }
}

// EOF
