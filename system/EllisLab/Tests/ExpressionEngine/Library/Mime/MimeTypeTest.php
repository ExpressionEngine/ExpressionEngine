<?php

namespace EllisLab\Tests\ExpressionEngine\Library\Mime;

use EllisLab\ExpressionEngine\Library\Mime\MimeType;

class MimeTypeTest extends \PHPUnit_Framework_TestCase {

	protected $mime_type;
	protected $safe_mime_types = array();

	public function setUp()
	{
		$this->safe_mime_types = array(
			'application/pdf',
			'audio/mpeg',
			'image/gif',
			'image/jpeg',
			'text/css',
			'text/plain',
			'video/mp4',
		);
		$this->mime_type = new MimeType();
	}

	public function tearDown()
	{
		$this->mime_type = NULL;
	}

	public function testEmptyConstructor()
	{
		$this->assertEquals($this->mime_type->getWhiteList(), array(), "Empty constructor should produce an empty whitelist.");
	}

	/**
	 * @dataProvider mimesDataProvider
	 */
	public function testConstructor($description, $in, $out, $exception)
	{
		if ($exception)
		{
			$this->setExpectedException($exception);
		}
		$this->mime_type = new MimeType($in);
		$this->assertEquals($this->mime_type->getWhiteList(), $out, $description);
	}

	public function testEmptyAddMimeTypesArgument()
	{
		$this->setExpectedException('PHPUnit_Framework_Error');
		$this->mime_type->addMimeTypes();
	}

	/**
	 * @dataProvider mimesDataProvider
	 */
	public function testAddMimeTypes($description, $in, $out, $exception)
	{
		if ($exception)
		{
			$this->setExpectedException($exception);
		}
		$this->mime_type->addMimeTypes($in);
		$this->assertEquals($this->mime_type->getWhiteList(), $out, $description);
	}

	public function mimesDataProvider()
	{
		return array(
			array('Boolen Argument',        TRUE,                        NULL,                'PHPUnit_Framework_Error'),
			array('Integer Argument',       1,                           NULL,                'PHPUnit_Framework_Error'),
			array('Float Argument',         1.1,                         NULL,                'PHPUnit_Framework_Error'),
			array('String Argument',        "text/plain",                NULL,                'PHPUnit_Framework_Error'),
			array('Empty Array Argument',   array(),                     array(),             FALSE),
			array('Valid Array Argument',   array('text/html'),          array('text/html'),  FALSE),
			array('Invalid Array Argument', array('text'),               NULL,                'InvalidArgumentException'),
			array('Invalid Array Argument', array('text/html', 'text',), NULL,                'InvalidArgumentException'),
			array('Invalid Array Argument', array('a/b/c'),              NULL,                'InvalidArgumentException'),
			array('Object Argument',        new \stdClass(),             NULL,                'PHPUnit_Framework_Error'),
			array('Closure Argument',       function() { return TRUE; }, NULL,                'PHPUnit_Framework_Error'),
			array('NULL Argument',          NULL,                        NULL,                'PHPUnit_Framework_Error'),
		);
	}

	/**
	 * @dataProvider mimeDataProvider
	 */
	public function testAddMimeType($description, $in, $out, $exception)
	{
		if ($exception)
		{
			$this->setExpectedException('InvalidArgumentException');
		}
		$this->mime_type->addMimeType($in);
		$this->assertEquals($this->mime_type->getWhiteList(), $out, $description);
	}

	public function mimeDataProvider()
	{
		return array(
			array('Boolen Argument',         TRUE,                        NULL,                TRUE),
			array('Integer Argument',        1,                           NULL,                TRUE),
			array('Float Argument',          1.1,                         NULL,                TRUE),
			array('Invalid String Argument', "text",                      NULL,                TRUE),
			array('Invalid String Argument', "a/b/c",                     NULL,                TRUE),
			array('Valid String Argument',   "text/plain",                array("text/plain"), FALSE),
			array('Empty Array Argument',    array(),                     NULL,                TRUE),
			array('Array Argument',          array('text/html'),          NULL,                TRUE),
			array('Object Argument',         new \stdClass(),             NULL,                TRUE),
			array('Closure Argument',        function() { return TRUE; }, NULL,                TRUE),
			array('NULL Argument',           NULL,                        NULL,                TRUE),
		);
	}

	public function testGetWhitelist()
	{

	}

	public function testOfFile()
	{

	}

	public function testFileIsImage()
	{

	}

	public function testIsImage()
	{

	}

	public function testFileIsSafeForUpload()
	{

	}

	public function testIsSafeForUpload()
	{

	}

}