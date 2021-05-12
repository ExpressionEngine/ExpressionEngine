<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Library\Security;

require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
require_once APPPATH . 'core/Security.php';

use EE_Security;
use PHPUnit\Framework\TestCase;

class SanitizeFilenameTest extends TestCase
{
	private $security;

	private $goodNames = [
		'image.png',
		'index.html',
		'my-pdf-file-name.pdf',
	];

	private $badNames = [
		"/some/uri/path/\r" => 'someuripath',
		"/some/uri/path/\n" => 'someuripath',
		"/some/uri/path/\r\nn" => 'someuripathn',
		'F&A Costs.html' => 'FA Costs.html',
		'badfilename#name.pdf' => 'badfilenamename.pdf',
		// "badfilename../" => "badfilename",
		"badfilename<!--" => "badfilename",
		"badfilename-->" => "badfilename",
		"badfilename<" => "badfilename",
		"badfilename>" => "badfilename",
		"badfilename'" => "badfilename",
		'badfilename"' => "badfilename",
		'badfilename&' => "badfilename",
		'badfilename$' => "badfilename",
		'badfilename#' => "badfilename",
		'badfilename{' => "badfilename",
		'badfilename}' => "badfilename",
		'badfilename[' => "badfilename",
		'badfilename]' => "badfilename",
		'badfilename=' => "badfilename",
		'badfilename:' => "badfilename",
		'badfilename;' => "badfilename",
		'badfilename?' => "badfilename",
		"badfilename%20" => "badfilename",
		"badfilename%22" => "badfilename",
		"badfilename%3c" => "badfilename",
		"badfilename%253c" => "badfilename",
		"badfilename%3e" => "badfilename",
		"badfilename%0e" => "badfilename",
		"badfilename%28" => "badfilename",
		"badfilename%29" => "badfilename",
		"badfilename%2528" => "badfilename",
		"badfilename%26" => "badfilename",
		"badfilename%24" => "badfilename",
		"badfilename%3f" => "badfilename",
		"badfilename%3b" => "badfilename",
		"badfilename%3d" => "badfilename",
	];

	private $badRelativeNames = [
		"/some/uri/path/\r" => '/some/uri/path/',
		"/some/uri/path/\n" => '/some/uri/path/',
		"/some/uri/path/\r\nn" => '/some/uri/path/n',
		'F&A Costs.html' => 'FA Costs.html',
		'badfilename#name.pdf' => 'badfilenamename.pdf',
		// "badfilename../" => "badfilename",
		"badfilename<!--" => "badfilename",
		"badfilename-->" => "badfilename",
		"badfilename<" => "badfilename",
		"badfilename>" => "badfilename",
		"badfilename'" => "badfilename",
		'badfilename"' => "badfilename",
		'badfilename&' => "badfilename",
		'badfilename$' => "badfilename",
		'badfilename#' => "badfilename",
		'badfilename{' => "badfilename",
		'badfilename}' => "badfilename",
		'badfilename[' => "badfilename",
		'badfilename]' => "badfilename",
		'badfilename=' => "badfilename",
		'badfilename:' => "badfilename",
		'badfilename;' => "badfilename",
		'badfilename?' => "badfilename",
		"badfilename%20" => "badfilename",
		"badfilename%22" => "badfilename",
		"badfilename%3c" => "badfilename",
		"badfilename%253c" => "badfilename",
		"badfilename%3e" => "badfilename",
		"badfilename%0e" => "badfilename",
		"badfilename%28" => "badfilename",
		"badfilename%29" => "badfilename",
		"badfilename%2528" => "badfilename",
		"badfilename%26" => "badfilename",
		"badfilename%24" => "badfilename",
		"badfilename%3f" => "badfilename",
		"badfilename%3b" => "badfilename",
		"badfilename%3d" => "badfilename",
	];

	public function setUp(): void
	{
		$this->security = new EE_Security();
	}

	public function tearDown(): void
	{
		$this->security = null;
	}

	public function testGoodNames()
	{
		foreach ($this->goodNames as $goodName) {
			$test = $this->security->sanitize_filename($goodName);
			$this->assertEquals($test, $goodName);
		}
	}

	public function testBadNames()
	{
		foreach ($this->badNames as $badName => $result) {
			$test = $this->security->sanitize_filename($badName);
			$this->assertEquals($test, $result);
		}
	}

	public function testGoodNamesWithRelativePath()
	{
		foreach ($this->goodNames as $goodName) {
			$test = $this->security->sanitize_filename($goodName, true);
			$this->assertEquals($test, $goodName);
		}
	}

	public function testBadNamesWithRelativePath()
	{
		foreach ($this->badRelativeNames as $badName => $result) {
			$test = $this->security->sanitize_filename($badName, true);
			$this->assertEquals($test, $result);
		}
	}
}