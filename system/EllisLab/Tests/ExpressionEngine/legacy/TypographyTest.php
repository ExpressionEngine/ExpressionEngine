<?php

require_once BASEPATH.'libraries/Typography.php';
require_once BASEPATH.'helpers/string_helper.php';

require_once APPPATH.'libraries/EE_Typography.php';
require_once APPPATH.'helpers/EE_string_helper.php';

define('PATH_MOD', APPPATH.'modules/');

class TypographyTest extends \PHPUnit_Framework_TestCase {

	private $typography;

	public function setUp()
	{
		$this->typography = new TypographyStub();
	}

	public function testCodeFence()
	{
		$str = $this->typography->markdown_pre_process_bypass('abc');
		$str = $this->typography->markdown($str);
		return TRUE;
	}

	public function testCodeBlock()
	{

		return TRUE;
	}

	public function testCodeBlockAndFence()
	{

		return TRUE;
	}

	public function testSmartyPants()
	{

		return TRUE;
	}
}

class TypographyStub extends EE_Typography
{
	public function __construct()
	{
		// Skipping initialize...
		require_once __DIR__.'/../../../ExpressionEngine/Core/Autoloader.php';
		Autoloader::getInstance()->addPrefix('Michelf', APPPATH.'libraries/typography/Markdown/Michelf/');
	}

	public function markdown_pre_process_bypass($text)
	{
		return $this->markdown_pre_process($text);
	}
}
