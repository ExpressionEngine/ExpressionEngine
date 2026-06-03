<?php

namespace ExpressionEngine\Tests\Service\Template\Variables;

require_once __DIR__ . '/../../../../eeObjectMock.php';

use ExpressionEngine\Service\Template\Variables\LegacyParser;
use PHPUnit\Framework\TestCase;

class LegacyParserTest extends TestCase
{
    public $parser;

    public function setUp(): void
    {
        if (! defined('LD')) {
            define('LD', '{');
        }
        if (! defined('RD')) {
            define('RD', '}');
        }
        $this->parser = new LegacyParser();
    }

    public function tearDown(): void
    {
        ee()->resetMocks();
        $this->parser = null;
    }

    /**
     * @dataProvider tagProvider
     */
    public function testParseVariableProperties($tag, $expected, $prefix = '')
    {
        $props = $this->parser->parseVariableProperties($tag, $prefix);
        $this->assertEquals($expected, $props);
    }

    public function testParseVariablePropertiesMarksNumericModifiersInvalidAndSkipsParams()
    {
        $props = $this->parser->parseVariableProperties('hello:123:trim param="ignored"');

        $this->assertSame('hello', $props['field_name']);
        $this->assertSame('trim', $props['modifier']);
        $this->assertSame('123:trim', $props['full_modifier']);
        $this->assertTrue($props['invalid_modifier']);
        $this->assertSame([], $props['params']);
        $this->assertSame(
            [
                '123' => [],
                'trim' => [],
            ],
            $props['all_modifiers']
        );
    }

    public function testParseVariablePropertiesMarksSingleNumericModifierInvalidAndSkipsParams()
    {
        $props = $this->parser->parseVariableProperties('hello:123 param="ignored"');

        $this->assertSame('hello', $props['field_name']);
        $this->assertSame('123', $props['modifier']);
        $this->assertSame('123', $props['full_modifier']);
        $this->assertTrue($props['invalid_modifier']);
        $this->assertSame([], $props['params']);
        $this->assertSame(
            [
                '123' => [],
            ],
            $props['all_modifiers']
        );
    }

    public function testParseVariablePropertiesParsesParameterAfterNewline()
    {
        $props = $this->parser->parseVariableProperties("hello\nparam='hey'");

        $this->assertSame('hello', $props['field_name']);
        $this->assertSame('', $props['modifier']);
        $this->assertFalse($props['invalid_modifier']);
        $this->assertSame(['param' => 'hey'], $props['params']);
    }

    public function testParseVariablePropertiesTrimsWhitespaceAfterPrefixBeforeParsing()
    {
        $props = $this->parser->parseVariableProperties("embed:   foo:rot13 param='hey'", 'embed:');

        $this->assertSame('foo', $props['field_name']);
        $this->assertSame('rot13', $props['modifier']);
        $this->assertFalse($props['invalid_modifier']);
        $this->assertSame(['param' => 'hey'], $props['params']);
    }

    public function tagProvider()
    {
        $tags = [
            [
                'hello',
                [
                    'field_name' => 'hello',
                    'params' => [],
                    'modifier' => '',
                    'full_modifier' => '',
                    'invalid_modifier' => false,
                    'all_modifiers' => [
                        '' => []
                    ]
                ]
            ],
            [
                'prefixed:var',
                [
                    'field_name' => 'var',
                    'params' => [],
                    'modifier' => '',
                    'full_modifier' => '',
                    'invalid_modifier' => false,
                    'all_modifiers' => [
                        '' => []
                    ]
                ],
                'prefixed:'
            ],
            [
                'hello param="hey"',
                [
                    'field_name' => 'hello',
                    'params' => [
                        'param' => 'hey'
                    ],
                    'modifier' => '',
                    'full_modifier' => '',
                    'invalid_modifier' => false,
                    'all_modifiers' => [
                        '' => [
                            'param' => 'hey'
                        ]
                    ]
                ]
            ],
            [
                'hello:some_mod param="hey"',
                [
                    'field_name' => 'hello',
                    'params' => [
                        'param' => 'hey'
                    ],
                    'modifier' => 'some_mod',
                    'full_modifier' => 'some_mod',
                    'invalid_modifier' => false,
                    'all_modifiers' => [
                        'some_mod' => [
                            'param' => 'hey'
                        ]
                    ]
                ]
            ],
            [
                'prefixed:var',
                [
                    'field_name' => 'var',
                    'params' => [],
                    'modifier' => '',
                    'full_modifier' => '',
                    'invalid_modifier' => false,
                    'all_modifiers' => [
                        '' => []
                    ]
                ],
                'prefixed:'
            ],
            [
                'prefixed:hello param="hey"',
                [
                    'field_name' => 'hello',
                    'params' => [
                        'param' => 'hey'
                    ],
                    'modifier' => '',
                    'full_modifier' => '',
                    'invalid_modifier' => false,
                    'all_modifiers' => [
                        '' => [
                            'param' => 'hey'
                        ]
                    ]
                ],
                'prefixed:'
            ],
            [
                'prefixed:hello:some_mod param="hey"',
                [
                    'field_name' => 'hello',
                    'params' => [
                        'param' => 'hey'
                    ],
                    'modifier' => 'some_mod',
                    'full_modifier' => 'some_mod',
                    'invalid_modifier' => false,
                    'all_modifiers' => [
                        'some_mod' => [
                            'param' => 'hey'
                        ]
                    ]
                ],
                'prefixed:'
            ],
            [
                'prefixed:hello:multiple:modifiers param="hey"',
                [
                    'field_name' => 'hello',
                    'params' => [
                        'param' => 'hey'
                    ],
                    'modifier' => 'modifiers',
                    'full_modifier' => 'multiple:modifiers',
                    'invalid_modifier' => false,
                    'all_modifiers' => [
                        'multiple' => [
                            'param' => 'hey'
                        ],
                        'modifiers' => [
                            'param' => 'hey'
                        ]
                    ]
                ],
                'prefixed:'
            ],
            [
                "variable:modifier param1='foo' param2='bar'",
                [
                    'field_name' => 'variable',
                    'params' => [
                        'param1' => 'foo',
                        'param2' => 'bar',
                    ],
                    'modifier' => 'modifier',
                    'full_modifier' => 'modifier',
                    'invalid_modifier' => false,
                    'all_modifiers' => [
                        'modifier' => [
                            'param1' => 'foo',
                            'param2' => 'bar',
                        ]
                    ]
                ]
            ],
            [
                "variable:modifier:hello param1='foo' param2='bar'",
                [
                    'field_name' => 'variable',
                    'params' => [
                        'param1' => 'foo',
                        'param2' => 'bar',
                    ],
                    'modifier' => 'hello',
                    'full_modifier' => 'modifier:hello',
                    'invalid_modifier' => false,
                    'all_modifiers' => [
                        'modifier' => [
                            'param1' => 'foo',
                            'param2' => 'bar',
                        ],
                        'hello' => [
                            'param1' => 'foo',
                            'param2' => 'bar',
                        ]
                    ]
                ]
            ],
            [
                "who:is:john:lakeman param1='foo' param2='bar'",
                [
                    'field_name' => 'who',
                    'params' => [
                        'param1' => 'foo',
                        'param2' => 'bar',
                    ],
                    'modifier' => 'lakeman',
                    'full_modifier' => 'is:john:lakeman',
                    'invalid_modifier' => false,
                    'all_modifiers' => [
                        'is' => [
                            'param1' => 'foo',
                            'param2' => 'bar',
                        ],
                        'john' => [
                            'param1' => 'foo',
                            'param2' => 'bar',
                        ],
                        'lakeman' => [
                            'param1' => 'foo',
                            'param2' => 'bar',
                        ]
                    ]
                ]
            ],
            [
                "who:is:john:lakeman param1='foo' is:param2='bar' lakeman:param3='baz'",
                [
                    'field_name' => 'who',
                    'params' => [
                        'param1' => 'foo',
                        'is:param2' => 'bar',
                        'lakeman:param3' => 'baz',
                        'param3' => 'baz',
                    ],
                    'modifier' => 'lakeman',
                    'full_modifier' => 'is:john:lakeman',
                    'invalid_modifier' => false,
                    'all_modifiers' => [
                        'is' => [
                            'param1' => 'foo',
                            'is:param2' => 'bar',
                            'lakeman:param3' => 'baz',
                            'param2' => 'bar',
                        ],
                        'john' => [
                            'param1' => 'foo',
                            'is:param2' => 'bar',
                            'lakeman:param3' => 'baz',
                        ],
                        'lakeman' => [
                            'param1' => 'foo',
                            'is:param2' => 'bar',
                            'lakeman:param3' => 'baz',
                            'param3' => 'baz',
                        ]
                    ]
                ]
            ],
            [
                "who:is:john:lakeman param1='foo' is:param2='bar' param3='bad' lakeman:param3='baz'",
                [
                    'field_name' => 'who',
                    'params' => [
                        'param1' => 'foo',
                        'is:param2' => 'bar',
                        'lakeman:param3' => 'baz',
                        'param3' => 'baz',
                    ],
                    'modifier' => 'lakeman',
                    'full_modifier' => 'is:john:lakeman',
                    'invalid_modifier' => false,
                    'all_modifiers' => [
                        'is' => [
                            'param1' => 'foo',
                            'is:param2' => 'bar',
                            'lakeman:param3' => 'baz',
                            'param2' => 'bar',
                            'param3' => 'bad',
                        ],
                        'john' => [
                            'param1' => 'foo',
                            'is:param2' => 'bar',
                            'lakeman:param3' => 'baz',
                            'param3' => 'bad',
                        ],
                        'lakeman' => [
                            'param1' => 'foo',
                            'is:param2' => 'bar',
                            'lakeman:param3' => 'baz',
                            'param3' => 'baz',
                        ]
                    ]
                ]
            ]
        ];

        return $tags;
    }

    public function testExtractVariablesReturnsEmptyForEmptyTagdata()
    {
        $result = $this->parser->extractVariables('');
        $this->assertSame(['var_single' => [], 'var_pair' => []], $result);
    }

    public function testExtractVariablesReturnsEmptyWhenNoDelimiters()
    {
        $result = $this->parser->extractVariables('plain text');
        $this->assertSame(['var_single' => [], 'var_pair' => []], $result);
    }

    public function testExtractVariablesExtractsSinglesPairsAndSkipsComments()
    {
        ee()->setMock('Variables/Parser', new class {
            public function parseTagParameters($tag)
            {
                return ['parsed' => $tag];
            }
        });

        $tagdata = "{foo param='bar'}content{/foo}{baz}{!-- comment --}";
        $result = $this->parser->extractVariables($tagdata);

        $this->assertSame(['baz' => 'baz'], $result['var_single']);
        $this->assertSame(
            ["foo param='bar'" => ['parsed' => "foo param='bar'"]],
            $result['var_pair']
        );
    }

    public function testExtractVariablesHonorsTarget()
    {
        $tagdata = '{foo}{bar}{foo:modifier}';
        $result = $this->parser->extractVariables($tagdata, 'bar');

        $this->assertSame(['bar' => 'bar'], $result['var_single']);
        $this->assertSame([], $result['var_pair']);
    }

    public function testExtractVariablesExtractsDateFormats()
    {
        $tagdata = '{date format="%Y-%m"}';
        $result = $this->parser->extractVariables($tagdata);

        $this->assertSame(['date format="%Y-%m"' => '%Y-%m'], $result['var_single']);
        $this->assertSame([], $result['var_pair']);
    }

    public function testExtractVariablesExtractsVariableFromConditionalsWithNestedTags()
    {
        $tagdata = "{if {segment_1} == 'news'}{segment_1}{/if}";
        $result = $this->parser->extractVariables($tagdata);

        $this->assertSame(['segment_1' => 'segment_1'], $result['var_single']);
        $this->assertSame([], $result['var_pair']);
    }

    public function testExtractVariablesIgnoresNumericAndIfTokensWithoutNestedVariables()
    {
        $tagdata = '{123}{if foo == "bar"}{/if}';
        $result = $this->parser->extractVariables($tagdata);

        $this->assertSame([], $result['var_single']);
        $this->assertSame([], $result['var_pair']);
    }

    public function testExtractVariablesDeduplicatesSingleAndPairTags()
    {
        $variablesParser = new class {
            public $calls = [];

            public function parseTagParameters($tag)
            {
                $this->calls[] = $tag;

                return ['parsed' => $tag];
            }
        };
        ee()->setMock('Variables/Parser', $variablesParser);

        $tagdata = '{foo}{foo}{bar}one{/bar}{bar}two{/bar}';
        $result = $this->parser->extractVariables($tagdata);

        $this->assertSame(['foo' => 'foo'], $result['var_single']);
        $this->assertSame(['bar' => ['parsed' => 'bar']], $result['var_pair']);
        $this->assertSame(['bar'], $variablesParser->calls);
    }

    public function testExtractVariablesReturnsEmptyForMissingTarget()
    {
        $tagdata = '{foo}{bar}{/bar}';
        $result = $this->parser->extractVariables($tagdata, 'missing');

        $this->assertSame([], $result['var_single']);
        $this->assertSame([], $result['var_pair']);
    }

    public function testExtractVariablesHandlesNestedDelimitersInVariableToken()
    {
        $tagdata = '{outer{inner}}';
        $result = $this->parser->extractVariables($tagdata);

        $this->assertSame(['inner' => 'inner'], $result['var_single']);
        $this->assertSame([], $result['var_pair']);
    }

    public function testExtractVariablesHonorsTargetForModifiedTags()
    {
        $tagdata = '{bar}{bar:modifier}{foo}';
        $result = $this->parser->extractVariables($tagdata, 'bar');

        $this->assertSame(
            [
                'bar' => 'bar',
                'bar:modifier' => 'bar:modifier',
            ],
            $result['var_single']
        );
        $this->assertSame([], $result['var_pair']);
    }

    public function testExtractVariablesMatchesPairsWithEqualsInOpeningTag()
    {
        ee()->setMock('Variables/Parser', new class {
            public function parseTagParameters($tag)
            {
                return ['parsed' => $tag];
            }
        });

        $tagdata = '{foo=bar}content{/foo}';
        $result = $this->parser->extractVariables($tagdata);

        $this->assertSame([], $result['var_single']);
        $this->assertSame(['foo=bar' => ['parsed' => 'foo=bar']], $result['var_pair']);
    }

    public function testExtractVariablesOnlyRunsSimpleConditionParserForSupportedTokens()
    {
        $parser = new class extends LegacyParser {
            public $simpleConditionCalls = [];

            public function fetch_simple_conditions($val)
            {
                $this->simpleConditionCalls[] = $val;

                return 'COND:' . $val;
            }
        };

        $tagdata = "{switch \\| yes}{multi_field \\| yes}{foo \\| bar}";
        $result = $parser->extractVariables($tagdata);

        $this->assertSame(
            [
                'switch \| yes' => 'switch \| yes',
                'multi_field \| yes' => 'multi_field \| yes',
                'foo \| bar' => 'COND:foo \| bar',
            ],
            $result['var_single']
        );
        $this->assertSame([], $result['var_pair']);
        $this->assertSame(['foo \| bar'], $parser->simpleConditionCalls);
    }

    public function testExtractVariablesHandlesNestedSameNamedPairs()
    {
        ee()->setMock('Variables/Parser', new class {
            public function parseTagParameters($tag)
            {
                return ['parsed' => $tag];
            }
        });

        $tagdata = '{foo}outer {foo}inner{/foo} tail{/foo}';
        $result = $this->parser->extractVariables($tagdata);

        $this->assertSame(['foo' => 'foo'], $result['var_single']);
        $this->assertSame(['foo' => ['parsed' => 'foo']], $result['var_pair']);
    }

    public function testExtractVariablesIgnoresMalformedNestedDelimiterToken()
    {
        $result = $this->parser->extractVariables('{{foo}}');

        $this->assertSame([], $result['var_single']);
        $this->assertSame([], $result['var_pair']);
    }

    public function testExtractDateFormatReturnsNullForEmptyInput()
    {
        $this->assertNull($this->parser->extractDateFormat(''));
    }

    public function testExtractDateFormatReturnsNullForNullInput()
    {
        $this->assertNull($this->parser->extractDateFormat(null));
    }

    /**
     * @dataProvider extractDateFormatProvider
     */
    public function testExtractDateFormatExtractsFormatToken($dateString, $expected)
    {
        $this->assertSame($expected, $this->parser->extractDateFormat($dateString));
    }

    public function extractDateFormatProvider()
    {
        return [
            'no format parameter' => ['date', false],
            'unquoted format parameter does not match' => ['date format=%Y-%m-%d', false],
            'double-quoted format' => ['date format="%Y-%m-%d"', '%Y-%m-%d'],
            'single-quoted format' => ["date format='%H:%i'", '%H:%i'],
            'escaped quote delimiters' => ['date format=\\"%Y/%m\\"', '%Y/%m'],
            'escaped single-quote delimiters' => ["date format=\\'%Y/%m\\'", '%Y/%m'],
            'whitespace around equals' => ['date format = "%M %d, %Y"', '%M %d, %Y'],
            'format token may span newlines' => ["date format=\"%Y\n%m\"", "%Y\n%m"],
            'empty format token returns empty string' => ['date format=""', ''],
            'first format token is returned when repeated' => ['date format="%Y" other="x" format="%m"', '%Y'],
            'double-escaped quote delimiters do not match' => ['date format=\\\\\"%Y/%m\\\\\"', false],
        ];
    }

    public function testExtractDateFormatReturnsFalseWhenQuoteIsUnclosed()
    {
        $this->assertFalse($this->parser->extractDateFormat("date format='%Y"));
    }

    public function testExtractDateFormatReturnsFalseWhenEscapingIsAsymmetric()
    {
        $this->assertFalse($this->parser->extractDateFormat('date format=\\"%Y/%m"'));
    }

    /**
     * @dataProvider parseTagParametersProvider
     */
    public function testParseTagParametersHandlesQuotesCommentsAndDefaults($paramString, array $defaults, array $expected)
    {
        $this->assertSame($expected, $this->parser->parseTagParameters($paramString, $defaults));
    }

    public function testParseTagParametersParsesAttributesSeparatedByNewlinesAndTabs()
    {
        $result = $this->parser->parseTagParameters("foo=\"bar\"\n\tbaz='qux'");

        $this->assertSame(['foo' => 'bar', 'baz' => 'qux'], $result);
    }

    public function testParseTagParametersReturnsEmptyArrayForCommentOnlyInputWithoutDefaults()
    {
        $result = $this->parser->parseTagParameters('{!-- only comment --}');

        $this->assertSame([], $result);
    }

    public function parseTagParametersProvider()
    {
        return [
            'empty string returns defaults' => [
                '',
                ['limit' => '5'],
                ['limit' => '5'],
            ],
            'null param string returns defaults' => [
                null,
                ['limit' => '5'],
                ['limit' => '5'],
            ],
            'no matches returns defaults' => [
                'foo=bar',
                ['limit' => '5'],
                ['limit' => '5'],
            ],
            'comment-only input returns defaults' => [
                '{!-- only comment --}',
                ['limit' => '5'],
                ['limit' => '5'],
            ],
            'quoted parameters with trimming' => [
                'foo=" bar " baz=\'qux\'',
                [],
                ['foo' => 'bar', 'baz' => 'qux'],
            ],
            'preserves whitespace-only values' => [
                'foo="   "',
                [],
                ['foo' => '   '],
            ],
            'repeated parameters keep the last value' => [
                'foo="first" foo="second"',
                [],
                ['foo' => 'second'],
            ],
            'supports parameter names with colons' => [
                'embed:limit="10"',
                [],
                ['embed:limit' => '10'],
            ],
            'removes template comments before parsing' => [
                'foo="bar" {!-- ignore --} baz="qux"',
                [],
                ['foo' => 'bar', 'baz' => 'qux'],
            ],
            'removes multiline template comments before parsing' => [
                "foo=\"bar\" {!-- ignore\nspanning lines --} baz=\"qux\"",
                [],
                ['foo' => 'bar', 'baz' => 'qux'],
            ],
            'applies missing defaults alongside parsed params' => [
                'foo="bar"',
                ['foo' => 'fallback', 'limit' => '5'],
                ['foo' => 'bar', 'limit' => '5'],
            ],
            'respects numeric defaults when value is not numeric' => [
                'limit="five" offset="10"',
                ['limit' => 3, 'offset' => 7],
                ['limit' => 3, 'offset' => '10'],
            ],
            'keeps numeric values when numeric defaults provided' => [
                'limit="15"',
                ['limit' => 3],
                ['limit' => '15'],
            ],
            'keeps numeric zero when numeric defaults provided' => [
                'limit="0"',
                ['limit' => 3],
                ['limit' => '0'],
            ],
            'uses numeric default when parsed numeric field is empty' => [
                'limit=""',
                ['limit' => 3],
                ['limit' => 3],
            ],
            'uses numeric default when parsed numeric field is whitespace-only' => [
                'limit="   "',
                ['limit' => 3],
                ['limit' => 3],
            ],
            'trimmed numeric string keeps parsed value over numeric default' => [
                'limit=" 12 "',
                ['limit' => 3],
                ['limit' => '12'],
            ],
            'keeps empty non-numeric value when default is non-numeric' => [
                'title=""',
                ['title' => 'fallback'],
                ['title' => ''],
            ],
            'backslash-escaped quotes are handled' => [
                'title=\\"Hello\\"',
                [],
                ['title' => 'Hello'],
            ],
            'backslash-escaped single quotes are handled' => [
                "title=\\'Hello\\'",
                [],
                ['title' => 'Hello'],
            ],
            'missing closing quote falls back to defaults' => [
                'title="Hello',
                ['title' => 'fallback'],
                ['title' => 'fallback'],
            ],
        ];
    }

    public function testGetFullTagReturnsPartialTagWhenNoMatch()
    {
        $result = $this->parser->getFullTag('plain text', '{tag}');
        $this->assertSame('{tag}', $result);
    }

    public function testGetFullTagExpandsNestedTags()
    {
        $tagdata = '{tag}outer {tag}inner{/tag} tail{/tag}';
        $result = $this->parser->getFullTag($tagdata, '{tag}');

        $this->assertSame($tagdata, $result);
    }

    public function testGetFullTagHonorsCustomDelimiters()
    {
        $tagdata = '[quote]Outer [quote]Inner[/quote] tail[/quote]';
        $result = $this->parser->getFullTag($tagdata, '[quote]', '[', ']');

        $this->assertSame($tagdata, $result);
    }

    public function testGetFullTagReturnsExpandedPartialWhenNestedTagNeverCloses()
    {
        $tagdata = '{tag}outer {tag}inner';
        $result = $this->parser->getFullTag($tagdata, '{tag}');

        $this->assertSame('{tag}outer {tag}', $result);
    }

    public function testGetFullTagReturnsMatchWithoutRecursionWhenNoNestedOpeningExists()
    {
        $tagdata = '{tag}value}';
        $result = $this->parser->getFullTag($tagdata, '{tag}');

        $this->assertSame('{tag}value}', $result);
    }

    public function testGetFullTagExpandsMultipleLevelsOfNestedTags()
    {
        $tagdata = '{tag}one {tag}two {tag}three{/tag} four{/tag} five{/tag}';
        $result = $this->parser->getFullTag($tagdata, '{tag}');

        $this->assertSame($tagdata, $result);
    }

    public function testGetFullTagEscapesRegexMetaCharactersInPartialTag()
    {
        $partialTag = "{exp:channel:entries channel='news+events' search:entry_id='not 10|20'}";
        $tagdata = $partialTag . 'payload{/exp:channel:entries}';
        $result = $this->parser->getFullTag($tagdata, $partialTag);

        $this->assertSame($tagdata, $result);
    }

    public function testParseModifiedVariablesAppliesMultipleModifiersAndPrepsConditionals()
    {
        $parser = new class extends LegacyParser {
            public function replace_custom($data, $params = array(), $tagdata = false)
            {
                return $data . '-custom';
            }
        };

        $functions = new class {
            public $calls = [];

            public function prep_conditionals($str, $conditionals)
            {
                $this->calls[] = [$str, $conditionals];
                return 'PREP:' . $str;
            }
        };

        ee()->setMock('functions', $functions);
        $template = 'Value: {foo:rot13:custom}';
        $result = $parser->parseModifiedVariables($template, ['foo' => 'bar']);

        $this->assertSame('PREP:Value: one-custom', $result);
        $this->assertCount(1, $functions->calls);
        $this->assertSame(
            ['Value: one-custom', ['foo:rot13:custom' => 'one-custom']],
            $functions->calls[0]
        );
    }

    public function testParseModifiedVariablesSkipsWhenFirstModifierInvalid()
    {
        $functions = new class {
            public $calls = [];

            public function prep_conditionals($str, $conditionals)
            {
                $this->calls[] = [$str, $conditionals];
                return 'PREP:' . $str;
            }
        };

        ee()->setMock('functions', $functions);
        ee()->setMock('Variables/Modifiers', new class {
            public function has($name)
            {
                return false;
            }
        });

        $template = 'Value: {foo:unknown:rot13}';
        $result = $this->parser->parseModifiedVariables($template, ['foo' => 'bar']);

        $this->assertSame($template, $result);
        $this->assertSame([], $functions->calls);
    }

    public function testParseModifiedVariablesSkipsInvalidLaterModifierButKeepsPrior()
    {
        $functions = new class {
            public $calls = [];

            public function prep_conditionals($str, $conditionals)
            {
                $this->calls[] = [$str, $conditionals];
                return 'PREP:' . $str;
            }
        };

        ee()->setMock('functions', $functions);
        ee()->setMock('Variables/Modifiers', new class {
            public function has($name)
            {
                return false;
            }
        });

        $template = 'Value: {foo:rot13:unknown}';
        $result = $this->parser->parseModifiedVariables($template, ['foo' => 'bar']);

        $this->assertSame('PREP:Value: one', $result);
        $this->assertCount(1, $functions->calls);
        $this->assertSame(
            ['Value: one', ['foo:rot13:unknown' => 'one']],
            $functions->calls[0]
        );
    }

    public function testParseModifiedVariablesReturnsOriginalWhenNoModifiedTagsPresent()
    {
        $functions = new class {
            public $calls = [];

            public function prep_conditionals($str, $conditionals)
            {
                $this->calls[] = [$str, $conditionals];
                return 'PREP:' . $str;
            }
        };

        ee()->setMock('functions', $functions);

        $template = 'Value: {foo}';
        $result = $this->parser->parseModifiedVariables($template, ['foo' => 'bar']);

        $this->assertSame($template, $result);
        $this->assertSame([], $functions->calls);
    }

    public function testParseModifiedVariablesSupportsPrefixedVariableName()
    {
        $functions = new class {
            public $calls = [];

            public function prep_conditionals($str, $conditionals)
            {
                $this->calls[] = [$str, $conditionals];
                return 'PREP:' . $str;
            }
        };

        ee()->setMock('functions', $functions);

        $template = 'Value: {embed:foo:rot13}';
        $result = $this->parser->parseModifiedVariables($template, ['embed:foo' => 'bar']);

        $this->assertSame('PREP:Value: one', $result);
        $this->assertCount(1, $functions->calls);
        $this->assertSame(
            ['Value: one', ['embed:foo:rot13' => 'one']],
            $functions->calls[0]
        );
    }

    public function testParseModifiedVariablesUsesFallbackWhenAllModifiersMissing()
    {
        $parser = new class extends LegacyParser {
            public function parseVariableProperties($template_var, $prefix = '')
            {
                return [
                    'field_name' => 'foo',
                    'params' => [],
                    'modifier' => 'rot13',
                ];
            }
        };

        $functions = new class {
            public $calls = [];

            public function prep_conditionals($str, $conditionals)
            {
                $this->calls[] = [$str, $conditionals];
                return 'PREP:' . $str;
            }
        };

        ee()->setMock('functions', $functions);

        $template = 'Value: {foo:rot13}';
        $result = $parser->parseModifiedVariables($template, ['foo' => 'bar']);

        $this->assertSame('PREP:Value: one', $result);
        $this->assertCount(1, $functions->calls);
        $this->assertSame(
            ['Value: one', ['foo:rot13' => 'one']],
            $functions->calls[0]
        );
    }

    public function testParseModifiedVariablesFallbackSkipsUnknownModifier()
    {
        $parser = new class extends LegacyParser {
            public function parseVariableProperties($template_var, $prefix = '')
            {
                return [
                    'field_name' => 'foo',
                    'params' => [],
                    'modifier' => 'unknown',
                ];
            }
        };

        $functions = new class {
            public $calls = [];

            public function prep_conditionals($str, $conditionals)
            {
                $this->calls[] = [$str, $conditionals];
                return 'PREP:' . $str;
            }
        };

        ee()->setMock('functions', $functions);
        ee()->setMock('Variables/Modifiers', new class {
            public function has($name)
            {
                return false;
            }
        });

        $template = 'Value: {foo:unknown}';
        $result = $parser->parseModifiedVariables($template, ['foo' => 'bar']);

        $this->assertSame($template, $result);
        $this->assertSame([], $functions->calls);
    }

    public function testParseModifiedVariablesReplacesRepeatedModifiedVariables()
    {
        $functions = new class {
            public $calls = [];

            public function prep_conditionals($str, $conditionals)
            {
                $this->calls[] = [$str, $conditionals];
                return 'PREP:' . $str;
            }
        };

        ee()->setMock('functions', $functions);

        $template = 'A {foo:rot13} B {foo:rot13}';
        $result = $this->parser->parseModifiedVariables($template, ['foo' => 'bar']);

        $this->assertSame('PREP:A one B one', $result);
        $this->assertCount(1, $functions->calls);
        $this->assertSame(
            ['A one B one', ['foo:rot13' => 'one']],
            $functions->calls[0]
        );
    }

    public function testParseModifiedVariablesHandlesCustomModifierMethodInFallbackBranch()
    {
        $parser = new class extends LegacyParser {
            public function parseVariableProperties($template_var, $prefix = '')
            {
                return [
                    'field_name' => 'foo',
                    'params' => ['mode' => 'x'],
                    'modifier' => 'custom',
                    'all_modifiers' => [],
                ];
            }

            public function replace_custom($data, $params = array(), $tagdata = false)
            {
                return $data . '-custom';
            }
        };

        $functions = new class {
            public $calls = [];

            public function prep_conditionals($str, $conditionals)
            {
                $this->calls[] = [$str, $conditionals];
                return 'PREP:' . $str;
            }
        };

        ee()->setMock('functions', $functions);
        $template = 'Value: {foo:custom}';
        $result = $parser->parseModifiedVariables($template, ['foo' => 'bar']);

        $this->assertSame('PREP:Value: bar-custom', $result);
        $this->assertCount(1, $functions->calls);
        $this->assertSame(
            ['Value: bar-custom', ['foo:custom' => 'bar-custom']],
            $functions->calls[0]
        );
    }

    public function testParseModifiedVariablesContinuesAfterFirstInvalidModifierToken()
    {
        $functions = new class {
            public $calls = [];

            public function prep_conditionals($str, $conditionals)
            {
                $this->calls[] = [$str, $conditionals];
                return 'PREP:' . $str;
            }
        };

        ee()->setMock('functions', $functions);
        ee()->setMock('Variables/Modifiers', new class {
            public function has($name)
            {
                return false;
            }
        });

        $template = 'A {foo:unknown:rot13} B {foo:rot13}';
        $result = $this->parser->parseModifiedVariables($template, ['foo' => 'bar']);

        $this->assertSame('PREP:A {foo:unknown:rot13} B one', $result);
        $this->assertCount(1, $functions->calls);
        $this->assertSame(
            ['A {foo:unknown:rot13} B one', ['foo:rot13' => 'one']],
            $functions->calls[0]
        );
    }

    public function testParseModifiedVariablesSkipsMissingVarAndStillParsesMatchingVar()
    {
        $functions = new class {
            public $calls = [];

            public function prep_conditionals($str, $conditionals)
            {
                $this->calls[] = [$str, $conditionals];
                return 'PREP:' . $str;
            }
        };

        ee()->setMock('functions', $functions);

        $template = 'Value: {foo:rot13}';
        $result = $this->parser->parseModifiedVariables($template, [
            'missing' => 'skip',
            'foo' => 'bar',
        ]);

        $this->assertSame('PREP:Value: one', $result);
        $this->assertCount(1, $functions->calls);
        $this->assertSame(
            ['Value: one', ['foo:rot13' => 'one']],
            $functions->calls[0]
        );
    }

    /**
     * @dataProvider parseOrParameterProvider
     */
    public function testParseOrParameterParsesOptionsAndNegation($input, $expected)
    {
        $this->assertSame($expected, $this->parser->parseOrParameter($input));
    }

    public function testParseOrParameterTrimsOptionsAfterSplit()
    {
        $result = $this->parser->parseOrParameter(' one | two | three ');

        $this->assertSame(['options' => ['one', 'two', 'three'], 'not' => false], $result);
    }

    public function parseOrParameterProvider()
    {
        return [
            'empty input' => [
                '',
                ['options' => [], 'not' => false],
            ],
            'whitespace input' => [
                '   ',
                ['options' => [], 'not' => false],
            ],
            'single option' => [
                'foo',
                ['options' => ['foo'], 'not' => false],
            ],
            'single option is trimmed before parsing' => [
                '   foo bar   ',
                ['options' => ['foo bar'], 'not' => false],
            ],
            'multi options with spacing' => [
                ' foo | bar |  baz ',
                ['options' => ['foo', 'bar', 'baz'], 'not' => false],
            ],
            'delimiters with surrounding whitespace discard whitespace-only segments' => [
                ' | foo | ',
                ['options' => ['foo'], 'not' => false],
            ],
            'multi options with empty segments' => [
                'foo||bar|',
                ['options' => ['foo', 'bar'], 'not' => false],
            ],
            'pipe-only delimiters return empty options' => [
                '|||',
                ['options' => [], 'not' => false],
            ],
            'whitespace-only segment is preserved after trim mapping' => [
                'foo|   |bar',
                ['options' => ['foo', '', 'bar'], 'not' => false],
            ],
            'negated single option' => [
                'not foo',
                ['options' => ['foo'], 'not' => true],
            ],
            'negated options' => [
                'not foo|bar',
                ['options' => ['foo', 'bar'], 'not' => true],
            ],
            'negated pipe-only delimiters return empty options' => [
                'not |||',
                ['options' => [], 'not' => true],
            ],
            'case-insensitive negation keyword' => [
                'NoT foo|bar',
                ['options' => ['foo', 'bar'], 'not' => true],
            ],
            'leading whitespace before negation is ignored' => [
                '   not foo|bar  ',
                ['options' => ['foo', 'bar'], 'not' => true],
            ],
            'negation with extra spacing before options' => [
                'not   foo  |  bar ',
                ['options' => ['foo', 'bar'], 'not' => true],
            ],
            'negated options preserve empty post-trim segment' => [
                'not foo|   |bar',
                ['options' => ['foo', '', 'bar'], 'not' => true],
            ],
            'negated single option with internal spaces' => [
                'not foo bar',
                ['options' => ['foo bar'], 'not' => true],
            ],
            'tab after not is not treated as negation prefix' => [
                "not\tfoo|bar",
                ['options' => ["not\tfoo", 'bar'], 'not' => false],
            ],
            'newline after not is not treated as negation prefix' => [
                "not\nfoo|bar",
                ['options' => ["not\nfoo", 'bar'], 'not' => false],
            ],
            'tabs around options are trimmed' => [
                "foo|\tbar\t|baz",
                ['options' => ['foo', 'bar', 'baz'], 'not' => false],
            ],
            'not without trailing space is treated as a value' => [
                'not|foo',
                ['options' => ['not', 'foo'], 'not' => false],
            ],
            'numeric zero input is treated as empty options' => [
                '0',
                ['options' => [], 'not' => false],
            ],
            'negated numeric zero keeps negation with empty options' => [
                'not 0',
                ['options' => [], 'not' => true],
            ],
            'negation with no options' => [
                'not ',
                ['options' => ['not'], 'not' => false],
            ],
        ];
    }
}
