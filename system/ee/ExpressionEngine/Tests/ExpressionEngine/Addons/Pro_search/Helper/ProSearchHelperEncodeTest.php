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

    /**
     * pro_strpos_all returns every literal match offset.
     *
     * @dataProvider strposAllMatchProvider
     * @param string $haystack
     * @param string $needle
     * @param array $expected
     * @return void
     */
    public function testStrposAllReturnsEveryLiteralMatchOffset(string $haystack, string $needle, array $expected): void
    {
        $actual = pro_strpos_all($haystack, $needle);

        $this->assertSame($expected, $actual);

        foreach ($actual as $offset) {
            $this->assertIsInt($offset);
        }
    }

    /**
     * Match inputs for pro_strpos_all.
     *
     * @return array
     */
    public function strposAllMatchProvider(): array
    {
        return [
            'single match' => ['alpha beta', 'beta', [6]],
            'multiple matches' => ['alpha beta alpha', 'alpha', [0, 11]],
            'regex metacharacter literal' => ['a.b.a.b', '.', [1, 3, 5]],
            'regex delimiter literal' => ['a#b#a#b', '#', [1, 3, 5]],
            'first and last positions' => ['abxxab', 'ab', [0, 4]],
        ];
    }

    /**
     * pro_strpos_all returns an empty array when no needle is matched.
     *
     * @dataProvider strposAllNoMatchProvider
     * @param string|null $haystack
     * @param string $needle
     * @return void
     */
    public function testStrposAllReturnsEmptyArrayWhenNoNeedleIsMatched($haystack, string $needle): void
    {
        $this->assertSame([], pro_strpos_all($haystack, $needle));
    }

    /**
     * No-match inputs for pro_strpos_all.
     *
     * @return array
     */
    public function strposAllNoMatchProvider(): array
    {
        return [
            'null haystack' => [null, 'a'],
            'empty haystack' => ['', 'a'],
            'missing needle' => ['abc', 'z'],
            'case-sensitive mismatch' => ['Alpha', 'alpha'],
            'needle longer than haystack' => ['ab', 'abc'],
        ];
    }

    /**
     * pro_substr_pad returns snippets for each supplied offset.
     *
     * @dataProvider substrPadSnippetProvider
     * @param string $haystack
     * @param array $positions
     * @param int $length
     * @param int $pad
     * @param array $expected
     * @return void
     */
    public function testSubstrPadReturnsSnippetsForOffsets(
        string $haystack,
        array $positions,
        int $length,
        int $pad,
        array $expected
    ): void {
        $actual = pro_substr_pad($haystack, $positions, $length, $pad);

        $this->assertSame($expected, $actual);

        foreach ($actual as $snippet) {
            $this->assertIsString($snippet);
        }
    }

    /**
     * Snippet extraction inputs for pro_substr_pad.
     *
     * @return array
     */
    public function substrPadSnippetProvider(): array
    {
        return [
            'exact length without padding' => [
                'alpha beta gamma',
                [6],
                4,
                0,
                ['beta'],
            ],
            'left and right padding around match' => [
                'alpha beta gamma',
                [6],
                4,
                2,
                ['a beta g'],
            ],
            'left padding clamps to start' => [
                'alpha beta gamma',
                [1],
                3,
                5,
                ['alpha beta ga'],
            ],
            'right padding clamps to haystack end' => [
                'alpha beta gamma',
                [12],
                5,
                3,
                ['a gamma'],
            ],
            'zero length can return surrounding padding only' => [
                'alpha beta gamma',
                [6],
                0,
                2,
                ['a be'],
            ],
            'multiple offsets preserve supplied order' => [
                'alpha beta gamma beta',
                [6, 0],
                5,
                0,
                ['beta ', 'alpha'],
            ],
        ];
    }

    /**
     * pro_substr_pad returns an empty array when no offsets are supplied.
     *
     * @return void
     */
    public function testSubstrPadReturnsEmptyArrayWithoutOffsets(): void
    {
        $this->assertSame([], pro_substr_pad('alpha beta gamma', [], 4, 2));
    }

    /**
     * pro_substr_pad returns an empty array for legacy null offsets.
     *
     * @return void
     */
    public function testSubstrPadReturnsEmptyArrayForNullOffsets(): void
    {
        $this->assertSame([], @pro_substr_pad('alpha beta gamma', null, 4, 2));
    }
}
