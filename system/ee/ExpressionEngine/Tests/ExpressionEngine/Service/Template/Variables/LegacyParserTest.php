<?php

namespace ExpressionEngine\Tests\Service\Template\Variables;

use ExpressionEngine\Service\Template\Variables\LegacyParser;
use PHPUnit\Framework\TestCase;

class LegacyParserTest extends TestCase
{
    public $parser;

    public function setUp(): void
    {
        $this->parser = new LegacyParser();
    }

    public function tearDown(): void
    {
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
}
