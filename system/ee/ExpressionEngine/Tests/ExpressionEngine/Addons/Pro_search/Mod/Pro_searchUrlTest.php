<?php

require_once __DIR__ . '/Pro_searchTestBase.php';

class Pro_searchUrlTest extends Pro_searchTestBase
{
    private function decodePayloadFromUrl(string $url): array
    {
        $parts = explode('/', rtrim($url, '/'));
        $encoded = end($parts);
        $json = base64_decode($encoded);
        $arr = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    }

    public function testUrlGeneratesEncodedLinkWithForceProtocolAndToggle()
    {
        // Existing params include tags a|b so toggle removes b
        $this->setParamsStub([
            'tags' => 'a|b',
        ]);

        // Tag parameters include a normal param, force_protocol and toggle:tags
        $this->setTemplateParams([
            'foo' => 'bar',
            'force_protocol' => 'http',
            'toggle:tags' => 'b',
        ]);

        $url = $this->pro->url();

        // Protocol forced to http
        $this->assertStringStartsWith('http://', $url);

        // Encoded payload includes provided params and updated tags
        $payload = $this->decodePayloadFromUrl($url);
        $this->assertSame('bar', $payload['foo'] ?? null);
        $this->assertSame('a', $payload['tags'] ?? null); // b toggled off

        // force_protocol is removed from encoded payload
        $this->assertArrayNotHasKey('force_protocol', $payload);
    }

    public function testUrlGeneratesQueryStringWhenNotEncodingAndRespectsForceProtocol()
    {
        // Disable encoding
        $this->setSettingsStub([
            'encode_query' => 'n',
            'default_result_page' => '/search/results',
            'can_manage_shortcuts' => [],
            'build_index_act_key' => 'secret'
        ]);

        $this->setTemplateParams([
            'result_page' => '/search/results',
            'bar' => 'baz',
            'force_protocol' => 'http',
        ]);

        $url = $this->pro->url();

        // Protocol forced to http
        $this->assertStringStartsWith('http://', $url);
        // Should be standard query string without result_page
        $this->assertStringContainsString('/search/results?', $url);
        $this->assertStringContainsString('bar=baz', $url);
        $this->assertStringNotContainsString('result_page=', $url);
    }
}


