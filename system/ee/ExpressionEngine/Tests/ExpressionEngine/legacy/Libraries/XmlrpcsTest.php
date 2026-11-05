<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries;

use EE_Xmlrpcs;
use ErrorException;
use PHPUnit\Framework\TestCase;
use XML_RPC_Message;
use XML_RPC_Response;
use XML_RPC_Values;

class XmlrpcsTest extends TestCase
{
    private $server;
    private $handler;
    private $errorReporting;

    /**
     * Load the real XML-RPC parser and server.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
        require_once BASEPATH . 'libraries/Xmlrpc.php';
        require_once BASEPATH . 'libraries/Xmlrpcs.php';
    }

    /**
     * Register a generic instance handler and surface all runtime diagnostics.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->errorReporting = error_reporting(E_ALL);
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        $this->handler = new XmlrpcsTestHandler();
        $this->server = new EE_Xmlrpcs();
        $this->server->initialize(array('object' => $this->handler, 'xss_clean' => false));
        $this->server->add_to_map('example.receive', XmlrpcsTestHandler::class . '.receive', array(), 'Receive parameters');
    }

    /**
     * Restore process state for the surrounding suite.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ee()->resetMocks();
        restore_error_handler();
        error_reporting($this->errorReporting);
    }

    /**
     * Parse real XML, decode parameters, and serialize the handler response.
     *
     * @return void
     */
    public function testParsesAndDispatchesToConfiguredInstance(): void
    {
        $response = $this->server->parseRequest($this->request('example.receive',
            '<param><value><string>Café &amp; tea</string></value></param>' .
            '<param><value><int>42</int></value></param>' .
            '<param><value><boolean>0</boolean></value></param>' .
            '<param><value>untyped</value></param>'
        ));

        $this->assertSame(0, $response->faultCode());
        $this->assertSame('received', $response->value()->scalarval());
        $this->assertSame(array('Café & tea', 42, 0, 'untyped'), $this->handler->message->output_parameters());
        $this->assertSame('example.receive', $this->handler->message->method_name);
        $this->assertSame("<methodResponse>\n<params>\n<param>\n<value>\n<string>received</string>\n</value>\n</param>\n</params>\n</methodResponse>", $response->prepare_response());
    }

    /**
     * Keep empty and structured parameters intact without invoking client code.
     *
     * @return void
     */
    public function testParsesEmptyAndStructuredParameters(): void
    {
        $this->server->parseRequest($this->request('example.receive',
            '<param><value><string></string></value></param>' .
            '<param><value><array><data><value><int>7</int></value><value><string>seven</string></value></data></array></value></param>' .
            '<param><value><struct><member><name>enabled</name><value><boolean>1</boolean></value></member></struct></value></param>'
        ));

        $params = $this->handler->message->params;
        $this->assertCount(3, $params);
        $this->assertSame('', $params[0]->scalarval());
        $this->assertSame(7, $params[1]->me['array'][0]->scalarval());
        $this->assertSame('seven', $params[1]->me['array'][1]->scalarval());
        $this->assertSame(1, $params[2]->me['struct']['enabled']->scalarval());
    }

    /**
     * Exercise method validation independently of the parser failures.
     *
     * @return void
     */
    public function testExecutesNonStaticMethodOnConfiguredInstance(): void
    {
        $message = new XML_RPC_Message('example.receive');
        $response = $this->server->_execute($message);

        $this->assertSame(0, $response->faultCode());
        $this->assertSame($message, $this->handler->message);
    }

    /**
     * Validate the configured receiver even when the mapping names another class.
     *
     * @return void
     */
    public function testValidatesTheConfiguredReceiver(): void
    {
        $this->server->methods['example.receive']['function'] = 'OtherHandler.receive';
        $response = $this->server->_execute(new XML_RPC_Message('example.receive'));

        $this->assertSame(0, $response->faultCode());
        $this->assertInstanceOf(XML_RPC_Message::class, $this->handler->message);
    }

    /**
     * Retain dispatch through the EE singleton when no object was configured.
     *
     * @return void
     */
    public function testDispatchesToTheDefaultEeReceiver(): void
    {
        ee()->setMock('', $this->handler);
        $this->server->object = false;
        $this->server->methods['example.receive']['function'] = XmlrpcsTestStaticHandler::class . '.receive';
        $message = new XML_RPC_Message('example.receive');
        $response = $this->server->_execute($message);

        $this->assertSame(0, $response->faultCode());
        $this->assertSame($message, $this->handler->message);
    }

    /**
     * Keep uncallable default registrations from reaching the dynamic EE facade.
     *
     * @return void
     */
    public function testRejectsUnknownMethodsOnTheDefaultReceiver(): void
    {
        $this->server->object = false;
        $this->server->add_to_map('example.missing', 'AbsentHandler.missing', array(), '');
        $response = $this->server->_execute(new XML_RPC_Message('example.missing'));

        $this->assertInstanceOf(XML_RPC_Response::class, $response);
        $this->assertSame(1, (int) $response->faultCode());
        $this->assertNull($this->handler->message);
    }

    /**
     * Keep unknown-method faults for absent and uncallable registrations.
     *
     * @return void
     */
    public function testRejectsUnknownAndUncallableMethods(): void
    {
        $this->server->add_to_map('example.missing', XmlrpcsTestHandler::class . '.missing', array(), '');
        $this->server->add_to_map('example.private', XmlrpcsTestHandler::class . '.hidden', array(), '');
        $this->server->add_to_map('example.function', 'missing_xmlrpcs_test_function', array(), '');

        foreach (array('example.unknown', 'example.missing', 'example.private', 'example.function') as $method) {
            $response = $this->server->parseRequest($this->request($method));
            $this->assertSame(1, (int) $response->faultCode(), $method);
            $this->assertSame($this->server->xmlrpcstr['unknown_method'], $response->faultString());
        }

        $this->assertNull($this->handler->message);
    }

    /**
     * Reject methods absent from the receiver even if the named class is callable.
     *
     * @return void
     */
    public function testRejectsMethodsMissingFromTheConfiguredReceiver(): void
    {
        $this->server->object = new \stdClass();
        $this->server->methods['example.receive']['function'] = XmlrpcsTestStaticHandler::class . '.receive';
        $response = $this->server->_execute(new XML_RPC_Message('example.receive'));

        $this->assertSame(1, (int) $response->faultCode());
        $this->assertNull($this->handler->message);
    }

    /**
     * Preserve normal function and static callable registrations.
     *
     * @return void
     */
    public function testDispatchesFunctionAndStaticCallables(): void
    {
        $this->server->add_to_map('example.function', __NAMESPACE__ . '\\xmlrpcs_test_function', array(), '');
        $this->server->add_to_map('example.static', XmlrpcsTestStaticHandler::class . '::receive', array(), '');

        foreach (array('example.function' => 'function', 'example.static' => 'static') as $method => $expected) {
            $response = $this->server->parseRequest($this->request($method, '<param><value><string>parameter</string></value></param>'));
            $this->assertSame(0, $response->faultCode());
            $this->assertSame($expected . ':parameter', $response->value()->scalarval());
        }
    }

    /**
     * Keep introspection and disabled multicall behavior on the server object.
     *
     * @return void
     */
    public function testDispatchesSystemMethodsOnTheServer(): void
    {
        $response = $this->server->parseRequest($this->request('system.listMethods'));
        $methods = array_map(function ($value) {
            return $value->scalarval();
        }, $response->value()->me['array']);
        $this->assertContains('example.receive', $methods);
        $this->assertContains('system.listMethods', $methods);
        $this->assertSame(0, $response->faultCode());

        $response = $this->server->parseRequest($this->request('system.multicall'));
        $this->assertSame(1, (int) $response->faultCode());
        $this->assertNull($this->handler->message);
    }

    /**
     * Preserve signature faults before invoking the configured handler.
     *
     * @return void
     */
    public function testPreservesIncorrectParameterFault(): void
    {
        $this->server->methods['example.receive']['signature'] = array(array('string', 'int'));
        $response = $this->server->parseRequest($this->request('example.receive', '<param><value><string>text</string></value></param>'));

        $this->assertSame(3, (int) $response->faultCode());
        $this->assertSame('Incorrect parameters were passed to method: Wanted int, got text at param 1)', $response->faultString());
        $this->assertNull($this->handler->message);
    }

    /**
     * Return XML parser faults and avoid dispatch for malformed requests.
     *
     * @return void
     */
    public function testMalformedXmlReturnsTheExistingFault(): void
    {
        $response = $this->server->parseRequest("<methodCall>\n<methodName>example.receive</methodName>\n</methodResponse>");

        $this->assertSame('Mismatched tag', xml_error_string($response->faultCode() - $this->server->xmlrpcerrxml));
        $this->assertSame('XML error: Mismatched tag at line 3', $response->faultString());
        $this->assertStringContainsString('<name>faultCode</name>', $response->prepare_response());
        $this->assertNull($this->handler->message);
    }

    /**
     * Retain the invalid-return fault for recognized tags in invalid positions.
     *
     * @return void
     */
    public function testInvalidXmlRpcNestingReturnsTheExistingFault(): void
    {
        $response = $this->server->parseRequest('<methodCall><string>invalid placement</string></methodCall>');

        $this->assertSame(2, (int) $response->faultCode());
        $this->assertSame($this->server->xmlrpcstr['invalid_return'], $response->faultString());
        $this->assertNull($this->handler->message);
    }

    /**
     * Preserve both forms of successful-request debug output exactly.
     *
     * @return void
     */
    public function testPreservesDebugOutput(): void
    {
        $this->server->debug = true;
        ob_start();

        try {
            $response = $this->server->parseRequest($this->request('example.receive', '<param><value><string>debug</string></value></param>'));
        } finally {
            $output = ob_get_clean();
        }

        $plist = '0 - ' . print_r(get_object_vars(new XML_RPC_Values('debug', 'string')), true) . ";\n";
        $this->assertSame("<pre>---PLIST---\n" . $plist . "\n---PLIST END---\n\n</pre>", $output);
        $this->assertSame("<!-- DEBUG INFO:\n\n" . $plist . "\n END DEBUG-->\n", $this->server->debug_msg);
        $this->assertSame(0, $response->faultCode());
    }

    /**
     * Keep malformed-request debugging free of undefined parameter-list warnings.
     *
     * @return void
     */
    public function testMalformedXmlPreservesDebugCommentWithoutDispatch(): void
    {
        $this->server->debug = true;
        $response = $this->server->parseRequest('<methodCall>');

        $this->assertNotSame(0, $response->faultCode());
        $this->assertSame("<!-- DEBUG INFO:\n\n\n END DEBUG-->\n", $this->server->debug_msg);
        $this->assertNull($this->handler->message);
    }

    /**
     * Keep separate parser state when a server handles consecutive requests.
     *
     * @return void
     */
    public function testConsecutiveRequestsDoNotShareParameters(): void
    {
        $this->server->parseRequest($this->request('example.receive', '<param><value><string>first</string></value></param>'));
        $response = $this->server->parseRequest($this->request('example.receive'));

        $this->assertSame(0, $response->faultCode());
        $this->assertSame(array(), $this->handler->message->params);
    }

    /**
     * Build a request without using the XML-RPC client implementation.
     *
     * @param string $method The registered method name.
     * @param string $params Serialized parameter elements.
     * @return string
     */
    private function request($method, $params = '')
    {
        return '<?xml version="1.0" encoding="UTF-8"?><methodCall><methodName>' . $method . '</methodName><params>' . $params . '</params></methodCall>';
    }
}

class XmlrpcsTestHandler
{
    public $message;

    /**
     * Capture the message on this particular handler instance.
     *
     * @param XML_RPC_Message $message The dispatched request.
     * @return XML_RPC_Response
     */
    public function receive($message)
    {
        $this->message = $message;

        return new XML_RPC_Response(new XML_RPC_Values('received', 'string'));
    }

    /**
     * Represent an inaccessible handler method.
     *
     * @param XML_RPC_Message $message The dispatched request.
     * @return XML_RPC_Response
     */
    private function hidden($message)
    {
        return $this->receive($message);
    }
}

class XmlrpcsTestStaticHandler
{
    /**
     * Respond through a static callable with the parsed argument.
     *
     * @param XML_RPC_Message $message The dispatched request.
     * @return XML_RPC_Response
     */
    public static function receive($message)
    {
        return new XML_RPC_Response(new XML_RPC_Values('static:' . $message->params[0]->scalarval(), 'string'));
    }
}

/**
 * Respond through a standalone function with the parsed argument.
 *
 * @param XML_RPC_Message $message The dispatched request.
 * @return XML_RPC_Response
 */
function xmlrpcs_test_function($message)
{
    return new XML_RPC_Response(new XML_RPC_Values('function:' . $message->params[0]->scalarval(), 'string'));
}
