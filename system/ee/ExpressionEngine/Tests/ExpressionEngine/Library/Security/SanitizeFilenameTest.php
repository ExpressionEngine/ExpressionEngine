<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
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
        "/some/uri/path/\r" => '_some_uri_path__',
        "/some/uri/path/\n" => '_some_uri_path__',
        "/some/uri/path/\r\nn" => '_some_uri_path___n',
        'F&A Costs.html' => 'F_A Costs.html',
        'badfilename#name.pdf' => 'badfilename_name.pdf',
        // "badfilename../" => "badfilename",
        "badfilename<!--" => "badfilename_",
        "badfilename-->" => "badfilename_",
        "badfilename<" => "badfilename_",
        "badfilename>" => "badfilename_",
        "badfilename'" => "badfilename_",
        'badfilename"' => "badfilename_",
        'badfilename&' => "badfilename_",
        'badfilename$' => "badfilename_",
        'badfilename#' => "badfilename_",
        'badfilename{' => "badfilename_",
        'badfilename}' => "badfilename_",
        'badfilename[' => "badfilename_",
        'badfilename]' => "badfilename_",
        'badfilename=' => "badfilename_",
        'badfilename:' => "badfilename_",
        'badfilename;' => "badfilename_",
        'badfilename?' => "badfilename_",
        "badfilename%20" => "badfilename_",
        "badfilename%22" => "badfilename_",
        "badfilename%3c" => "badfilename_",
        "badfilename%253c" => "badfilename_",
        "badfilename%3e" => "badfilename_",
        "badfilename%0e" => "badfilename_",
        "badfilename%28" => "badfilename_",
        "badfilename%29" => "badfilename_",
        "badfilename%2528" => "badfilename_",
        "badfilename%26" => "badfilename_",
        "badfilename%24" => "badfilename_",
        "badfilename%3f" => "badfilename_",
        "badfilename%3b" => "badfilename_",
        "badfilename%3d" => "badfilename_",
    ];

    private $badRelativeNames = [
        "/some/uri/path/\r" => '/some/uri/path/_',
        "/some/uri/path/\n" => '/some/uri/path/_',
        "/some/uri/path/\r\nn" => '/some/uri/path/__n',
        'F&A Costs.html' => 'F_A Costs.html',
        'badfilename#name.pdf' => 'badfilename_name.pdf',
        // "badfilename../" => "badfilename",
        "badfilename<!--" => "badfilename_",
        "badfilename-->" => "badfilename_",
        "badfilename<" => "badfilename_",
        "badfilename>" => "badfilename_",
        "badfilename'" => "badfilename_",
        'badfilename"' => "badfilename_",
        'badfilename&' => "badfilename_",
        'badfilename$' => "badfilename_",
        'badfilename#' => "badfilename_",
        'badfilename{' => "badfilename_",
        'badfilename}' => "badfilename_",
        'badfilename[' => "badfilename_",
        'badfilename]' => "badfilename_",
        'badfilename=' => "badfilename_",
        'badfilename:' => "badfilename_",
        'badfilename;' => "badfilename_",
        'badfilename?' => "badfilename_",
        "badfilename%20" => "badfilename_",
        "badfilename%22" => "badfilename_",
        "badfilename%3c" => "badfilename_",
        "badfilename%253c" => "badfilename_",
        "badfilename%3e" => "badfilename_",
        "badfilename%0e" => "badfilename_",
        "badfilename%28" => "badfilename_",
        "badfilename%29" => "badfilename_",
        "badfilename%2528" => "badfilename_",
        "badfilename%26" => "badfilename_",
        "badfilename%24" => "badfilename_",
        "badfilename%3f" => "badfilename_",
        "badfilename%3b" => "badfilename_",
        "badfilename%3d" => "badfilename_",
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
