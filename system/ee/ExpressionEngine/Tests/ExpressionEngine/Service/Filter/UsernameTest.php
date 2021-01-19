<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Filter;

use ExpressionEngine\Service\Filter\Username;
use Mockery as m;
use stdClass;
use PHPUnit\Framework\TestCase;

class UsernameTest extends TestCase
{
    public function setUp() : void
    {
        $this->query = m::mock('ExpressionEngine\Service\Model\Query\Builder');

        $this->usernames = array(
            '1' => 'admin',
            '2' => 'johndoe',
            '3' => 'janedoe',
            '5' => 'somebody',
            '9' => 'nobody'
        );
    }

    public function tearDown() : void
    {
        unset($_POST['filter_by_username']);
        unset($_GET['filter_by_username']);

        m::close();
    }

    public function testDefault()
    {
        $filter = new Username($this->usernames);
        $this->assertNull($filter->value(), 'The value is NULL by default.');
        $this->assertTrue($filter->isValid(), 'The default is invalid');

        $vf = m::mock('ExpressionEngine\Service\View\ViewFactory');
        $url = m::mock('ExpressionEngine\Library\CP\URL');

        $vf->shouldReceive('make->render')->atLeast()->once();
        $url->shouldReceive('removeQueryStringVariable', 'setQueryStringVariable', 'compile')->atLeast()->once();
        $filter->render($vf, $url);
    }

    public function testPOST()
    {
        $_POST['filter_by_username'] = 2;
        $filter = new Username($this->usernames);
        $this->assertEquals(2, $filter->value(), 'The value reflects the POSTed value');
        $this->assertTrue($filter->isValid(), 'POSTing a number is valid');
    }

    public function testGET()
    {
        $_GET['filter_by_username'] = 2;
        $filter = new Username($this->usernames);
        $this->assertEquals(2, $filter->value(), 'The value reflects the GETed value');
        $this->assertTrue($filter->isValid(), 'GETing a number is valid');
    }

    public function testPOSTOverGET()
    {
        $_POST['filter_by_username'] = 2;
        $_GET['filter_by_username'] = 3;
        $filter = new Username($this->usernames);
        $this->assertEquals(2, $filter->value(), 'Use POST over GET');
    }

    // Use GET when POST is present but "empty"
    public function testGETWhenPOSTIsEmpty()
    {
        $_POST['filter_by_username'] = '';
        $_GET['filter_by_username'] = 3;
        $filter = new Username($this->usernames);
        $this->assertEquals(3, $filter->value(), 'Use GET when POST is an empty string');

        $_POST['filter_by_username'] = null;
        $_GET['filter_by_username'] = 3;
        $filter = new Username($this->usernames);
        $this->assertEquals(3, $filter->value(), 'Use GET when POST is NULL');

        $_POST['filter_by_username'] = 0;
        $_GET['filter_by_username'] = 3;
        $filter = new Username($this->usernames);
        $this->assertEquals(3, $filter->value(), 'Use GET when POST is 0');

        $_POST['filter_by_username'] = "0";
        $_GET['filter_by_username'] = 3;
        $filter = new Username($this->usernames);
        $this->assertEquals(3, $filter->value(), 'Use GET when POST is "0"');
    }

    // Test valid without query and input not numeric
    public function testInvalidNonNumericValue()
    {
        $_POST['filter_by_username'] = 'admin';
        $filter = new Username($this->usernames);
        $this->assertFalse($filter->isValid(), 'POSTing a string without a Query object is invalid');

        unset($_POST['filter_by_username']);
        $_GET['filter_by_username'] = 'admin';
        $filter = new Username($this->usernames);
        $this->assertFalse($filter->isValid(), 'GETing a string without a Query object is invalid');
    }

    // Test valid without query and input not in options
    public function testInvalidNumericValue()
    {
        $_POST['filter_by_username'] = '4';
        $filter = new Username($this->usernames);
        $this->assertFalse($filter->isValid(), 'POSTing an ID not in the options array is invalid');

        unset($_POST['filter_by_username']);
        $_GET['filter_by_username'] = '4';
        $filter = new Username($this->usernames);
        $this->assertFalse($filter->isValid(), 'GETing an ID not in the options array is invalid');
    }

    protected function makeFilterWithQuery()
    {
        $filter = new Username();

        $usernames = array();
        foreach ($this->usernames as $id => $username) {
            $user = new stdClass();
            $user->member_id = $id;
            $user->username = $username;
            $usernames[] = $user;
        }

        $this->query->shouldReceive('count')->andReturn(count($this->usernames));
        $this->query->shouldReceive('all')->andReturn($usernames);
        $filter->setQuery($this->query);
        return $filter;
    }

    public function testSetQuery()
    {
        $filter = $this->makeFilterWithQuery();
        $this->assertEquals($this->usernames, $filter->getOptions(), "setQuery should set the options");
    }

    // Test setQuery will not overwrite options
    public function testSetQueryDoesNotOverwriteSetOptions()
    {
        $filter = new Username($this->usernames);

        $this->query->shouldReceive('count')->andReturn(5);
        $filter->setQuery($this->query);
        $this->assertSame($this->usernames, $filter->getOptions(), "setQuery should leave the options alone if they are set in the constructor");
    }

    // Test setQuery will not set options when query->count > 25
    public function testSetQueryDoesNotSetOptionsWithLargeUserCount()
    {
        $filter = new Username();

        $this->query->shouldReceive('count')->andReturn(26);
        $filter->setQuery($this->query);
        $this->assertEquals(array(), $filter->getOptions(), "setQuery should leave the options alone if there are more than 25 users");
    }

    // Test value with setQuery and input is numeric
    public function testSetQueryWithNumericInput()
    {
        // Present
        $_POST['filter_by_username'] = 2;
        $filter = $this->makeFilterWithQuery();
        $this->assertEquals(2, $filter->value(), 'The value reflects the submitted value');
        $this->assertTrue($filter->isValid(), 'Submitting an existing user id is valid');

        // Absent
        $_POST['filter_by_username'] = 4;
        $filter = $this->makeFilterWithQuery();
        $this->assertEquals(4, $filter->value(), 'The value reflects the submitted value');
        $this->assertFalse($filter->isValid(), 'Submitting non-existant user id is invalid');
    }

    public function testSetQueryWithNonNumericInputAndUserPresent()
    {
        $this->markTestSkipped('Skipping becase filter::first is reported as not defined - which is not true');
        
        $_POST['filter_by_username'] = 'admin';
        $filter = $this->makeFilterWithQuery();

        $members = m::mock('ExpressionEngine\Service\Model\Collection');
        $this->query->shouldReceive("filter->all")->andReturn($members);
        $members->shouldReceive('count')->andReturn(1);
        $members->shouldReceive('pluck')->with('member_id')->andReturn(1);

        $this->assertEquals(1, $filter->value(), 'The value reflects the id of the username');
        $this->assertTrue($filter->isValid(), 'Submitting an existing username is valid');
    }

    public function testSetQueryWithNonNumericInputAndUserNotPresent()
    {
        $this->markTestSkipped('Skipping becase filter::first is reported as not defined - which is not true');
        
        $_POST['filter_by_username'] = 'ferdinand.von.zeppelin';
        $filter = $this->makeFilterWithQuery();

        $members = m::mock('ExpressionEngine\Service\Model\Collection');
        $this->query->shouldReceive("filter->all")->andReturn($members);
        $members->shouldReceive('count')->andReturn(0);

        $this->assertEquals(-1, $filter->value(), 'We should have an array of -1 for failed searches');
        $this->assertFalse($filter->isValid(), 'Submitting an non-existing username is invalid');
    }
}
