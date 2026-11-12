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
