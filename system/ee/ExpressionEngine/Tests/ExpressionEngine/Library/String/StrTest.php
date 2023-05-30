<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Library\String;

use ExpressionEngine\Library\String\Str;
use PHPUnit\Framework\TestCase;

class StrTest extends TestCase
{
    protected $str;

    public function setUp(): void
    {
        $this->str = new Str();
    }

    /**
     * @dataProvider studlyDataProvider
     */
    public function testStudly($in, $out)
    {
        $this->assertEquals($out, $this->str->studly($in));
    }

    public function studlyDataProvider()
    {
        return array(
            array(' expression', 'Expression'),
            array('expression-engine', 'ExpressionEngine'),
            array('Expression Engine', 'ExpressionEngine'),
            array('Expression  Engine', 'ExpressionEngine'),
            array('Expression  -engine', 'ExpressionEngine'),
            array('expression-engine', 'ExpressionEngine'),
            array('expression_engine', 'ExpressionEngine'),
            array('expression%engine', 'Expression%engine'),
            array('expression~engine', 'Expression~engine'),
            #array('expression-éngine', 'ExpressionÉngine')
        );
    }

    /**
     * @dataProvider path2NsDataProvider
     */
    public function testPath2Ns($in, $out)
    {
        $this->assertEquals($out, $this->str->path2ns($in));
    }

    public function path2NsDataProvider()
    {
        return array(
            array('String\Formatted\Like\This', 'String\Formatted\Like\This'),
            array('String/Formatted/Like/This', 'String\Formatted\Like\This'),
            array('String\Formatted/Like\This', 'String\Formatted\Like\This'),
        );
    }

    /**
     * @dataProvider snakecaseDataProvider
     */
    public function testSnakecase($in, $out)
    {
        $this->assertEquals($out, $this->str->snakecase($in));
    }

    public function snakecaseDataProvider()
    {
        return array(
            array(' Expression', 'expression'),
            array('expression-engine', 'expression_engine'),
            array('Expression Engine', 'expression_engine'),
            array('Expression  Engine', 'expression_engine'),
            array('Expression  -engine', 'expression_engine'),
            array('expression-engine', 'expression_engine'),
            array('expression_engine', 'expression_engine'),
            array('expression%engine', 'expression%engine'),
            array('expression~engine', 'expression~engine'),
            #array('expression-éngine', 'ExpressionÉngine')
        );
    }
}

// EOF
