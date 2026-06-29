<?php

use PHPUnit\Framework\TestCase;

if (!defined('SYSPATH')) {
    define('SYSPATH', realpath(__DIR__ . '/../../../../../../..') . '/');
}

if (!defined('BASEPATH')) {
    define('BASEPATH', SYSPATH . 'ee/legacy/');
}

if (!defined('LD')) {
    define('LD', '{');
}

if (!defined('RD')) {
    define('RD', '}');
}

if (!function_exists('ee')) {
    require_once __DIR__ . '/../../../../eeObjectMock.php';
}

require_once __DIR__ . '/../../../../../Addons/pro_search/helpers/pro_search_helper.php';

class ProSearchHelperEncodeTest extends TestCase
{
    /**
     * Reset ExpressionEngine mocks after helper tests that touch ee().
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }
    }

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
     * pro_clean_string returns empty input unchanged without loading words.
     *
     * @dataProvider cleanStringEmptyInputProvider
     * @param mixed $value
     * @return void
     */
    public function testCleanStringReturnsEmptyInputWithoutLoadingWords($value): void
    {
        $loader = $this->getMockBuilder('stdClass')
            ->addMethods(['library'])
            ->getMock();
        $loader->expects($this->never())->method('library');
        ee()->setMock('load', $loader);

        $this->assertSame($value, pro_clean_string($value, ['ignored']));
    }

    /**
     * Empty legacy inputs for pro_clean_string.
     *
     * @return array
     */
    public function cleanStringEmptyInputProvider(): array
    {
        return [
            'empty string' => [''],
            'zero string' => ['0'],
            'zero integer' => [0],
            'null' => [null],
            'false' => [false],
        ];
    }

    /**
     * pro_clean_string loads words and returns the final cleaned string.
     *
     * @return void
     */
    public function testCleanStringLoadsWordsAndReturnsFinalCleanedString(): void
    {
        $loader = $this->getMockBuilder('stdClass')
            ->addMethods(['library'])
            ->getMock();
        $loader->expects($this->once())
            ->method('library')
            ->with('pro_search_words');
        ee()->setMock('load', $loader);

        $words = $this->getMockBuilder('stdClass')
            ->addMethods(['clean', 'remove_diacritics'])
            ->getMock();
        $words->expects($this->once())
            ->method('clean')
            ->with(' Cafe Search ', true)
            ->willReturn('cleaned cafe search');
        $words->expects($this->once())
            ->method('remove_diacritics')
            ->with('cleaned cafe search')
            ->willReturn('cafe search');
        ee()->setMock('pro_search_words', $words);

        $this->assertSame('cafe search', pro_clean_string(' Cafe Search ', ['cafe']));
    }

    /**
     * pro_clean_string casts the ignore argument before cleaning.
     *
     * @dataProvider cleanStringIgnoreCoercionProvider
     * @param mixed $ignore
     * @param bool $expectedIgnore
     * @return void
     */
    public function testCleanStringCastsIgnoreArgumentBeforeCleaning($ignore, bool $expectedIgnore): void
    {
        $loader = $this->getMockBuilder('stdClass')
            ->addMethods(['library'])
            ->getMock();
        $loader->expects($this->once())
            ->method('library')
            ->with('pro_search_words');
        ee()->setMock('load', $loader);

        $words = $this->getMockBuilder('stdClass')
            ->addMethods(['clean', 'remove_diacritics'])
            ->getMock();
        $words->expects($this->once())
            ->method('clean')
            ->with('alpha beta', $expectedIgnore)
            ->willReturn('clean alpha beta');
        $words->expects($this->once())
            ->method('remove_diacritics')
            ->with('clean alpha beta')
            ->willReturn('clean alpha beta');
        ee()->setMock('pro_search_words', $words);

        $this->assertSame('clean alpha beta', pro_clean_string('alpha beta', $ignore));
    }

    /**
     * Ignore argument coercion inputs for pro_clean_string.
     *
     * @return array
     */
    public function cleanStringIgnoreCoercionProvider(): array
    {
        return [
            'null ignore' => [null, false],
            'empty array ignore' => [[], false],
            'empty string ignore' => ['', false],
            'zero string ignore' => ['0', false],
            'stop word array ignore' => [['alpha'], true],
            'string ignore' => ['alpha', true],
            'integer ignore' => [1, true],
        ];
    }

    /**
     * pro_format applies the default HTML and EE parameter encoding.
     *
     * @return void
     */
    public function testFormatAppliesDefaultHtmlAndEeParameterEncoding(): void
    {
        $this->assertSame(
            'A &amp; &lt;B&gt; &#123;x&#125; &quot;q&quot;',
            pro_format('A & <B> {x} "q"')
        );
    }

    /**
     * pro_format applies requested string formats.
     *
     * @dataProvider formatProvider
     * @param string $value
     * @param string $format
     * @param string $expected
     * @return void
     */
    public function testFormatAppliesRequestedStringFormats(string $value, string $format, string $expected): void
    {
        $this->assertSame($expected, pro_format($value, $format));
    }

    /**
     * Format inputs for pro_format.
     *
     * @return array
     */
    public function formatProvider(): array
    {
        return [
            'url encoding' => [
                'A & <B> {x} "q"',
                'url',
                'A+%26+%3CB%3E+%7Bx%7D+%22q%22',
            ],
            'explicit html encoding' => [
                'A & <B> {x} "q"',
                'html',
                'A &amp; &lt;B&gt; &#123;x&#125; &quot;q&quot;',
            ],
            'ee encode' => [
                'A & <B> {x} "q"',
                'ee-encode',
                'A & <B> &#123;x&#125; &quot;q&quot;',
            ],
            'ee decode' => [
                'A &amp; &lt;B&gt; &#123;x&#125; &quot;q&quot;',
                'ee-decode',
                'A &amp; &lt;B&gt; {x} "q"',
            ],
            'unknown format passthrough' => [
                'A & <B> {x} "q"',
                'raw',
                'A & <B> {x} "q"',
            ],
        ];
    }

    /**
     * pro_format delegates clean formatting through Pro Search words.
     *
     * @return void
     */
    public function testFormatDelegatesCleanFormatThroughProSearchWords(): void
    {
        $loader = $this->getMockBuilder('stdClass')
            ->addMethods(['library'])
            ->getMock();
        $loader->expects($this->once())
            ->method('library')
            ->with('pro_search_words');
        ee()->setMock('load', $loader);

        $words = $this->getMockBuilder('stdClass')
            ->addMethods(['clean', 'remove_diacritics'])
            ->getMock();
        $words->expects($this->once())
            ->method('clean')
            ->with(' Café Search ', false)
            ->willReturn('cleaned café search');
        $words->expects($this->once())
            ->method('remove_diacritics')
            ->with('cleaned café search')
            ->willReturn('cleaned cafe search');
        ee()->setMock('pro_search_words', $words);

        $this->assertSame('cleaned cafe search', pro_format(' Café Search ', 'clean'));
    }

    /**
     * pro_format preserves clean format's empty-string guard.
     *
     * @return void
     */
    public function testFormatCleanReturnsEmptyStringWithoutLoadingWords(): void
    {
        $loader = $this->getMockBuilder('stdClass')
            ->addMethods(['library'])
            ->getMock();
        $loader->expects($this->never())->method('library');
        ee()->setMock('load', $loader);

        $this->assertSame('', pro_format('', 'clean'));
    }

    /**
     * pro_param_string builds legacy parameter strings from string values only.
     *
     * @dataProvider paramStringProvider
     * @param array $params
     * @param string $expected
     * @return void
     */
    public function testParamStringBuildsLegacyParameterStrings(array $params, string $expected): void
    {
        $actual = pro_param_string($params);

        $this->assertSame($expected, $actual);
        $this->assertIsString($actual);

        if ($expected === '') {
            return;
        }

        $this->assertSame($expected, trim($actual));
    }

    /**
     * Parameter string inputs.
     *
     * @return array
     */
    public function paramStringProvider(): array
    {
        return [
            'ordered string parameters' => [
                [
                    'keywords' => 'alpha beta',
                    'collection' => 'news',
                    'sort' => 'desc',
                ],
                'keywords="alpha beta" collection="news" sort="desc"',
            ],
            'string boundary values' => [
                [
                    'empty' => '',
                    'zero' => '0',
                    'spaces' => '  alpha  ',
                ],
                'empty="" zero="0" spaces="  alpha  "',
            ],
            'literal string values' => [
                [
                    'quote' => 'a "quoted" value',
                    'html' => '<b>&</b>',
                    'braces' => '{segment_1}',
                ],
                'quote="a "quoted" value" html="<b>&</b>" braces="{segment_1}"',
            ],
            'empty input' => [
                [],
                '',
            ],
            'all non-string values skipped' => [
                [
                    'none' => null,
                    'false' => false,
                    'true' => true,
                    'count' => 3,
                    'items' => ['alpha'],
                    'object' => new stdClass(),
                ],
                '',
            ],
            'mixed values preserve surviving string order' => [
                [
                    'before' => 'alpha',
                    'limit' => 10,
                    'after' => 'beta',
                    'zero_string' => '0',
                    'empty' => '',
                ],
                'before="alpha" after="beta" zero_string="0" empty=""',
            ],
        ];
    }

    /**
     * pro_param_string preserves its legacy null-input boundary.
     *
     * @return void
     */
    public function testParamStringTreatsLegacyNullInputAsEmptyString(): void
    {
        $this->assertSame('', @pro_param_string(null));
    }

    /**
     * pro_prep_in_conditionals expands legacy IN conditionals.
     *
     * @dataProvider prepInConditionalsProvider
     * @param string $tagdata
     * @param string $expected
     * @return void
     */
    public function testPrepInConditionalsExpandsLegacyConditionals(string $tagdata, string $expected): void
    {
        $actual = pro_prep_in_conditionals($tagdata);

        $this->assertSame($expected, $actual);
        $this->assertIsString($actual);
    }

    /**
     * Legacy IN conditional inputs.
     *
     * @return array
     */
    public function prepInConditionalsProvider(): array
    {
        return [
            'plain in values' => [
                'before {if status IN (open|closed)}body{/if} after',
                'before {if status == "open" OR status == "closed"}body{/if} after',
            ],
            'not in values' => [
                '{if entry_id NOT IN (1|2|3)}hidden{/if}',
                '{if entry_id != "1" AND entry_id != "2" AND entry_id != "3"}hidden{/if}',
            ],
            'compact not in hyphenated identifier' => [
                '{if field-name NOTIN (alpha|beta)}hidden{/if}',
                '{if field-name != "alpha" AND field-name != "beta"}hidden{/if}',
            ],
            'legacy ampersand separators' => [
                '{if category IN (news&amp;sports&arts)}',
                '{if category == "news" OR category == "sports" OR category == "arts"}',
            ],
            'quoted operands' => [
                '{if "status" IN ("open"|\'closed\'|pending)}',
                '{if "status" == "open" OR "status" == \'closed\' OR "status" == "pending"}',
            ],
            'multiple conditionals' => [
                'before {if foo IN (1|2)}A{/if} middle {if bar NOT IN (3|4)}B{/if} after',
                'before {if foo == "1" OR foo == "2"}A{/if} middle {if bar != "3" AND bar != "4"}B{/if} after',
            ],
            'empty item list boundary' => [
                '{if status IN ()}empty{/if}',
                '{if status == ""}empty{/if}',
            ],
        ];
    }

    /**
     * pro_prep_in_conditionals leaves non-matching tagdata unchanged.
     *
     * @return void
     */
    public function testPrepInConditionalsLeavesUnmatchedTagdataUnchanged(): void
    {
        $tagdata = '{if status in (open|closed)}body{/if} {if status == "open"}open{/if}';

        $this->assertSame($tagdata, pro_prep_in_conditionals($tagdata));
        $this->assertSame('', pro_prep_in_conditionals());
    }

    /**
     * pro_prep_word_list lowercases, filters duplicates, and sorts tokens.
     *
     * @return void
     */
    public function testPrepWordListNormalizesSortsAndDeduplicatesWords(): void
    {
        $input = "Beta beta\nAlpha, ALPHA can't!";
        $this->mockProMultibyteStrtolower($input);

        $this->assertSame("alpha beta can't", pro_prep_word_list($input));
    }

    /**
     * pro_prep_word_list preserves legacy punctuation cleanup rules.
     *
     * @return void
     */
    public function testPrepWordListRemovesPunctuationWithoutSplittingWords(): void
    {
        $input = "two-word email@example.com foo_bar O'Malley rock&roll";
        $this->mockProMultibyteStrtolower($input);

        $this->assertSame(
            "emailexamplecom foo_bar o'malley rockroll twoword",
            pro_prep_word_list($input)
        );
    }

    /**
     * pro_prep_word_list collapses whitespace-only input to an empty string.
     *
     * @return void
     */
    public function testPrepWordListReturnsEmptyStringForWhitespaceOnlyInput(): void
    {
        $input = " \n\t ";
        $this->mockProMultibyteStrtolower($input);

        $this->assertSame('', pro_prep_word_list($input));
    }

    /**
     * pro_prep_word_list handles its default empty input.
     *
     * @return void
     */
    public function testPrepWordListReturnsEmptyStringForDefaultInput(): void
    {
        $this->mockProMultibyteStrtolower('');

        $this->assertSame('', pro_prep_word_list());
    }

    /**
     * pro_prep_word_list normalizes the value returned by Pro_multibyte.
     *
     * @return void
     */
    public function testPrepWordListUsesReturnedMultibyteLowercaseValue(): void
    {
        $input = 'MiXeD Input';
        $this->mockProMultibyteStrtolower($input, 'gamma alpha gamma');

        $this->assertSame('alpha gamma', pro_prep_word_list($input));
    }

    /**
     * Mock Pro Search multibyte lowercasing for word-list helper tests.
     *
     * @param mixed $expectedInput
     * @param string|null $lowercaseResult
     * @return void
     */
    private function mockProMultibyteStrtolower($expectedInput, ?string $lowercaseResult = null): void
    {
        $multibyte = $this->getMockBuilder('stdClass')
            ->addMethods(['strtolower'])
            ->getMock();
        $multibyte->expects($this->once())
            ->method('strtolower')
            ->with($expectedInput)
            ->willReturn($lowercaseResult ?? mb_strtolower((string) $expectedInput));

        ee()->setMock('pro_multibyte', $multibyte);
    }

    /**
     * pro_chr decodes numeric and named character references.
     *
     * @dataProvider proChrCharacterReferenceProvider
     * @param mixed $value
     * @param string $expected
     * @return void
     */
    public function testProChrDecodesCharacterReferences($value, string $expected): void
    {
        $this->assertSame($expected, pro_chr($value));
    }

    /**
     * Character reference inputs for pro_chr.
     *
     * @return array
     */
    public function proChrCharacterReferenceProvider(): array
    {
        return [
            'decimal integer' => [65, 'A'],
            'decimal string' => ['65', 'A'],
            'numeric double quote with ENT_QUOTES' => [34, '"'],
            'numeric single quote with ENT_QUOTES' => [39, "'"],
            'named entity' => ['quot', '"'],
            'invalid named entity is preserved' => ['not-a-real-entity', '&not-a-real-entity;'],
            'invalid numeric code point is preserved' => [1114112, '&#1114112;'],
        ];
    }

    /**
     * pro_chr decodes UTF-8 code points without truncating bytes.
     *
     * @dataProvider proChrUtf8CodePointProvider
     * @param mixed $value
     * @param string $expectedHex
     * @return void
     */
    public function testProChrDecodesUtf8CodePoints($value, string $expectedHex): void
    {
        $actual = pro_chr($value);

        $this->assertSame($expectedHex, bin2hex($actual));
        $this->assertSame(strlen(hex2bin($expectedHex)), strlen($actual));
    }

    /**
     * UTF-8 code point inputs for pro_chr.
     *
     * @return array
     */
    public function proChrUtf8CodePointProvider(): array
    {
        return [
            'named copyright entity' => ['copy', 'c2a9'],
            'three-byte numeric code point' => [8364, 'e282ac'],
            'highest valid Unicode code point' => [1114111, 'f48fbfbf'],
        ];
    }

    /**
     * pro_hilite wraps literal needle matches in mark tags.
     *
     * @dataProvider hiliteMatchProvider
     * @param string $haystack
     * @param string $needle
     * @param string $expected
     * @return void
     */
    public function testHiliteWrapsLiteralNeedleMatches(string $haystack, string $needle, string $expected): void
    {
        $this->assertSame($expected, pro_hilite($haystack, $needle));
    }

    /**
     * Match inputs for pro_hilite.
     *
     * @return array
     */
    public function hiliteMatchProvider(): array
    {
        return [
            'multiple matches' => [
                'alpha beta alpha',
                'alpha',
                '<mark>alpha</mark> beta <mark>alpha</mark>',
            ],
            'regex metacharacters are literal' => [
                'Find a+b? before a+b?.',
                'a+b?',
                'Find <mark>a+b?</mark> before <mark>a+b?</mark>.',
            ],
            'regex delimiter is literal' => [
                'a#b#a#b',
                '#',
                'a<mark>#</mark>b<mark>#</mark>a<mark>#</mark>b',
            ],
            'case-sensitive matching' => [
                'Alpha alpha',
                'alpha',
                'Alpha <mark>alpha</mark>',
            ],
            'markup remains unescaped' => [
                '<p>alpha & beta</p>',
                'alpha & beta',
                '<p><mark>alpha & beta</mark></p>',
            ],
        ];
    }

    /**
     * pro_hilite returns the original haystack when no needle is matched.
     *
     * @return void
     */
    public function testHiliteReturnsOriginalHaystackWhenNeedleIsMissing(): void
    {
        $this->assertSame('alpha beta', pro_hilite('alpha beta', 'gamma'));
    }

    /**
     * pro_hilite preserves its legacy empty-needle boundary behavior.
     *
     * @return void
     */
    public function testHilitePreservesEmptyNeedleBoundaryBehavior(): void
    {
        $this->assertSame(
            '<mark></mark>a<mark></mark>b<mark></mark>c<mark></mark>',
            pro_hilite('abc', '')
        );
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
