<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Filter;

use ExpressionEngine\Service\Filter\Custom;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CustomTest extends TestCase
{
    protected $options = array(
        'whatthefoxsay' => 'Ring-ding-ding-ding-dingeringeding!',
        '42' => 'The Answer',
        '9.1' => 'Floating'
    );

    public function tearDown(): void
    {
        unset($_POST['filter_by_custom']);
        unset($_GET['filter_by_custom']);

        m::close();
    }

    public function testDefault()
    {
        $filter = new Custom('filter_by_custom', 'custom', $this->options);
        $this->assertNull($filter->value(), 'The value is NULL by default.');
        $this->assertTrue($filter->isValid(), 'The default is valid');

        $vf = m::mock('ExpressionEngine\Service\View\ViewFactory');
        $url = m::mock('ExpressionEngine\Library\CP\URL');

        $vf->shouldReceive('make->render')->atLeast()->once();
        $url->shouldReceive('removeQueryStringVariable', 'setQueryStringVariable', 'compile')->atLeast()->once();

        $filter->render($vf, $url);
    }

    public function testPOST()
    {
        $_POST['filter_by_custom'] = 'whatthefoxsay';
        $filter = new Custom('filter_by_custom', 'custom', $this->options);
        $this->assertEquals('whatthefoxsay', $filter->value(), 'The value reflects the POSTed value');
        $this->assertTrue($filter->isValid(), 'POSTing "whatthefoxsay" is valid');
    }

    public function testGET()
    {
        $_GET['filter_by_custom'] = 'whatthefoxsay';
        $filter = new Custom('filter_by_custom', 'custom', $this->options);
        $this->assertEquals('whatthefoxsay', $filter->value(), 'The value reflects the GETed value');
        $this->assertTrue($filter->isValid(), 'GETing "whatthefoxsay" is valid');
    }

    public function testPOSTOverGET()
    {
        $_POST['filter_by_custom'] = 'whatthefoxsay';
        $_GET['filter_by_custom'] = 42;
        $filter = new Custom('filter_by_custom', 'custom', $this->options);
        $this->assertEquals('whatthefoxsay', $filter->value(), 'Use POST over GET');
    }

    // Use GET when POST is present but "empty"
    public function testGETWhenPOSTIsEmpty()
    {
        $_POST['filter_by_custom'] = '';
        $_GET['filter_by_custom'] = 42;
        $filter = new Custom('filter_by_custom', 'custom', $this->options);
        $this->assertEquals(42, $filter->value(), 'Use GET when POST is an empty string');

        $_POST['filter_by_custom'] = null;
        $_GET['filter_by_custom'] = 42;
        $filter = new Custom('filter_by_custom', 'custom', $this->options);
        $this->assertEquals(42, $filter->value(), 'Use GET when POST is NULL');

        $_POST['filter_by_custom'] = 0;
        $_GET['filter_by_custom'] = 42;
        $filter = new Custom('filter_by_custom', 'custom', $this->options);
        $this->assertEquals(42, $filter->value(), 'Use GET when POST is 0');

        $_POST['filter_by_custom'] = "0";
        $_GET['filter_by_custom'] = 42;
        $filter = new Custom('filter_by_custom', 'custom', $this->options);
        $this->assertEquals(42, $filter->value(), 'Use GET when POST is "0"');
    }

    /**
     * @dataProvider validityDataProvider
     */
    public function testValidity($submitted, $valid)
    {
        // check with $_POST
        $_POST['filter_by_custom'] = $submitted;
        $filter = new Custom('filter_by_custom', 'custom', $this->options);
        $filter->disableCustomValue();

        if ($valid) {
            $this->assertEquals($submitted, $filter->value());
            $this->assertTrue($filter->isValid(), '"' . $submitted . '" is valid');
        } else {
            $this->assertEquals(null, $filter->value());
            $this->assertFalse($filter->isValid(), '"' . $submitted . '" is invalid');
        }

        // check with $_GET
        unset($_POST['filter_by_custom']);
        $_GET['filter_by_custom'] = $submitted;
        $filter = new Custom('filter_by_custom', 'custom', $this->options);
        $filter->disableCustomValue();

        if ($valid) {
            $this->assertEquals($submitted, $filter->value());
            $this->assertTrue($filter->isValid(), '"' . $submitted . '" is valid');
        } else {
            $this->assertEquals(null, $filter->value());
            $this->assertFalse($filter->isValid(), '"' . $submitted . '" is invalid');
        }

        // if custom values are allowed then everything is valid
        $_GET['filter_by_custom'] = $submitted;
        $filter = new Custom('filter_by_custom', 'custom', $this->options);

        $this->assertEquals($submitted, $filter->value());
        $this->assertTrue($filter->isValid(), '"' . $submitted . '" is valid');
    }

    public function validityDataProvider()
    {
        return array(
            array('whatthefoxsay', true),
            array(42, true),
            array('42', true),
            array('9.1', true),

            // Some missing keys
            array('WhatTheFoxSay', false),
            array('1', false),
            array(-1, false)
        );
    }
}

// EOF
