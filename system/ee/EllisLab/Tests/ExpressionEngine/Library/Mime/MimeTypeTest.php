<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Library\Mime;

use EllisLab\ExpressionEngine\Library\Mime\MimeType;
use PHPUnit\Framework\TestCase;

class MimeTypeTest extends TestCase {

	protected $mime_type;
	protected $safe_mime_types = array();
	protected $exception_class;

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
		$this->mime_type = new MimeType($this->safe_mime_types);

		$this->exception_class = (PHP_VERSION_ID < 70000) ? 'PHPUnit_Framework_Error' : 'TypeError';
	}

	public function tearDown()
	{
		$this->mime_type = NULL;
	}

	public function testEmptyConstructor()
	{
		$this->mime_type = new MimeType();
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
		$this->setExpectedException($this->exception_class);
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
		$this->mime_type = new MimeType();
		$this->mime_type->addMimeTypes($in);
		$this->assertEquals($this->mime_type->getWhiteList(), $out, $description);
	}

	public function mimesDataProvider()
	{
		// setUp() not called for data providers?
		$exception_class = (PHP_VERSION_ID < 70000) ? 'PHPUnit_Framework_Error' : 'TypeError';

		return array(
			array('Boolen Argument',          TRUE,                             NULL,                             $exception_class),
			array('Integer Argument',         1,                                NULL,                             $exception_class),
			array('Float Argument',           1.1,                              NULL,                             $exception_class),
			array('String Argument',          "text/plain",                     NULL,                             $exception_class),
			array('Empty Array Argument',     array(),                          array(),                          FALSE),
			array('Valid Array Argument',     array('text/html'),               array('text/html'),               FALSE),
			array('Valid Array Argument',     array('text/html', 'text/plain'), array('text/html', 'text/plain'), FALSE),
			array('Duplicate Array Argument', array('text/html', 'text/html'),  array('text/html'),               FALSE),
			array('Invalid Array Argument',   array('text'),                    NULL,                             'InvalidArgumentException'),
			array('Invalid Array Argument',   array('text/html', 'text',),      NULL,                             'InvalidArgumentException'),
			array('Invalid Array Argument',   array('a/b/c'),                   NULL,                             'InvalidArgumentException'),
			array('Object Argument',          new \stdClass(),                  NULL,                             $exception_class),
			array('Closure Argument',         function() { return TRUE; },      NULL,                             $exception_class),
			array('NULL Argument',            NULL,                             NULL,                             $exception_class),
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
		$this->mime_type = new MimeType();
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

	/**
	 * @dataProvider multipleMimeDataProvider
	 */
	public function testMultipleAddMimeType($description, $in, $out)
	{
		$this->mime_type = new MimeType();
		foreach ($in as $mime)
		{
			$this->mime_type->addMimeType($mime);
		}
		$this->assertEquals($this->mime_type->getWhiteList(), $out, $description);
	}

	public function multipleMimeDataProvider()
	{
		return array(
			array('Two Unique',           array('text/html', 'text/plain'),              array('text/html', 'text/plain')),
			array('Two Duplicate',        array('text/html', 'text/html'),               array('text/html')),
			array('Three with Duplicate', array('text/html', 'text/plain', 'text/html'), array('text/html', 'text/plain')),
		);
	}

	/**
	 * @dataProvider ofFileDataProvider
	 */
	public function testOfFile($description, $in, $out, $exception)
	{
		if ($exception)
		{
			$this->setExpectedException('Exception');
		}
		$this->assertEquals($this->mime_type->ofFile($in), $out, $description);
	}

	public function ofFileDataProvider()
	{
		$xml_mime = 'text/xml';
		if (version_compare(PHP_VERSION, '7.2', '<'))
		{
			$xml_mime = 'application/xml';
		}
		return array(
			array('Bad Path',      'foo.bar', '', TRUE),
			array('CSS File',      realpath(__DIR__.'/../../../support/test.css'),  'text/css', FALSE),
			array('GIF File',      realpath(__DIR__.'/../../../support/test.gif'),  'image/gif', FALSE),
			array('HTML File',     realpath(__DIR__.'/../../../support/test.html'), 'text/html', FALSE),
			array('JPG File',      realpath(__DIR__.'/../../../support/test.jpg'),  'image/jpeg', FALSE),
			array('JS File',       realpath(__DIR__.'/../../../support/test.js'),   'application/javascript', FALSE),
			array('JSON File',     realpath(__DIR__.'/../../../support/test.json'), 'application/json', FALSE),
			array('Markdown File', realpath(__DIR__.'/../../../support/test.md'),   'text/plain', FALSE),
			array('PDF File',      realpath(__DIR__.'/../../../support/test.pdf'),  'application/pdf', FALSE),
			array('PHP File',      realpath(__DIR__.'/../../../support/test.php'),  'text/x-php', FALSE),
			array('PNG File',      realpath(__DIR__.'/../../../support/test.png'),  'image/png', FALSE),
			array('Text File',     realpath(__DIR__.'/../../../support/test.txt'),  'text/plain', FALSE),
			array('XML File',      realpath(__DIR__.'/../../../support/test.xml'),  $xml_mime, FALSE),
		);
	}

	/**
	 * @dataProvider fileDataProvider
	 */
	public function testFileIsImage($description, $in, $out, $exception)
	{
		if ($exception)
		{
			$this->setExpectedException('Exception');
		}
		$this->assertEquals($this->mime_type->fileIsImage($in), $out, $description);
	}

	/**
	 * @dataProvider fileDataProvider
	 */
	public function testFileIsSafeForUpload($description, $in, $out, $exception)
	{
		if ($exception)
		{
			$this->setExpectedException('Exception');
		}
		$this->assertEquals($this->mime_type->fileIsSafeForUpload($in), $out, $description);
	}

	public function fileDataProvider()
	{
		return array(
			array('Bad Path',  'foo.bar', '', TRUE),
			array('Good Path', realpath(__DIR__.'/../../../support/test.php'),  FALSE, FALSE),
		);
	}

	/**
	 * @dataProvider isImageDataProvider
	 */
	public function testIsImage($description, $in, $out)
	{
		$this->assertEquals($this->mime_type->isImage($in), $out, $description);
	}

	public function isImageDataProvider()
	{
		return array(
			array('JPEG MIME Type', 'image/jpeg',      TRUE),
			array('PNG MIME Type',  'image/png',       FALSE),
			array('HTML MIME Type', 'text/html',       FALSE),
			array('PDF MIME Type',  'application/pdf', FALSE),

			array('Boolen Argument',         TRUE,                        FALSE),
			array('Integer Argument',        1,                           FALSE),
			array('Float Argument',          1.1,                         FALSE),
			array('Invalid String Argument', "text",                      FALSE),
			array('Invalid String Argument', "a/b/c",                     FALSE),
			array('Empty Array Argument',    array(),                     FALSE),
			array('Array Argument',          array('text/html'),          FALSE),
			array('Object Argument',         new \stdClass(),             FALSE),
			array('Closure Argument',        function() { return TRUE; }, FALSE),
			array('NULL Argument',           NULL,                        FALSE),
		);
	}

	/**
	 * @dataProvider isSafeUploadDataProvider
	 */
	public function testIsSafeForUpload($description, $in, $out)
	{
		$this->assertEquals($this->mime_type->isSafeForUpload($in), $out, $description);
	}

	public function isSafeUploadDataProvider()
	{
		return array(
			array('JPEG MIME Type', 'image/jpeg',      TRUE),
			array('HTML MIME Type', 'text/html',       FALSE),
			array('PDF MIME Type',  'application/pdf', TRUE),

			array('Boolen Argument',         TRUE,                        FALSE),
			array('Integer Argument',        1,                           FALSE),
			array('Float Argument',          1.1,                         FALSE),
			array('Invalid String Argument', "text",                      FALSE),
			array('Invalid String Argument', "a/b/c",                     FALSE),
			array('Empty Array Argument',    array(),                     FALSE),
			array('Array Argument',          array('text/html'),          FALSE),
			array('Object Argument',         new \stdClass(),             FALSE),
			array('Closure Argument',        function() { return TRUE; }, FALSE),
			array('NULL Argument',           NULL,                        FALSE),
		);
	}

	/**
	 * @dataProvider ofBufferDataProvider
	 */
	public function testOfBuffer($description, $in, $out)
	{
		$this->assertEquals($this->mime_type->ofBuffer($in), $out, $description);
	}

	public function ofBufferDataProvider()
	{
		$random_mime = 'application/octet-stream';
		if (version_compare(PHP_VERSION, '5.6.32', '<'))
		{
			$random_mime = 'binary';
		}

		return array(
			array('HTML Data',    '<html><body>Hello world</body></html>',    'text/html'),
			array('PHP Data',     '<?php echo "Hello world";?>',              'text/x-php'),
			array('Empty Data',   '',                                         'application/x-empty'),
			array('Random Bytes', pack('l', openssl_random_pseudo_bytes(42)), $random_mime),

			array('Boolen Argument',         TRUE,                        'application/octet-stream'),
			array('Integer Argument',        1,                           'application/octet-stream'),
			array('Float Argument',          1.1,                         'text/plain'),
			array('Empty Array Argument',    array(),                     'application/octet-stream'),
			array('Array Argument',          array('text/html'),          'application/octet-stream'),
			array('Object Argument',         new \stdClass(),             'application/octet-stream'),
			array('Closure Argument',        function() { return TRUE; }, 'application/octet-stream'),
			array('NULL Argument',           NULL,                        'application/x-empty'),
		);
	}

}

// EOF
