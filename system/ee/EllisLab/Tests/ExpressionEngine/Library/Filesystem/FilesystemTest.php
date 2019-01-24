<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Library\Filesystem;

use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class FilesystemTest extends TestCase {

	protected $fs;
	protected $dummy_files;
	protected $path;

	public function setUp()
	{
		$this->fs = new Filesystem();

		$this->path = realpath(__DIR__.'/../../../support/') . '/';

		$this->dummy_files = array(
			'image001.jpg',
			'image002.jpg',
			'image002_2.jpg',
			'vacation_2016_01_02.jpg',
			'unique_test.txt',
			'image.jpg',
			'image_99.jpg',
			'image_99_1.jpg'
		);

		foreach ($this->dummy_files as $file)
		{
			touch($this->path.$file);
		}

		for ($x = 1; $x < 101; $x++)
		{
			touch($this->path.'unique_test_'.$x.'.txt');
		}
	}

	public function tearDown()
	{
		foreach ($this->dummy_files as $file)
		{
			unlink($this->path.$file);
		}

		for ($x = 1; $x < 101; $x++)
		{
			unlink($this->path.'unique_test_'.$x.'.txt');
		}
	}

	/**
	 * @dataProvider uniqueFilenamesProvider
	 */
	public function testGetUniqueFilename($description, $in, $out)
	{
		$this->assertEquals($this->fs->getUniqueFilename($this->path.$in), $this->path.$out, $description);
	}

	public function uniqueFilenamesProvider()
	{
		return array(
			array('File is already unique',            'DSC_0001.jpg',            'DSC_0001.jpg'),
			array('Adds an underscore',                'image001.jpg',            'image001_1.jpg'),
			array('Rename picks up where it left off', 'image002.jpg',            'image002_3.jpg'),
			array('Handles extra underscores in name', 'vacation_2016_01_02.jpg', 'vacation_2016_01_02_1.jpg'),
			array('Handles extra underscores',         'image.jpg',               'image_100.jpg'),
			array('Handles partial matches',           'vacation.jpg',            'vacation.jpg'),
			array('Exceeds 100 renames',               'unique_test.txt',         'unique_test_101.txt')
		);
	}

}

// EOF
