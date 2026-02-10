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

    public function testExtractDateFormatReturnsNullForEmptyInput()
    {
        $this->assertNull($this->parser->extractDateFormat(''));
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
            'double-quoted format' => ['date format="%Y-%m-%d"', '%Y-%m-%d'],
            'single-quoted format' => ["date format='%H:%i'", '%H:%i'],
            'escaped quote delimiters' => ['date format=\\"%Y/%m\\"', '%Y/%m'],
            'whitespace around equals' => ['date format = "%M %d, %Y"', '%M %d, %Y'],
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

    public function testParseModifiedVariablesAppliesMultipleModifiersAndPrepsConditionals()
    {
        if (! defined('REQ')) {
            define('REQ', 'PAGE');
        }

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
                return $name === 'custom';
            }

            public function all()
            {
                return [
                    'custom' => '\\ExpressionEngine\\Tests\\Service\\Template\\Variables\\FakeModifier',
                ];
            }
        });

        $template = 'Value: {foo:rot13:custom}';
        $result = $this->parser->parseModifiedVariables($template, ['foo' => 'bar']);

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

    /**
     * @dataProvider parseOrParameterProvider
     */
    public function testParseOrParameterParsesOptionsAndNegation($input, $expected)
    {
        $this->assertSame($expected, $this->parser->parseOrParameter($input));
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
            'multi options with spacing' => [
                ' foo | bar |  baz ',
                ['options' => ['foo', 'bar', 'baz'], 'not' => false],
            ],
            'multi options with empty segments' => [
                'foo||bar|',
                ['options' => ['foo', 'bar'], 'not' => false],
            ],
            'negated single option' => [
                'not foo',
                ['options' => ['foo'], 'not' => true],
            ],
            'negated options' => [
                'not foo|bar',
                ['options' => ['foo', 'bar'], 'not' => true],
            ],
            'negation with no options' => [
                'not ',
                ['options' => ['not'], 'not' => false],
            ],
        ];
    }
}

class FakeModifier
{
    public function modify($data, $params, $tagdata = false)
    {
        return $data . '-custom';
    }
}
