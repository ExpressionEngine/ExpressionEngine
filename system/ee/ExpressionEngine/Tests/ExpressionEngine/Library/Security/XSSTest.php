<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Library\Security;

use ExpressionEngine\Library\Security\XSS;
use PHPUnit\Framework\TestCase;

require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';

class XSSTest extends TestCase
{
    public $xss;

    public function setUp(): void
    {
        $this->xss = new XSS();
    }

    public function tearDown(): void
    {
        $this->xss = null;
    }

    public function testXssClean()
    {
        $testArray = array(
            '"><script>alert(\'stored xss\')</script>' => '">[removed]alert&#9001;\'stored xss\'&#9002;[removed]',
            '"><a onload=alert(1);>' => '"><a >',
            '"><a/onload=alert(1);>' => '"><a/>',
            '"><img onload=alert(1);>' => '"><img >',
            '"><img/onload=alert(1);>' => '"><img/>',
            '"><svg onload=alert(1);>' => '"><svg >',
            '"><svg/onload=alert(1);>' => '"><svg/>',
            '"><svg/onload=(prompt``)>' => '"><svg/>',
            '<x onclick=alert(1) src=a>1</x>' => '<x  src=a>1</x>',
            '<marquee loop=1 width=0 onfinish=confirm(1)//' => '<marquee loop=1 width=0 ',
            "<select autofocus onfocus='confirm(1)'" => '<select autofocus ',

            // RTLO characters, some invisible, all at position some_file_[RTLO]3pm.exe
            '<a href="http://example.com/some_file_‮3pm.exe">http://example.com/some_file_‮3pm.exe</a>' =>
                '<a href="http://example.com/some_file_3pm.exe">http://example.com/some_file_3pm.exe</a>',
            '<a href="http://example.com/some_file_&#8238;3pm.exe">http://example.com/some_file_&#8238;3pm.exe</a>' =>
                '<a href="http://example.com/some_file_3pm.exe">http://example.com/some_file_3pm.exe</a>',
            '<a href="http://example.com/some_file_%E2%80%AE3pm.exe">http://example.com/some_file_%E2%80%AE3pm.exe</a>' =>
                '<a href="http://example.com/some_file_3pm.exe">http://example.com/some_file_3pm.exe</a>',
            'http://example.com/some_file_‮3pm.exe' => 'http://example.com/some_file_3pm.exe',
            'http://example.com/some_file_&#8238;3pm.exe' => 'http://example.com/some_file_3pm.exe',
            'http://example.com/some_file_%E2%80%AE3pm.exe' => 'http://example.com/some_file_3pm.exe',

            // make sure URL encoded characters don't break strings or cause security issues
            // %ba decodes to a non-UTF character, which would cause utf-8 PCRE expressions to nullify the string
            // XSS Clean should still decode this in URLs, but the resulting invalid character should be stripped instead of nulling the string
            // and these characters outside of tags should be allowed through
            '%bar%' => '%bar%',
            '<a href="http://example.com/%bar%">%bar%</a>' => '<a href="http://example.com/r%">%bar%</a>',
            '<a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>' => '<a href="http://www.google.com">Google</a>',
            '<r/onmouseover=eval.call`${\'alert\x28document.domain\x29\'}`>Text</r>' => '<r/>Text</r>',
        );

        foreach ($testArray as $before => $after) {
            $this->assertEquals($after, $this->xss->clean($before));
        }
    }
}
