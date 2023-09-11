<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
if (! function_exists('xml_parser_create')) {
    show_error('Your PHP installation does not support XML');
}

/**
 * XML-RPC request handler class
 */
class EE_Xmlrpc
{
    public $debug = false;// Debugging on or off
    public $xmlrpcI4 = 'i4';
    public $xmlrpcInt = 'int';
    public $xmlrpcBoolean = 'boolean';
    public $xmlrpcDouble = 'double';
    public $xmlrpcString = 'string';
    public $xmlrpcDateTime = 'dateTime.iso8601';
    public $xmlrpcBase64 = 'base64';
    public $xmlrpcArray = 'array';
    public $xmlrpcStruct = 'struct';

    public $xmlrpcTypes = array();
    public $valid_parents = array();
    public $xmlrpcerr = array(); // Response numbers
    public $xmlrpcstr = array();  // Response strings

    public $xmlrpc_defencoding = 'UTF-8';
    public $xmlrpcName = 'XML-RPC for ExpressionEngine';
    public $xmlrpcVersion = '1.1';
    public $xmlrpcerruser = 800; // Start of user errors
    public $xmlrpcerrxml = 100; // Start of XML Parse errors
    public $xmlrpc_backslash = ''; // formulate backslashes for escaping regexp

    public $client;
    public $method;
    public $data;
    public $message = '';
    public $error = ''; // Error string for request
    public $errstr = '';
    public $result;
    public $response = array();  // Response from remote server

    public $xss_clean = true;

    //-------------------------------------
    //  VALUES THAT MULTIPLE CLASSES NEED
    //-------------------------------------

    public function __construct($config = array())
    {
        $this->xmlrpcName = $this->xmlrpcName;
        $this->xmlrpc_backslash = chr(92) . chr(92);

        // Types for info sent back and forth
        $this->xmlrpcTypes = array(
            $this->xmlrpcI4 => '1',
            $this->xmlrpcInt => '1',
            $this->xmlrpcBoolean => '1',
            $this->xmlrpcString => '1',
            $this->xmlrpcDouble => '1',
            $this->xmlrpcDateTime => '1',
            $this->xmlrpcBase64 => '1',
            $this->xmlrpcArray => '2',
            $this->xmlrpcStruct => '3'
        );

        // Array of Valid Parents for Various XML-RPC elements
        $this->valid_parents = array('BOOLEAN' => array('VALUE'),
            'I4' => array('VALUE'),
            'INT' => array('VALUE'),
            'STRING' => array('VALUE'),
            'DOUBLE' => array('VALUE'),
            'DATETIME.ISO8601' => array('VALUE'),
            'BASE64' => array('VALUE'),
            'ARRAY' => array('VALUE'),
            'STRUCT' => array('VALUE'),
            'PARAM' => array('PARAMS'),
            'METHODNAME' => array('METHODCALL'),
            'PARAMS' => array('METHODCALL', 'METHODRESPONSE'),
            'MEMBER' => array('STRUCT'),
            'NAME' => array('MEMBER'),
            'DATA' => array('ARRAY'),
            'FAULT' => array('METHODRESPONSE'),
            'VALUE' => array('MEMBER', 'DATA', 'PARAM', 'FAULT')
        );

        // XML-RPC Responses
        $this->xmlrpcerr['unknown_method'] = '1';
        $this->xmlrpcstr['unknown_method'] = 'This is not a known method for this XML-RPC Server';
        $this->xmlrpcerr['invalid_return'] = '2';
        $this->xmlrpcstr['invalid_return'] = 'The XML data received was either invalid or not in the correct form for XML-RPC.  Turn on debugging to examine the XML data further.';
        $this->xmlrpcerr['incorrect_params'] = '3';
        $this->xmlrpcstr['incorrect_params'] = 'Incorrect parameters were passed to method';
        $this->xmlrpcerr['introspect_unknown'] = '4';
        $this->xmlrpcstr['introspect_unknown'] = "Cannot inspect signature for request: method unknown";
        $this->xmlrpcerr['http_error'] = '5';
        $this->xmlrpcstr['http_error'] = "Did not receive a '200 OK' response from remote server.";
        $this->xmlrpcerr['no_data'] = '6';
        $this->xmlrpcstr['no_data'] = 'No data received from server.';

        $this->initialize($config);

        log_message('debug', "XML-RPC Class Initialized");
    }

    //-------------------------------------
    //  Initialize Prefs
    //-------------------------------------

    public function initialize($config = array())
    {
        if (count($config) > 0) {
            foreach ($config as $key => $val) {
                if (isset($this->$key)) {
                    $this->$key = $val;
                }
            }
        }
    }
    // END

    //-------------------------------------
    //  Take URL and parse it
    //-------------------------------------

    public function server($url, $port = 80)
    {
        if (substr($url, 0, 4) != "http") {
            $url = "http://" . $url;
        }

        $parts = parse_url($url);

        $path = (! isset($parts['path'])) ? '/' : $parts['path'];

        if (isset($parts['query']) && $parts['query'] != '') {
            $path .= '?' . $parts['query'];
        }

        $this->client = new XML_RPC_Client($path, $parts['host'], $port);
    }
    // END

    //-------------------------------------
    //  Set Timeout
    //-------------------------------------

    public function timeout($seconds = 5)
    {
        if (! is_null($this->client) && is_int($seconds)) {
            $this->client->timeout = $seconds;
        }
    }
    // END

    //-------------------------------------
    //  Set Methods
    //-------------------------------------

    public function method($function)
    {
        $this->method = $function;
    }
    // END

    //-------------------------------------
    //  Take Array of Data and Create Objects
    //-------------------------------------

    public function request($incoming)
    {
        if (! is_array($incoming)) {
            // Send Error
        }

        $this->data = array();

        foreach ($incoming as $key => $value) {
            $this->data[$key] = $this->values_parsing($value);
        }
    }
    // END

    //-------------------------------------
    //  Set Debug
    //-------------------------------------

    public function set_debug($flag = true)
    {
        $this->debug = ($flag == true) ? true : false;
    }

    //-------------------------------------
    //  Values Parsing
    //-------------------------------------

    public function values_parsing($value, $return = false)
    {
        if (is_array($value) && array_key_exists(0, $value)) {
            if (! isset($value['1']) or (! isset($this->xmlrpcTypes[$value['1']]))) {
                if (is_array($value[0])) {
                    $temp = new XML_RPC_Values($value['0'], 'array');
                } else {
                    $temp = new XML_RPC_Values($value['0'], 'string');
                }
            } elseif (is_array($value['0']) && ($value['1'] == 'struct' or $value['1'] == 'array')) {
                foreach (array_keys($value[0]) as $k) {
                    $value[0][$k] = $this->values_parsing($value[0][$k]);
                }

                $temp = new XML_RPC_Values($value['0'], $value['1']);
            } else {
                $temp = new XML_RPC_Values($value['0'], $value['1']);
            }
        } else {
            $temp = new XML_RPC_Values($value, 'string');
        }

        return $temp;
    }
    // END

    //-------------------------------------
    //  Sends XML-RPC Request
    //-------------------------------------

    public function send_request()
    {
        $this->message = new XML_RPC_Message($this->method, $this->data);
        $this->message->debug = $this->debug;

        if (! $this->result = $this->client->send($this->message)) {
            $this->error = $this->result->errstr;

            return false;
        } elseif (! is_object($this->result->val)) {
            $this->error = $this->result->errstr;

            return false;
        }

        $this->response = $this->result->decode();

        return true;
    }
    // END

    //-------------------------------------
    //  Returns Error
    //-------------------------------------

    public function display_error()
    {
        return $this->error;
    }
    // END

    //-------------------------------------
    //  Returns Remote Server Response
    //-------------------------------------

    public function display_response()
    {
        return $this->response;
    }
    // END

    //-------------------------------------
    //  Sends an Error Message for Server Request
    //-------------------------------------

    public function send_error_message($number, $message)
    {
        return new XML_RPC_Response('0', $number, $message);
    }
    // END

    //-------------------------------------
    //  Send Response for Server Request
    //-------------------------------------

    public function send_response($response)
    {
        // $response should be array of values, which will be parsed
        // based on their data and type into a valid group of XML-RPC values

        $response = $this->values_parsing($response);

        return new XML_RPC_Response($response);
    }
    // END
}
// END XML_RPC Class

/**
 * XML-RPC Client class
 */
class XML_RPC_Client extends EE_Xmlrpc
{
    public $path = '';
    public $server = '';
    public $port = 80;
    public $errno = '';
    public $errstring = '';
    public $timeout = 5;
    public $no_multicall = false;

    public function __construct($path, $server, $port = 80)
    {
        parent::__construct();

        $this->port = $port;
        $this->server = $server;
        $this->path = $path;
    }

    public function send($msg)
    {
        if (is_array($msg)) {
            // Multi-call disabled
            $r = new XML_RPC_Response(0, $this->xmlrpcerr['multicall_recursion'], $this->xmlrpcstr['multicall_recursion']);

            return $r;
        }

        return $this->sendPayload($msg);
    }

    public function sendPayload($msg)
    {
        $fp = @fsockopen($this->server, $this->port, $this->errno, $this->errstr, $this->timeout);

        if (! is_resource($fp)) {
            error_log($this->xmlrpcstr['http_error']);
            $r = new XML_RPC_Response(0, $this->xmlrpcerr['http_error'], $this->xmlrpcstr['http_error']);

            return $r;
        }

        if (empty($msg->payload)) {
            // $msg = XML_RPC_Messages
            $msg->createPayload();
        }

        $r = "\r\n";
        $op = "POST {$this->path} HTTP/1.0$r";
        $op .= "Host: {$this->server}$r";
        $op .= "Content-Type: text/xml$r";
        $op .= "User-Agent: {$this->xmlrpcName}$r";
        $op .= "Content-Length: " . strlen($msg->payload) . "$r$r";
        $op .= $msg->payload;

        if (! fputs($fp, $op, strlen($op))) {
            error_log($this->xmlrpcstr['http_error']);
            $r = new XML_RPC_Response(0, $this->xmlrpcerr['http_error'], $this->xmlrpcstr['http_error']);

            return $r;
        }
        $resp = $msg->parseResponse($fp);
        fclose($fp);

        return $resp;
    }
}
// end class XML_RPC_Client

/**
 * XML-RPC Response class
 */
class XML_RPC_Response
{
    public $val = 0;
    public $errno = 0;
    public $errstr = '';
    public $headers = array();
    public $xss_clean = true;

    public function __construct($val, $code = 0, $fstr = '')
    {
        if ($code != 0) {
            // error
            $this->errno = $code;
            $this->errstr = htmlentities($fstr);
        } elseif (! is_object($val)) {
            // programmer error, not an object
            error_log("Invalid type '" . gettype($val) . "' (value: $val) passed to XML_RPC_Response.  Defaulting to empty value.");
            $this->val = new XML_RPC_Values();
        } else {
            $this->val = $val;
        }
    }

    public function faultCode()
    {
        return $this->errno;
    }

    public function faultString()
    {
        return $this->errstr;
    }

    public function value()
    {
        return $this->val;
    }

    public function prepare_response()
    {
        $result = "<methodResponse>\n";
        if ($this->errno) {
            $result .= '<fault>
	<value>
		<struct>
			<member>
				<name>faultCode</name>
				<value><int>' . $this->errno . '</int></value>
			</member>
			<member>
				<name>faultString</name>
				<value><string>' . $this->errstr . '</string></value>
			</member>
		</struct>
	</value>
</fault>';
        } else {
            $result .= "<params>\n<param>\n" .
                    $this->val->serialize_class() .
                    "</param>\n</params>";
        }
        $result .= "\n</methodResponse>";

        return $result;
    }

    public function decode($array = false)
    {
        if ($array !== false && is_array($array)) {
            foreach ($array as $key => $val) {
                if (is_array($array[$key])) {
                    $array[$key] = $this->decode($array[$key]);
                } else {
                    $array[$key] = ($this->xss_clean) ? ee('Security/XSS')->clean($array[$key]) : $array[$key];
                }
            }

            $result = $array;
        } else {
            $result = $this->xmlrpc_decoder($this->val);

            if (is_array($result)) {
                $result = $this->decode($result);
            } else {
                $result = ($this->xss_clean) ? ee('Security/XSS')->clean($result) : $result;
            }
        }

        return $result;
    }

    //-------------------------------------
    //  XML-RPC Object to PHP Types
    //-------------------------------------

    public function xmlrpc_decoder($xmlrpc_val)
    {
        $kind = $xmlrpc_val->kindOf();

        if ($kind == 'scalar') {
            return $xmlrpc_val->scalarval();
        } elseif ($kind == 'array') {
            reset($xmlrpc_val->me);
            $b = current($xmlrpc_val->me);
            $arr = array();

            for ($i = 0, $size = count($b); $i < $size; $i++) {
                $arr[] = $this->xmlrpc_decoder($xmlrpc_val->me['array'][$i]);
            }

            return $arr;
        } elseif ($kind == 'struct') {
            reset($xmlrpc_val->me['struct']);
            $arr = array();

            foreach ($xmlrpc_val->me['struct'] as $key => $value) {
                $arr[$key] = $this->xmlrpc_decoder($value);
            }

            return $arr;
        }
    }

    //-------------------------------------
    //  ISO-8601 time to server or UTC time
    //-------------------------------------

    public function iso8601_decode($time, $utc = 0)
    {
        // return a timet in the localtime, or UTC
        $t = 0;
        if (preg_match('/([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})/', $time, $regs)) {
            if ($utc == 1) {
                $t = gmmktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
            } else {
                $t = mktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
            }
        }

        return $t;
    }
}
// End Response Class

/**
 * XML-RPC Message class
 */
class XML_RPC_Message extends EE_Xmlrpc
{
    public $payload;
    public $method_name;
    public $params = array();
    public $xh = array();

    public function __construct($method, $pars = 0)
    {
        parent::__construct();

        $this->method_name = $method;
        if (is_array($pars) && count($pars) > 0) {
            for ($i = 0; $i < count($pars); $i++) {
                // $pars[$i] = XML_RPC_Values
                $this->params[] = $pars[$i];
            }
        }
    }

    //-------------------------------------
    //  Create Payload to Send
    //-------------------------------------

    public function createPayload()
    {
        $this->payload = "<?xml version=\"1.0\"?" . ">\r\n<methodCall>\r\n";
        $this->payload .= '<methodName>' . $this->method_name . "</methodName>\r\n";
        $this->payload .= "<params>\r\n";

        for ($i = 0; $i < count($this->params); $i++) {
            // $p = XML_RPC_Values
            $p = $this->params[$i];
            $this->payload .= "<param>\r\n" . $p->serialize_class() . "</param>\r\n";
        }

        $this->payload .= "</params>\r\n</methodCall>\r\n";
    }

    //-------------------------------------
    //  Parse External XML-RPC Server's Response
    //-------------------------------------

    public function parseResponse($fp)
    {
        $data = '';

        while ($datum = fread($fp, 4096)) {
            $data .= $datum;
        }

        //-------------------------------------
        //  DISPLAY HTTP CONTENT for DEBUGGING
        //-------------------------------------

        if ($this->debug === true) {
            echo "<pre>";
            echo "---DATA---\n" . htmlspecialchars($data) . "\n---END DATA---\n\n";
            echo "</pre>";
        }

        //-------------------------------------
        //  Check for data
        //-------------------------------------

        if ($data == "") {
            error_log($this->xmlrpcstr['no_data']);
            $r = new XML_RPC_Response(0, $this->xmlrpcerr['no_data'], $this->xmlrpcstr['no_data']);

            return $r;
        }

        //-------------------------------------
        //  Check for HTTP 200 Response
        //-------------------------------------

        if (strncmp($data, 'HTTP', 4) == 0 && ! preg_match('/^HTTP\/[0-9\.]+ 200 /', $data)) {
            $errstr = substr($data, 0, strpos($data, "\n") - 1);
            $r = new XML_RPC_Response(0, $this->xmlrpcerr['http_error'], $this->xmlrpcstr['http_error'] . ' (' . $errstr . ')');

            return $r;
        }

        //-------------------------------------
        //  Create and Set Up XML Parser
        //-------------------------------------

        $parser = xml_parser_create($this->xmlrpc_defencoding);
        $parser_name = is_php(8) ? spl_object_hash($parser) : (string) $parser;

        $this->xh[$parser_name] = array();
        $this->xh[$parser_name]['isf'] = 0;
        $this->xh[$parser_name]['ac'] = '';
        $this->xh[$parser_name]['headers'] = array();
        $this->xh[$parser_name]['stack'] = array();
        $this->xh[$parser_name]['valuestack'] = array();
        $this->xh[$parser_name]['isf_reason'] = 0;

        xml_set_object($parser, $this);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);
        xml_set_element_handler($parser, 'open_tag', 'closing_tag');
        xml_set_character_data_handler($parser, 'character_data');
        //xml_set_default_handler($parser, 'default_handler');

        //-------------------------------------
        //  GET HEADERS
        //-------------------------------------

        $lines = explode("\r\n", $data);
        while (($line = array_shift($lines))) {
            if (strlen($line) < 1) {
                break;
            }
            $this->xh[$parser_name]['headers'][] = $line;
        }
        $data = implode("\r\n", $lines);

        //-------------------------------------
        //  PARSE XML DATA
        //-------------------------------------

        if (! xml_parse($parser, $data, strlen($data))) {
            $errstr = sprintf(
                'XML error: %s at line %d',
                xml_error_string(xml_get_error_code($parser)),
                xml_get_current_line_number($parser)
            );
            //error_log($errstr);
            $r = new XML_RPC_Response(0, $this->xmlrpcerr['invalid_return'], $this->xmlrpcstr['invalid_return']);
            xml_parser_free($parser);

            return $r;
        }
        xml_parser_free($parser);

        // ---------------------------------------
        //  Got Ourselves Some Badness, It Seems
        // ---------------------------------------

        if ($this->xh[$parser_name]['isf'] > 1) {
            if ($this->debug === true) {
                echo "---Invalid Return---\n";
                echo $this->xh[$parser_name]['isf_reason'];
                echo "---Invalid Return---\n\n";
            }

            $r = new XML_RPC_Response(0, $this->xmlrpcerr['invalid_return'], $this->xmlrpcstr['invalid_return'] . ' ' . $this->xh[$parser_name]['isf_reason']);

            return $r;
        } elseif (! is_object($this->xh[$parser_name]['value'])) {
            $r = new XML_RPC_Response(0, $this->xmlrpcerr['invalid_return'], $this->xmlrpcstr['invalid_return'] . ' ' . $this->xh[$parser_name]['isf_reason']);

            return $r;
        }

        //-------------------------------------
        //  DISPLAY XML CONTENT for DEBUGGING
        //-------------------------------------

        if ($this->debug === true) {
            echo "<pre>";

            if (count($this->xh[$parser_name]['headers']) > 0) {
                echo "---HEADERS---\n";
                foreach ($this->xh[$parser_name]['headers'] as $header) {
                    echo "$header\n";
                }
                echo "---END HEADERS---\n\n";
            }

            echo "---DATA---\n" . htmlspecialchars($data) . "\n---END DATA---\n\n";

            echo "---PARSED---\n" ;
            var_dump($this->xh[$parser_name]['value']);
            echo "\n---END PARSED---</pre>";
        }

        //-------------------------------------
        //  SEND RESPONSE
        //-------------------------------------

        $v = $this->xh[$parser_name]['value'];

        if ($this->xh[$parser_name]['isf']) {
            $errno_v = $v->me['struct']['faultCode'];
            $errstr_v = $v->me['struct']['faultString'];
            $errno = $errno_v->scalarval();

            if ($errno == 0) {
                // FAULT returned, errno needs to reflect that
                $errno = -1;
            }

            $r = new XML_RPC_Response($v, $errno, $errstr_v->scalarval());
        } else {
            $r = new XML_RPC_Response($v);
        }

        $r->headers = $this->xh[$parser_name]['headers'];

        return $r;
    }

    // ------------------------------------
    //  Begin Return Message Parsing section
    // ------------------------------------

    // quick explanation of components:
    //   ac - used to accumulate values
    //   isf - used to indicate a fault
    //   lv - used to indicate "looking for a value": implements
    //		the logic to allow values with no types to be strings
    //   params - used to store parameters in method calls
    //   method - used to store method name
    //	 stack - array with parent tree of the xml element,
    //			 used to validate the nesting of elements

    //-------------------------------------
    //  Start Element Handler
    //-------------------------------------

    public function open_tag($the_parser, $name, $attrs)
    {
        $parser_name = is_php(8) ? spl_object_hash($the_parser) : (string) $the_parser;

        // If invalid nesting, then return
        if ($this->xh[$parser_name]['isf'] > 1) {
            return;
        }

        // Evaluate and check for correct nesting of XML elements

        if (count($this->xh[$parser_name]['stack']) == 0) {
            if ($name != 'METHODRESPONSE' && $name != 'METHODCALL') {
                $this->xh[$parser_name]['isf'] = 2;
                $this->xh[$parser_name]['isf_reason'] = 'Top level XML-RPC element is missing';

                return;
            }
        } else {
            // not top level element: see if parent is OK
            if (! in_array($this->xh[$parser_name]['stack'][0], $this->valid_parents[$name], true)) {
                $this->xh[$parser_name]['isf'] = 2;
                $this->xh[$parser_name]['isf_reason'] = "XML-RPC element $name cannot be child of " . $this->xh[$parser_name]['stack'][0];

                return;
            }
        }

        switch ($name) {
            case 'STRUCT':
            case 'ARRAY':
                // Creates array for child elements

                $cur_val = array('value' => array(),
                    'type' => $name);

                array_unshift($this->xh[$parser_name]['valuestack'], $cur_val);

                break;
            case 'METHODNAME':
            case 'NAME':
                $this->xh[$parser_name]['ac'] = '';

                break;
            case 'FAULT':
                $this->xh[$parser_name]['isf'] = 1;

                break;
            case 'PARAM':
                $this->xh[$parser_name]['value'] = null;

                break;
            case 'VALUE':
                $this->xh[$parser_name]['vt'] = 'value';
                $this->xh[$parser_name]['ac'] = '';
                $this->xh[$parser_name]['lv'] = 1;

                break;
            case 'I4':
            case 'INT':
            case 'STRING':
            case 'BOOLEAN':
            case 'DOUBLE':
            case 'DATETIME.ISO8601':
            case 'BASE64':
                if ($this->xh[$parser_name]['vt'] != 'value') {
                    //two data elements inside a value: an error occurred!
                    $this->xh[$parser_name]['isf'] = 2;
                    $this->xh[$parser_name]['isf_reason'] = "'Twas a $name element following a " . $this->xh[$parser_name]['vt'] . " element inside a single value";

                    return;
                }

                $this->xh[$parser_name]['ac'] = '';

                break;
            case 'MEMBER':
                // Set name of <member> to nothing to prevent errors later if no <name> is found
                $this->xh[$parser_name]['valuestack'][0]['name'] = '';

                // Set NULL value to check to see if value passed for this param/member
                $this->xh[$parser_name]['value'] = null;

                break;
            case 'DATA':
            case 'METHODCALL':
            case 'METHODRESPONSE':
            case 'PARAMS':
                // valid elements that add little to processing
                break;
            default:
                /// An Invalid Element is Found, so we have trouble
                $this->xh[$parser_name]['isf'] = 2;
                $this->xh[$parser_name]['isf_reason'] = "Invalid XML-RPC element found: $name";

                break;
        }

        // Add current element name to stack, to allow validation of nesting
        array_unshift($this->xh[$parser_name]['stack'], $name);

        if ($name != 'VALUE') {
            $this->xh[$parser_name]['lv'] = 0;
        }
    }
    // END

    //-------------------------------------
    //  End Element Handler
    //-------------------------------------

    public function closing_tag($the_parser, $name)
    {
        $parser_name = is_php(8) ? spl_object_hash($the_parser) : (string) $the_parser;

        if ($this->xh[$parser_name]['isf'] > 1) {
            return;
        }

        // Remove current element from stack and set variable
        // NOTE: If the XML validates, then we do not have to worry about
        // the opening and closing of elements.  Nesting is checked on the opening
        // tag so we be safe there as well.

        $curr_elem = array_shift($this->xh[$parser_name]['stack']);

        switch ($name) {
            case 'STRUCT':
            case 'ARRAY':
                $cur_val = array_shift($this->xh[$parser_name]['valuestack']);
                $this->xh[$parser_name]['value'] = (! isset($cur_val['values'])) ? array() : $cur_val['values'];
                $this->xh[$parser_name]['vt'] = strtolower($name);

                break;
            case 'NAME':
                $this->xh[$parser_name]['valuestack'][0]['name'] = $this->xh[$parser_name]['ac'];

                break;
            case 'BOOLEAN':
            case 'I4':
            case 'INT':
            case 'STRING':
            case 'DOUBLE':
            case 'DATETIME.ISO8601':
            case 'BASE64':
                $this->xh[$parser_name]['vt'] = strtolower($name);

                if ($name == 'STRING') {
                    $this->xh[$parser_name]['value'] = $this->xh[$parser_name]['ac'];
                } elseif ($name == 'DATETIME.ISO8601') {
                    $this->xh[$parser_name]['vt'] = $this->xmlrpcDateTime;
                    $this->xh[$parser_name]['value'] = $this->xh[$parser_name]['ac'];
                } elseif ($name == 'BASE64') {
                    $this->xh[$parser_name]['value'] = base64_decode($this->xh[$parser_name]['ac']);
                } elseif ($name == 'BOOLEAN') {
                    // Translated BOOLEAN values to TRUE AND FALSE
                    if ($this->xh[$parser_name]['ac'] == '1') {
                        $this->xh[$parser_name]['value'] = true;
                    } else {
                        $this->xh[$parser_name]['value'] = false;
                    }
                } elseif ($name == 'DOUBLE') {
                    // we have a DOUBLE
                    // we must check that only 0123456789-.<space> are characters here
                    if (! preg_match('/^[+-]?[eE0-9\t \.]+$/', $this->xh[$parser_name]['ac'])) {
                        $this->xh[$parser_name]['value'] = 'ERROR_NON_NUMERIC_FOUND';
                    } else {
                        $this->xh[$parser_name]['value'] = (float) $this->xh[$parser_name]['ac'];
                    }
                } else {
                    // we have an I4/INT
                    // we must check that only 0123456789-<space> are characters here
                    if (! preg_match('/^[+-]?[0-9\t ]+$/', $this->xh[$parser_name]['ac'])) {
                        $this->xh[$parser_name]['value'] = 'ERROR_NON_NUMERIC_FOUND';
                    } else {
                        $this->xh[$parser_name]['value'] = (int) $this->xh[$parser_name]['ac'];
                    }
                }
                $this->xh[$parser_name]['ac'] = '';
                $this->xh[$parser_name]['lv'] = 3; // indicate we've found a value

                break;
            case 'VALUE':
                // This if() detects if no scalar was inside <VALUE></VALUE>
                if ($this->xh[$parser_name]['vt'] == 'value') {
                    $this->xh[$parser_name]['value'] = $this->xh[$parser_name]['ac'];
                    $this->xh[$parser_name]['vt'] = $this->xmlrpcString;
                }

                // build the XML-RPC value out of the data received, and substitute it
                $temp = new XML_RPC_Values($this->xh[$parser_name]['value'], $this->xh[$parser_name]['vt']);

                if (count($this->xh[$parser_name]['valuestack']) && $this->xh[$parser_name]['valuestack'][0]['type'] == 'ARRAY') {
                    // Array
                    $this->xh[$parser_name]['valuestack'][0]['values'][] = $temp;
                } else {
                    // Struct
                    $this->xh[$parser_name]['value'] = $temp;
                }

                break;
            case 'MEMBER':
                $this->xh[$parser_name]['ac'] = '';

                // If value add to array in the stack for the last element built
                if ($this->xh[$parser_name]['value']) {
                    $this->xh[$parser_name]['valuestack'][0]['values'][$this->xh[$parser_name]['valuestack'][0]['name']] = $this->xh[$parser_name]['value'];
                }

                break;
            case 'DATA':
                $this->xh[$parser_name]['ac'] = '';

                break;
            case 'PARAM':
                if ($this->xh[$parser_name]['value']) {
                    $this->xh[$parser_name]['params'][] = $this->xh[$parser_name]['value'];
                }

                break;
            case 'METHODNAME':
                $this->xh[$parser_name]['method'] = ltrim($this->xh[$parser_name]['ac']);

                break;
            case 'PARAMS':
            case 'FAULT':
            case 'METHODCALL':
            case 'METHODRESPONSE':
                // We're all good kids with nuthin' to do
                break;
            default:
                // End of an Invalid Element.  Taken care of during the opening tag though
                break;
        }
    }

    //-------------------------------------
    //  Parses Character Data
    //-------------------------------------

    public function character_data($the_parser, $data)
    {
        $parser_name = is_php(8) ? spl_object_hash($the_parser) : (string) $the_parser;

        if ($this->xh[$parser_name]['isf'] > 1) {
            return;
        } // XML Fault found already

        // If a value has not been found
        if ($this->xh[$parser_name]['lv'] != 3) {
            if ($this->xh[$parser_name]['lv'] == 1) {
                $this->xh[$parser_name]['lv'] = 2; // Found a value
            }

            if (! @isset($this->xh[$parser_name]['ac'])) {
                $this->xh[$parser_name]['ac'] = '';
            }

            $this->xh[$parser_name]['ac'] .= $data;
        }
    }

    public function addParam($par)
    {
        $this->params[] = $par;
    }

    public function output_parameters($array = false)
    {
        if ($array !== false && is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($array[$key])) {
                    $array[$key] = $this->output_parameters($array[$key]);
                } else {
                    // 'bits' is for the MetaWeblog API image bits
                    // @todo - this needs to be made more general purpose
                    $array[$key] = ($key == 'bits' or $this->xss_clean == false) ? $array[$key] : ee('Security/XSS')->clean($array[$key]);
                }
            }

            $parameters = $array;
        } else {
            $parameters = array();
            for ($i = 0; $i < count($this->params); $i++) {
                $a_param = $this->decode_message($this->params[$i]);

                if (is_array($a_param)) {
                    $parameters[] = $this->output_parameters($a_param);
                } else {
                    $parameters[] = ($this->xss_clean) ? ee('Security/XSS')->clean($a_param) : $a_param;
                }
            }
        }

        return $parameters;
    }

    public function decode_message($param)
    {
        if (empty($param)) {
            return $param;
        }
        $kind = $param->kindOf();

        if ($kind == 'scalar') {
            return $param->scalarval();
        } elseif ($kind == 'array') {
            reset($param->me);

            $b = array_values($param->me);

            $arr = array();

            for ($i = 0; $i < count($b); $i++) {
                $arr[] = $this->decode_message($param->me['array'][$i]);
            }

            return $arr;
        } elseif ($kind == 'struct') {
            reset($param->me['struct']);

            $arr = array();

            foreach ($param->me['struct'] as $key => $value) {
                $arr[$key] = $this->decode_message($value);
            }

            return $arr;
        }
    }
}
// End XML_RPC_Messages class

/**
 * XML-RPC Values class
 */
class XML_RPC_Values extends EE_Xmlrpc
{
    public $me = array();
    public $mytype = 0;

    public function __construct($val = -1, $type = '')
    {
        parent::__construct();

        if ($val != -1 or $type != '') {
            $type = $type == '' ? 'string' : $type;

            if ($this->xmlrpcTypes[$type] == 1) {
                $this->addScalar($val, $type);
            } elseif ($this->xmlrpcTypes[$type] == 2) {
                $this->addArray($val);
            } elseif ($this->xmlrpcTypes[$type] == 3) {
                $this->addStruct($val);
            }
        }
    }

    public function addScalar($val, $type = 'string')
    {
        $typeof = $this->xmlrpcTypes[$type];

        if ($this->mytype == 1) {
            echo '<strong>XML_RPC_Values</strong>: scalar can have only one value<br />';

            return 0;
        }

        if ($typeof != 1) {
            echo '<strong>XML_RPC_Values</strong>: not a scalar type ({$typeof})<br />';

            return 0;
        }

        if ($type == $this->xmlrpcBoolean) {
            if (strcasecmp($val, 'true') == 0 or $val == 1 or ($val == true && strcasecmp($val, 'false'))) {
                $val = 1;
            } else {
                $val = 0;
            }
        }

        if ($this->mytype == 2) {
            // adding to an array here
            $ar = $this->me['array'];
            $ar[] = new XML_RPC_Values($val, $type);
            $this->me['array'] = $ar;
        } else {
            // a scalar, so set the value and remember we're scalar
            $this->me[$type] = $val;
            $this->mytype = $typeof;
        }

        return 1;
    }

    public function addArray($vals)
    {
        if ($this->mytype != 0) {
            echo '<strong>XML_RPC_Values</strong>: already initialized as a [' . $this->kindOf() . ']<br />';

            return 0;
        }

        $this->mytype = $this->xmlrpcTypes['array'];
        $this->me['array'] = $vals;

        return 1;
    }

    public function addStruct($vals)
    {
        if ($this->mytype != 0) {
            echo '<strong>XML_RPC_Values</strong>: already initialized as a [' . $this->kindOf() . ']<br />';

            return 0;
        }
        $this->mytype = $this->xmlrpcTypes['struct'];
        $this->me['struct'] = $vals;

        return 1;
    }

    public function kindOf()
    {
        switch ($this->mytype) {
            case 3:
                return 'struct';

                break;
            case 2:
                return 'array';

                break;
            case 1:
                return 'scalar';

                break;
            default:
                return 'undef';
        }
    }

    public function serializedata($typ, $val)
    {
        $rs = '';

        switch ($this->xmlrpcTypes[$typ]) {
            case 3:
                // struct
                $rs .= "<struct>\n";
                reset($val);

                foreach ($val as $key2 => $val2) {
                    $rs .= "<member>\n<name>{$key2}</name>\n";
                    $rs .= $this->serializeval($val2);
                    $rs .= "</member>\n";
                }
                $rs .= '</struct>';

                break;
            case 2:
                // array
                $rs .= "<array>\n<data>\n";
                for ($i = 0; $i < count($val); $i++) {
                    $rs .= $this->serializeval($val[$i]);
                }
                $rs .= "</data>\n</array>\n";

                break;
            case 1:
                // others
                switch ($typ) {
                    case $this->xmlrpcBase64:
                        $rs .= "<{$typ}>" . base64_encode((string) $val) . "</{$typ}>\n";
                        break;
                    case $this->xmlrpcBoolean:
                        $rs .= "<{$typ}>" . ((bool) $val ? '1' : '0') . "</{$typ}>\n";
                        break;
                    case $this->xmlrpcString:
                        $rs .= "<{$typ}>" . htmlspecialchars((string) $val) . "</{$typ}>\n";
                        break;
                    default:
                        $rs .= "<{$typ}>{$val}</{$typ}>\n";
                        break;
                }
                // no break
            default:
                break;
        }

        return $rs;
    }

    public function serialize_class()
    {
        return $this->serializeval($this);
    }

    public function serializeval($o)
    {
        $array = $o->me;
        list($value, $type) = array(reset($array), key($array));

        return "<value>\n" . $this->serializedata($type, $value) . "</value>\n";
    }

    public function scalarval()
    {
        return reset($this->me);
    }

    //-------------------------------------
    // Encode time in ISO-8601 form.
    //-------------------------------------

    // Useful for sending time in XML-RPC

    public function iso8601_encode($time, $utc = 0)
    {
        if (version_compare(PHP_VERSION, '8.1', '>=')) {
            return date("%Y%m%dT%H:%M:%S", $time);
        }
        if ($utc == 1) {
            $t = strftime("%Y%m%dT%H:%M:%S", $time);
        } else {
            if (function_exists('gmstrftime')) {
                $t = gmstrftime("%Y%m%dT%H:%M:%S", $time);
            } else {
                $t = strftime("%Y%m%dT%H:%M:%S", $time - date('Z'));
            }
        }

        return $t;
    }
}
// END XML_RPC_Values Class

// EOF
