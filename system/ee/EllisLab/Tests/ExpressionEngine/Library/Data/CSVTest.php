<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Library\Data;

use EllisLab\ExpressionEngine\Library\Data\CSV;
use PHPUnit\Framework\TestCase;

class CSVTest extends TestCase {

	protected $csv;

	protected function setUp()
	{
		$this->csv = new CSV();
	}

	public function testAddsAssociaitiveArrayRow()
	{
		$result = $this->csv->addRow(array(
			'name'  => 'EllisLab',
			'email' => 'team@ellislab.com',
		));

		$this->assertEquals($result, $this->csv);
	}

	public function testAddsObjectRow()
	{
		$row = new \stdClass();
		$row->name = 'EllisLab';
		$row->email = 'team@ellislab.com';
		$result = $this->csv->addRow($row);

		$this->assertEquals($result, $this->csv);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testFailsIndexedArrayRow()
	{
		$this->csv->addRow(array('team@ellislab.com', 'hello@ellislab.com'));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testFailsString()
	{
		$this->csv->addRow('team@ellislab.com');
	}

	public function testToString()
	{
		$this->csv
			->addRow(array(
				'name'  => 'EllisLab Team',
				'email' => 'team@ellislab.com',
			))
			->addRow(array(
				'name'  => 'EllisLab Support',
				'email' => 'support@ellislab.com',
			));

		$this->assertEquals(
			"\"name\",\"email\"\n\"EllisLab Team\",\"team@ellislab.com\"\n\"EllisLab Support\",\"support@ellislab.com\"\n",
			(string) $this->csv
		);
	}

	public function testSave()
	{
		$this->csv
			->addRow(array(
				'name'  => 'EllisLab Team',
				'email' => 'team@ellislab.com',
			))
			->addRow(array(
				'name'  => 'EllisLab Support',
				'email' => 'support@ellislab.com',
			));

		$this->csv->save('/var/tmp/test.csv');
		$this->assertFileExists('/var/tmp/test.csv');
		$this->assertEquals(
			"\"name\",\"email\"\n\"EllisLab Team\",\"team@ellislab.com\"\n\"EllisLab Support\",\"support@ellislab.com\"\n",
			file_get_contents('/var/tmp/test.csv')
		);
	}

	public function testAddDifferentArrays()
	{
		$this->csv
			->addRow(array(
				'name'  => 'EllisLab Team',
				'email' => 'team@ellislab.com'
			))
			->addRow(array(
				'email'      => 'developers@ellislab.com',
				'first_name' => 'EllisLab',
				'last_name'  => 'Developers'
			));

		$this->assertEquals(
			"\"name\",\"email\",\"first_name\",\"last_name\"\n\"EllisLab Team\",\"team@ellislab.com\",\"\",\"\"\n\"\",\"developers@ellislab.com\",\"EllisLab\",\"Developers\"\n",
			(string) $this->csv
		);
	}

	public function testAddDifferentObjects()
	{
		$row1             = new \stdClass();
		$row1->name       = 'EllisLab Team';
		$row1->email      = 'team@ellislab.com';
		$row2             = new \stdClass();
		$row2->email      = 'developers@ellislab.com';
		$row2->first_name = 'EllisLab';
		$row2->last_name  = 'Developers';

		$this->csv->addRow($row1)->addRow($row2);

		$this->assertEquals(
			"\"name\",\"email\",\"first_name\",\"last_name\"\n\"EllisLab Team\",\"team@ellislab.com\",\"\",\"\"\n\"\",\"developers@ellislab.com\",\"EllisLab\",\"Developers\"\n",
			(string) $this->csv
		);
	}

	public function testAddDifferentArraysAndObjects()
	{
		$row1             = new \stdClass();
		$row1->name       = 'EllisLab Team';
		$row1->email      = 'team@ellislab.com';

		$this->csv->addRow($row1)
			->addRow(array(
				'email'      => 'developers@ellislab.com',
				'first_name' => 'EllisLab',
				'last_name'  => 'Developers'
			));

		$this->assertEquals(
			"\"name\",\"email\",\"first_name\",\"last_name\"\n\"EllisLab Team\",\"team@ellislab.com\",\"\",\"\"\n\"\",\"developers@ellislab.com\",\"EllisLab\",\"Developers\"\n",
			(string) $this->csv
		);
	}

	public function testCommasInRows()
	{
		$result = $this->csv->addRow(array(
			'name'  => 'Team, EllisLab',
			'email' => 'team@ellislab.com',
		));

		$this->assertEquals(
			"\"name\",\"email\"\n\"Team, EllisLab\",\"team@ellislab.com\"\n",
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
