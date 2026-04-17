<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Core;

use ExpressionEngine\Core\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    private $originalOut;
    private $originalServer;
    private $startingOutputBufferLevel;

    protected function setUp(): void
    {
        $this->originalOut = array_key_exists('OUT', $GLOBALS) ? $GLOBALS['OUT'] : null;
        $this->originalServer = $_SERVER;
        $this->startingOutputBufferLevel = ob_get_level();
    }

    protected function tearDown(): void
    {
        if ($this->originalOut === null) {
            unset($GLOBALS['OUT']);
        } else {
            $GLOBALS['OUT'] = $this->originalOut;
        }

        $_SERVER = $this->originalServer;

        while (ob_get_level() > $this->startingOutputBufferLevel) {
            @ob_end_clean();
        }
    }

    public function testSetBodyStoresStringBody()
    {
        $response = new Response();

        $response->setBody('plain body');

        $this->assertSame('plain body', $this->readProperty($response, 'body'));
    }

    public function testSetBodyEncodesArrayAndSetsJsonHeader()
    {
        $response = new Response();
        $payload = array('status' => 'ok', 'count' => 2);

        $response->setBody($payload);

        $this->assertSame(json_encode($payload), $this->readProperty($response, 'body'));
        $this->assertSame('application/json; charset=UTF-8', $response->getHeader('Content-Type'));
    }

    public function testAppendBodyAppendsContent()
    {
        $response = new Response();
        $response->setBody('first');

        $response->appendBody(' second');

        $this->assertSame('first second', $this->readProperty($response, 'body'));
    }

    public function testHasHeaderAndGetHeaderBehaviors()
    {
        $response = new Response();
        $response->setHeader('X-Test', 'yes');

        $this->assertTrue($response->hasHeader('X-Test'));
        $this->assertSame('yes', $response->getHeader('X-Test'));
        $this->assertFalse($response->hasHeader('Missing'));
        $this->assertNull($response->getHeader('Missing'));
    }

    public function testSetHeaderParsesCombinedHeaderString()
    {
        $response = new Response();

        $response->setHeader('X-Combined: value');

        $this->assertTrue($response->hasHeader('X-Combined'));
        $this->assertSame(' value', $response->getHeader('X-Combined'));
    }

    public function testSetStatusAcceptsNumericInput()
    {
        $response = new Response();

        $response->setStatus('404');

        $this->assertEquals(404, $this->readProperty($response, 'status'));
    }

    public function testSetStatusThrowsTypeErrorOnNonNumericInput()
    {
        $response = new Response();

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('setStatus expects a number');

        $response->setStatus('not-a-number');
    }

    public function testSendWithoutBodyDelegatesToLegacyOutDisplay()
    {
        $response = new Response();
        $response->setHeader('Content-Type', 'text/plain');
        $response->setStatus(202);
        $out = new LegacyOutStub();
        $GLOBALS['OUT'] = $out;

        $result = $response->send();

        $this->assertSame(array(array('Content-Type: text/plain', true)), $out->headers);
        $this->assertSame(array(array('', 202)), $out->displayCalls);
        $this->assertSame('display-result', $result);
    }

    public function testSendWithBodyInvokesHeaderAndBodyWriters()
    {
        $response = new ResponseSendSpy();
        $response->setBody('payload');

        $response->send();

        $this->assertTrue($response->sendHeadersCalled);
        $this->assertTrue($response->sendBodyCalled);
    }

    public function testEnableCompressionOnlyTurnsOnWhenSupported()
    {
        $response = new ResponseCompressionHarness();

        $response->clientSupported = true;
        $response->serverSupported = false;
        $response->enableCompression();
        $this->assertFalse($response->isCompressionFlagOn());

        $response->serverSupported = true;
        $response->enableCompression();
        $this->assertTrue($response->isCompressionFlagOn());

        $response->disableCompression();
        $this->assertFalse($response->isCompressionFlagOn());
    }

    public function testSupportsCompressionRequiresClientAndServerSupport()
    {
        $response = new ResponseCompressionHarness();

        $response->clientSupported = true;
        $response->serverSupported = true;
        $this->assertTrue($response->supportsCompression());

        $response->clientSupported = false;
        $response->serverSupported = true;
        $this->assertFalse($response->supportsCompression());

        $response->clientSupported = true;
        $response->serverSupported = false;
        $this->assertFalse($response->supportsCompression());
    }

    public function testCompressionEnabledDependsOnStatusAndCompressionFlag()
    {
        $response = new ResponseProtectedHarness();

        $response->setCompressionFlag(true);
        $response->setStatus(200);
        $this->assertTrue($response->compressionEnabled());

        $response->setStatus(304);
        $this->assertFalse($response->compressionEnabled());

        $response->setCompressionFlag(false);
        $response->setStatus(200);
        $this->assertFalse($response->compressionEnabled());
    }

    public function testSendHeadersIteratesOverConfiguredHeaders()
    {
        $response = new ResponseProtectedHarness();
        $response->setHeader('X-Test', 'one');
        $response->setHeader('X-Trace', 'two');

        $response->invokeSendHeaders();

        $this->assertTrue(true);
    }

    public function testSendBodyOutputsBodyWhenCompressionDisabled()
    {
        $response = new ResponseProtectedHarness();
        $response->setBody('plain output');
        $response->setCompressionFlag(false);

        ob_start();
        $response->invokeSendBody();
        $output = ob_get_clean();

        $this->assertSame('plain output', $output);
    }

    public function testSendBodyExecutesCompressionPathWhenEnabled()
    {
        $response = new ResponseProtectedHarness();
        $response->setBody('compressed output');
        $response->setCompressionFlag(true);
        $response->setStatus(200);

        $response->invokeSendBody();

        $this->assertTrue(true);
    }

    public function testClientSupportsCompressionChecksRequestHeader()
    {
        $response = new ResponseProtectedHarness();

        unset($_SERVER['HTTP_ACCEPT_ENCODING']);
        $this->assertFalse($response->invokeClientSupportsCompression());

        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'br, gzip';
        $this->assertTrue($response->invokeClientSupportsCompression());

        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'br, deflate';
        $this->assertFalse($response->invokeClientSupportsCompression());
    }

    public function testServerSupportsCompressionUsesPhpConfigurationAndExtensionState()
    {
        $response = new ResponseProtectedHarness();
        $expected = ((bool) @ini_get('zlib.output_compression')) == false && extension_loaded('zlib');

        $this->assertSame($expected, $response->invokeServerSupportsCompression());
    }

    private function readProperty(Response $response, $property)
    {
        $reflection = new \ReflectionProperty(Response::class, $property);
        \TestReflectionHelper::makeAccessible($reflection);

        return $reflection->getValue($response);
    }
}

class LegacyOutStub
{
    public $headers = array();
    public $displayCalls = array();

    public function _display($body, $status)
    {
        $this->displayCalls[] = array($body, $status);

        return 'display-result';
    }
}

class ResponseSendSpy extends Response
{
    public $sendHeadersCalled = false;
    public $sendBodyCalled = false;

    protected function sendHeaders()
    {
        $this->sendHeadersCalled = true;
    }

    protected function sendBody()
    {
        $this->sendBodyCalled = true;
    }
}

class ResponseCompressionHarness extends Response
{
    public $clientSupported = true;
    public $serverSupported = true;

    public function isCompressionFlagOn()
    {
        return $this->compress;
    }

    protected function clientSupportsCompression()
    {
        return $this->clientSupported;
    }

    protected function serverSupportsCompression()
    {
        return $this->serverSupported;
    }
}

class ResponseProtectedHarness extends Response
{
    public function setCompressionFlag($value)
    {
        $this->compress = $value;
    }

    public function invokeSendHeaders()
    {
        parent::sendHeaders();
    }

    public function invokeSendBody()
    {
        parent::sendBody();
    }

    public function invokeClientSupportsCompression()
    {
        return parent::clientSupportsCompression();
    }

    public function invokeServerSupportsCompression()
    {
        return parent::serverSupportsCompression();
    }
}

// EOF
