<?php

use PHPUnit\Framework\TestCase;

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/../../../../../../legacy/');
}

require_once __DIR__ . '/../../../../../Addons/pro_search/helpers/pro_search_helper.php';

class ProSearchHelperEncodeTest extends TestCase
{
    /**
     * pro_search_encode filters empty values before encoding.
     *
     * @return void
     */
    public function testFiltersEmptyValuesBeforeEncoding(): void
    {
        $encoded = pro_search_encode([
            'keywords' => 'kittens',
            'empty_string' => '',
            'null_value' => null,
            'false_value' => false,
            'zero_string' => '0',
            'zero_int' => 0,
        ]);

        $this->assertSame([
            'keywords' => 'kittens',
            'zero_string' => '0',
            'zero_int' => 0,
        ], pro_search_decode($encoded));
    }

    /**
     * pro_search_encode returns raw JSON when URL encoding is disabled.
     *
     * @return void
     */
    public function testReturnsJsonWhenUrlEncodingIsDisabled(): void
    {
        $encoded = pro_search_encode([
            'keywords' => 'alpha beta',
            'collection' => 'news',
        ], false);

        $this->assertSame('{"keywords":"alpha beta","collection":"news"}', $encoded);
    }

    /**
     * pro_search_encode emits JSON objects for list-style arrays.
     *
     * @return void
     */
    public function testForcesJsonObjectShapeForSequentialArrays(): void
    {
        $encoded = pro_search_encode(['alpha', 'beta'], false);

        $this->assertSame('{"0":"alpha","1":"beta"}', $encoded);
    }

    /**
     * pro_search_encode creates URL-safe base64 without padding.
     *
     * @return void
     */
    public function testCreatesUrlSafeBase64WithoutPadding(): void
    {
        $encoded = pro_search_encode([
            'slash' => '////',
            'keywords' => 'alpha beta',
        ]);

        $expectedJson = json_encode([
            'slash' => '////',
            'keywords' => 'alpha beta',
        ], JSON_FORCE_OBJECT);

        $this->assertSame(rtrim(str_replace('/', '_', base64_encode($expectedJson)), '='), $encoded);
        $this->assertStringNotContainsString('/', $encoded);
        $this->assertStringNotContainsString('=', $encoded);
        $this->assertSame(['slash' => '////', 'keywords' => 'alpha beta'], pro_search_decode($encoded));
    }

    /**
     * pro_search_encode preserves non-empty nested values through decode.
     *
     * @return void
     */
    public function testPreservesNestedValuesThroughDecode(): void
    {
        $params = [
            'keywords' => 'alpha',
            'filters' => [
                'category' => ['1', '2'],
                'range' => ['from' => 10, 'to' => 20],
            ],
        ];

        $this->assertSame($params, pro_search_decode(pro_search_encode($params)));
    }

    /**
     * pro_search_encode returns an encoded empty JSON object for empty input.
     *
     * @return void
     */
    public function testEmptyInputEncodesEmptyJsonObject(): void
    {
        $this->assertSame('e30', pro_search_encode([]));
        $this->assertSame('{}', pro_search_encode([], false));
    }

    /**
     * pro_search_decode returns an empty array for invalid input.
     *
     * @dataProvider invalidDecodeInputProvider
     * @param mixed $value
     * @return void
     */
    public function testDecodeReturnsEmptyArrayForInvalidInput($value): void
    {
        $this->assertSame([], pro_search_decode($value));
    }

    /**
     * Invalid input values for pro_search_decode.
     *
     * @return array
     */
    public function invalidDecodeInputProvider(): array
    {
        return [
            'empty string' => [''],
            'null' => [null],
            'false' => [false],
            'zero integer' => [0],
            'array' => [[]],
        ];
    }

    /**
     * pro_search_decode returns raw JSON arrays when URL decoding is disabled.
     *
     * @return void
     */
    public function testDecodeReturnsRawJsonWhenUrlDecodingIsDisabled(): void
    {
        $rawJson = '{"keywords":"alpha beta","filters":{"category":["1","2"]}}';

        $this->assertSame([
            'keywords' => 'alpha beta',
            'filters' => [
                'category' => ['1', '2'],
            ],
        ], pro_search_decode($rawJson, false));
    }

    /**
     * pro_search_decode returns raw serialized arrays when URL decoding is disabled.
     *
     * @return void
     */
    public function testDecodeReturnsRawSerializedArraysWhenUrlDecodingIsDisabled(): void
    {
        $params = [
            'keywords' => 'serialized',
            'collection' => 'articles',
        ];

        $this->assertSame($params, pro_search_decode(serialize($params), false));
    }

    /**
     * pro_search_decode forces URL decoding for legacy serialized payloads.
     *
     * @return void
     */
    public function testDecodeForcesUrlDecodingForLegacySerializedPayloads(): void
    {
        $params = [
            'keywords' => 'legacy',
            'collection' => 'archive',
        ];
        $encoded = base64_encode(serialize($params));

        $this->assertStringStartsWith('YTo', $encoded);
        $this->assertSame($params, pro_search_decode($encoded, false));
    }

    /**
     * pro_search_decode repairs URI spaces before base64 decoding.
     *
     * @return void
     */
    public function testDecodeRepairsUriSpacesBeforeBase64Decoding(): void
    {
        $params = ['k' => '/>'];
        $encoded = base64_encode(json_encode($params, JSON_FORCE_OBJECT));

        $this->assertStringContainsString('+', $encoded);
        $this->assertSame($params, pro_search_decode(str_replace('+', ' ', $encoded)));
    }

    /**
     * pro_search_decode restores URL-safe underscores before base64 decoding.
     *
     * @return void
     */
    public function testDecodeRestoresUrlSafeUnderscoresBeforeBase64Decoding(): void
    {
        $params = ['k' => '/?'];
        $encoded = base64_encode(json_encode($params, JSON_FORCE_OBJECT));
        $urlSafe = rtrim(str_replace('/', '_', $encoded), '=');

        $this->assertStringContainsString('/', $encoded);
        $this->assertStringContainsString('_', $urlSafe);
        $this->assertStringNotContainsString('/', $urlSafe);
        $this->assertSame($params, pro_search_decode($urlSafe));
    }

    /**
     * pro_search_decode returns an empty array when the decoded payload is not an array.
     *
     * @return void
     */
    public function testDecodeReturnsEmptyArrayWhenDecodedPayloadIsNotArray(): void
    {
        $encodedScalar = base64_encode(json_encode('scalar'));

        $this->assertSame([], pro_search_decode($encodedScalar));
    }
}
