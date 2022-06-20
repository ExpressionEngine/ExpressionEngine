<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Library\Mime;

use ExpressionEngine\Library\Mime\MimeType;
use PHPUnit\Framework\TestCase;

class MimeTypeTest extends TestCase
{
    protected $mime_type;
    protected $safe_mime_types = array();
    protected $exception_class;

    public function setUp(): void
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

    public function tearDown(): void
    {
        $this->mime_type = null;
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
        if ($exception) {
            $this->expectException($exception);
        }
        $this->mime_type = new MimeType($in);
        $this->assertEquals($this->mime_type->getWhiteList(), $out, $description);
    }

    public function testEmptyAddMimeTypesArgument()
    {
        $this->expectException($this->exception_class);
        $this->mime_type->addMimeTypes();
    }

    /**
     * @dataProvider mimesDataProvider
     */
    public function testAddMimeTypes($description, $in, $out, $exception)
    {
        if ($exception) {
            $this->expectException($exception);
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
            array('Boolen Argument',          true,                             null,                             $exception_class),
            array('Integer Argument',         1,                                null,                             $exception_class),
            array('Float Argument',           1.1,                              null,                             $exception_class),
            array('String Argument',          "text/plain",                     null,                             $exception_class),
            array('Empty Array Argument',     array(),                          array(),                          false),
            array('Valid Array Argument',     array('text/html'),               array('text/html'),               false),
            array('Valid Array Argument',     array('text/html', 'text/plain'), array('text/html', 'text/plain'), false),
            array('Duplicate Array Argument', array('text/html', 'text/html'),  array('text/html'),               false),
            array('Invalid Array Argument',   array('text'),                    null,                             'InvalidArgumentException'),
            array('Invalid Array Argument',   array('text/html', 'text'),      null,                             'InvalidArgumentException'),
            array('Invalid Array Argument',   array('a/b/c'),                   null,                             'InvalidArgumentException'),
            array('Object Argument',          new \stdClass(),                  null,                             $exception_class),
            array('Closure Argument',         function () {
                return true;
            },      null,                             $exception_class),
            array('NULL Argument',            null,                             null,                             $exception_class),
        );
    }

    /**
     * @dataProvider mimeDataProvider
     */
    public function testAddMimeType($description, $in, $out, $exception)
    {
        if ($exception) {
            $this->expectException('InvalidArgumentException');
        }
        $this->mime_type = new MimeType();
        $this->mime_type->addMimeType($in);
        $this->assertEquals($this->mime_type->getWhiteList(), $out, $description);
    }

    public function mimeDataProvider()
    {
        return array(
            array('Boolen Argument',         true,                        null,                true),
            array('Integer Argument',        1,                           null,                true),
            array('Float Argument',          1.1,                         null,                true),
            array('Invalid String Argument', "text",                      null,                true),
            array('Invalid String Argument', "a/b/c",                     null,                true),
            array('Valid String Argument',   "text/plain",                array("text/plain"), false),
            array('Empty Array Argument',    array(),                     null,                true),
            array('Array Argument',          array('text/html'),          null,                true),
            array('Object Argument',         new \stdClass(),             null,                true),
            array('Closure Argument',        function () {
                return true;
            }, null,                true),
            array('NULL Argument',           null,                        null,                true),
        );
    }

    /**
     * @dataProvider multipleMimeDataProvider
     */
    public function testMultipleAddMimeType($description, $in, $out)
    {
        $this->mime_type = new MimeType();
        foreach ($in as $mime) {
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
        if ($exception) {
            $this->expectException('Exception');
        }
        $this->assertEquals($this->mime_type->ofFile($in), $out, $description);
    }

    public function ofFileDataProvider()
    {
        $xml_mime = 'text/xml';
        if (version_compare(PHP_VERSION, '7.2', '<')) {
            $xml_mime = 'application/xml';
        }

        return array(
            array('Bad Path',      'foo.bar', '', true),
            array('CSS File',      realpath(__DIR__ . '/../../../support/test.css'),  'text/css', false),
            array('GIF File',      realpath(__DIR__ . '/../../../support/test.gif'),  'image/gif', false),
            array('HTML File',     realpath(__DIR__ . '/../../../support/test.html'), 'text/html', false),
            array('JPG File',      realpath(__DIR__ . '/../../../support/test.jpg'),  'image/jpeg', false),
            array('JS File',       realpath(__DIR__ . '/../../../support/test.js'),   'application/javascript', false),
            array('JSON File',     realpath(__DIR__ . '/../../../support/test.json'), 'application/json', false),
            array('Markdown File', realpath(__DIR__ . '/../../../support/test.md'),   'text/markdown', false),
            array('PDF File',      realpath(__DIR__ . '/../../../support/test.pdf'),  'application/pdf', false),
            array('PHP File',      realpath(__DIR__ . '/../../../support/test.php'),  'text/x-php', false),
            array('PNG File',      realpath(__DIR__ . '/../../../support/test.png'),  'image/png', false),
            array('Text File',     realpath(__DIR__ . '/../../../support/test.txt'),  'text/plain', false),
            array('XML File',      realpath(__DIR__ . '/../../../support/test.xml'),  $xml_mime, false),
        );
    }

    /**
     * @dataProvider fileDataProvider
     */
    public function testFileIsImage($description, $in, $out, $exception)
    {
        if ($exception) {
            $this->expectException('Exception');
        }
        $this->assertEquals($this->mime_type->fileIsImage($in), $out, $description);
    }

    /**
     * @dataProvider fileDataProvider
     */
    public function testFileIsSafeForUpload($description, $in, $out, $exception)
    {
        if ($exception) {
            $this->expectException('Exception');
        }
        $this->assertEquals($this->mime_type->fileIsSafeForUpload($in), $out, $description);
    }

    public function fileDataProvider()
    {
        return array(
            array('Bad Path',  'foo.bar', '', true),
            array('Good Path', realpath(__DIR__ . '/../../../support/test.php'),  false, false),
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
            array('JPEG MIME Type', 'image/jpeg',      true),
            array('PNG MIME Type',  'image/png',       false),
            array('HTML MIME Type', 'text/html',       false),
            array('PDF MIME Type',  'application/pdf', false),

            array('Boolen Argument',         true,                        false),
            array('Integer Argument',        1,                           false),
            array('Float Argument',          1.1,                         false),
            array('Invalid String Argument', "text",                      false),
            array('Invalid String Argument', "a/b/c",                     false),
            array('Empty Array Argument',    array(),                     false),
            array('Array Argument',          array('text/html'),          false),
            array('Object Argument',         new \stdClass(),             false),
            array('Closure Argument',        function () {
                return true;
            }, false),
            array('NULL Argument',           null,                        false),
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
            array('JPEG MIME Type', 'image/jpeg',      true),
            array('HTML MIME Type', 'text/html',       false),
            array('PDF MIME Type',  'application/pdf', true),

            array('Boolen Argument',         true,                        false),
            array('Integer Argument',        1,                           false),
            array('Float Argument',          1.1,                         false),
            array('Invalid String Argument', "text",                      false),
            array('Invalid String Argument', "a/b/c",                     false),
            array('Empty Array Argument',    array(),                     false),
            array('Array Argument',          array('text/html'),          false),
            array('Object Argument',         new \stdClass(),             false),
            array('Closure Argument',        function () {
                return true;
            }, false),
            array('NULL Argument',           null,                        false),
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
        if (version_compare(PHP_VERSION, '5.6.32', '<')) {
            $random_mime = 'binary';
        }

        return array(
            array('HTML Data',    '<html><body>Hello world</body></html>',    'text/html'),
            array('PHP Data',     '<?php echo "Hello world";?>',              'text/x-php'),
            array('Empty Data',   '',                                         'application/x-empty'),
            array('Random Bytes', pack('l', openssl_random_pseudo_bytes(42)), $random_mime),

            array('Boolen Argument',         true,                        'application/octet-stream'),
            array('Integer Argument',        1,                           'application/octet-stream'),
            array('Float Argument',          1.1,                         'text/plain'),
            array('Empty Array Argument',    array(),                     'application/octet-stream'),
            array('Array Argument',          array('text/html'),          'application/octet-stream'),
            array('Object Argument',         new \stdClass(),             'application/octet-stream'),
            array('Closure Argument',        function () {
                return true;
            }, 'application/octet-stream'),
            array('NULL Argument',           null,                        'application/x-empty'),
        );
    }
}

// EOF
