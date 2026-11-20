<?php

namespace ExpressionEngine\Tests\Library\Curl;

use ExpressionEngine\Library\Curl\GetRequest;
use ExpressionEngine\Library\Curl\PostRequest;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    protected function setUp(): void
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('The curl extension is required.');
        }
    }

    /** @dataProvider requestShapes */
    public function testOnlyPublicObjectPropertiesAreEncoded($method, $nested)
    {
        $payload = new class {
            public $q = 'value';
            private $secret = 'private';
            protected $hidden = 'protected';
        };
        $this->assertQuery('q=value', $payload, $method, $nested);
    }

    /** @dataProvider hookedPayloads */
    public function testPropertyHooksAreNotExecuted($definition, $method, $nested)
    {
        if (PHP_VERSION_ID < 80400) {
            $this->markTestSkipped('Property hooks require PHP 8.4 or later.');
        }
        // Keep newer syntax out of files parsed by supported older PHP versions.
        $payload = eval('return new class { ' . $definition . ' };');
        $this->assertQuery('q=value', $payload, $method, $nested);
    }

    public function testFieldsAreSeparatedTheSameWayWhateverPhpUsesForLinks()
    {
        $configured = ini_get('arg_separator.output');
        ini_set('arg_separator.output', '&amp;');

        try {
            $post = new PostRequest('https://example.test/', ['license' => 'abc', 'addons' => ['pro' => ['slug' => 'pro']]]);
            $get = new GetRequest('https://example.test/', ['a' => 1, 'b' => 2]);
        } finally {
            ini_set('arg_separator.output', $configured);
        }

        $this->assertSame('license=abc&addons%5Bpro%5D%5Bslug%5D=pro', $post->config[CURLOPT_POSTFIELDS]);
        $this->assertSame('a=1&b=2', parse_url($get->config[CURLOPT_URL], PHP_URL_QUERY));
    }

    /** @dataProvider responsesWithHeaders */
    public function testTheBodyIsWhatFollowsTheLastSetOfHeaders($before)
    {
        $headers = "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nX-Trace: abc";
        $body = "{\"messageType\":\"success\"}\r\n\r\nwith a blank line of its own";

        $request = new class('https://example.test/') extends GetRequest {
            public function split($response, $header_size)
            {
                return $this->splitResponse($response, $header_size);
            }
        };

        $all_headers = $before . $headers . "\r\n\r\n";

        $this->assertSame([$headers, $body], $request->split($all_headers . $body, strlen($all_headers)));
        $this->assertSame([$headers, ''], $request->split($all_headers, strlen($all_headers)));
    }

    public function testWithoutASizeFromCurlTheFirstBlankLineEndsTheHeaders()
    {
        $request = new class('https://example.test/') extends GetRequest {
            public function split($response, $header_size)
            {
                return $this->splitResponse($response, $header_size);
            }
        };

        $this->assertSame(['HTTP/1.1 200 OK', "body\r\n\r\nmore"], $request->split("HTTP/1.1 200 OK\r\n\r\nbody\r\n\r\nmore", 0));
        $this->assertSame(['no blank line', ''], $request->split('no blank line', 0));
    }

    public function responsesWithHeaders()
    {
        return [
            'nothing before the response' => [''],
            'a proxy opening the connection' => ["HTTP/1.1 200 Connection established\r\n\r\n"],
            'the server asking for the rest of a large request' => ["HTTP/1.1 100 Continue\r\n\r\n"],
            'a redirect that was followed' => ["HTTP/1.1 301 Moved Permanently\r\nLocation: https://example.test/new\r\n\r\n"],
            'a proxy and then an interim response' => ["HTTP/1.1 200 Connection established\r\n\r\nHTTP/1.1 100 Continue\r\n\r\n"],
        ];
    }

    public function requestShapes()
    {
        return [
            'GET object' => ['GET', false],
            'GET nested object' => ['GET', true],
            'POST object' => ['POST', false],
            'POST nested object' => ['POST', true],
        ];
    }

    public function hookedPayloads()
    {
        $definitions = [
            'stored value' => 'public string $q = "value" { get => strtoupper($this->q); }',
            'computed property' => 'public string $q = "value"; public string $computed { get => "extra"; }',
            'throwing getter' => 'public string $q = "value"; public string $computed { get { throw new \RuntimeException("Getter must not execute"); } }',
        ];
        $cases = [];
        foreach ($definitions as $name => $definition) {
            foreach ($this->requestShapes() as $shape => $arguments) {
                $cases[$name . ': ' . $shape] = array_merge([$definition], $arguments);
            }
        }
        return $cases;
    }

    private function assertQuery($expected, $payload, $method, $nested)
    {
        if ($nested) {
            $payload = ['payload' => $payload];
            $expected = 'payload%5Bq%5D=value';
        }
        if ($method === 'GET') {
            $request = new GetRequest('https://example.test/', $payload);
            $actual = parse_url($request->config[CURLOPT_URL], PHP_URL_QUERY);
        } else {
            $request = new PostRequest('https://example.test/', $payload);
            $actual = $request->config[CURLOPT_POSTFIELDS];
        }
        $this->assertSame($expected, $actual);
    }
}
