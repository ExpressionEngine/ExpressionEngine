<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Library\Data;

use ExpressionEngine\Library\Data\CSV;
use PHPUnit\Framework\TestCase;

class CSVTest extends TestCase
{
    protected $csv;

    protected function setUp() : void
    {
        $this->csv = new CSV();
    }

    public function testAddsAssociaitiveArrayRow()
    {
        $result = $this->csv->addRow(array(
            'name'  => 'ExpressionEngine',
            'email' => 'support@expressionengine.com',
        ));

        $this->assertEquals($result, $this->csv);
    }

    public function testAddsObjectRow()
    {
        $row = new \stdClass();
        $row->name = 'ExpressionEngine';
        $row->email = 'support@expressionengine.com';
        $result = $this->csv->addRow($row);

        $this->assertEquals($result, $this->csv);
    }


    public function testFailsIndexedArrayRow()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->csv->addRow(array('support@expressionengine.com', 'hello@ellislab.com'));
    }


    public function testFailsString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->csv->addRow('support@expressionengine.com');
    }

    public function testToString()
    {
        $this->csv
            ->addRow(array(
                'name'  => 'ExpressionEngine Team',
                'email' => 'support@expressionengine.com',
            ))
            ->addRow(array(
                'name'  => 'ExpressionEngine Support',
                'email' => 'support@ellislab.com',
            ));

        $this->assertEquals(
            "\"name\",\"email\"\n\"ExpressionEngine Team\",\"support@expressionengine.com\"\n\"ExpressionEngine Support\",\"support@ellislab.com\"\n",
            (string) $this->csv
        );
    }

    public function testSave()
    {
        $this->csv
            ->addRow(array(
                'name'  => 'ExpressionEngine Team',
                'email' => 'support@expressionengine.com',
            ))
            ->addRow(array(
                'name'  => 'ExpressionEngine Support',
                'email' => 'support@ellislab.com',
            ));

        $tmp_dir = "/var/tmp";
        if (PHP_OS=="WINNT") {
            $tmp_dir = "C:/tmp";
        }
        $this->csv->save($tmp_dir.'/test.csv');
        $this->assertFileExists($tmp_dir.'/test.csv');
        $this->assertEquals(
            "\"name\",\"email\"\n\"ExpressionEngine Team\",\"support@expressionengine.com\"\n\"ExpressionEngine Support\",\"support@ellislab.com\"\n",
            file_get_contents($tmp_dir.'/test.csv')
        );
    }

    public function testAddDifferentArrays()
    {
        $this->csv
            ->addRow(array(
                'name'  => 'ExpressionEngine Team',
                'email' => 'support@expressionengine.com'
            ))
            ->addRow(array(
                'email'      => 'developers@ellislab.com',
                'first_name' => 'ExpressionEngine',
                'last_name'  => 'Developers'
            ));

        $this->assertEquals(
            "\"name\",\"email\",\"first_name\",\"last_name\"\n\"ExpressionEngine Team\",\"support@expressionengine.com\",\"\",\"\"\n\"\",\"developers@ellislab.com\",\"ExpressionEngine\",\"Developers\"\n",
            (string) $this->csv
        );
    }

    public function testAddDifferentObjects()
    {
        $row1             = new \stdClass();
        $row1->name       = 'ExpressionEngine Team';
        $row1->email      = 'support@expressionengine.com';
        $row2             = new \stdClass();
        $row2->email      = 'developers@ellislab.com';
        $row2->first_name = 'ExpressionEngine';
        $row2->last_name  = 'Developers';

        $this->csv->addRow($row1)->addRow($row2);

        $this->assertEquals(
            "\"name\",\"email\",\"first_name\",\"last_name\"\n\"ExpressionEngine Team\",\"support@expressionengine.com\",\"\",\"\"\n\"\",\"developers@ellislab.com\",\"ExpressionEngine\",\"Developers\"\n",
            (string) $this->csv
        );
    }

    public function testAddDifferentArraysAndObjects()
    {
        $row1             = new \stdClass();
        $row1->name       = 'ExpressionEngine Team';
        $row1->email      = 'support@expressionengine.com';

        $this->csv->addRow($row1)
            ->addRow(array(
                'email'      => 'developers@ellislab.com',
                'first_name' => 'ExpressionEngine',
                'last_name'  => 'Developers'
            ));

        $this->assertEquals(
            "\"name\",\"email\",\"first_name\",\"last_name\"\n\"ExpressionEngine Team\",\"support@expressionengine.com\",\"\",\"\"\n\"\",\"developers@ellislab.com\",\"ExpressionEngine\",\"Developers\"\n",
            (string) $this->csv
        );
    }

    public function testCommasInRows()
    {
        $result = $this->csv->addRow(array(
            'name'  => 'Team, ExpressionEngine',
            'email' => 'support@expressionengine.com',
        ));

        $this->assertEquals(
            "\"name\",\"email\"\n\"Team, ExpressionEngine\",\"support@expressionengine.com\"\n",
            (string) $this->csv
        );
    }

    public function testQuotesInRows()
    {
        $result = $this->csv->addRow(array(
            'name'  => '"Dev Robots" Team',
            'email' => 'developers@ellislab.com',
        ));

        $this->assertEquals(
            "\"name\",\"email\"\n\"\"\"Dev Robots\"\" Team\",\"developers@ellislab.com\"\n",
            (string) $this->csv
        );
    }
}

// EOF
