<?php

namespace ExpressionEngine\Tests\Library\Parser\Conditional;

use ExpressionEngine\Library\Parser\Conditional\Exception\LexerException;
use ExpressionEngine\Library\Parser\Conditional\Lexer;
use ExpressionEngine\Library\Parser\Conditional\Runner;
use ExpressionEngine\Library\Parser\Conditional\Token\Comment;
use ExpressionEngine\Library\Template\Annotation\Runtime as RuntimeAnnotations;
use PHPUnit\Framework\TestCase;

class LexerBoundaryTest extends TestCase
{
    /**
     * Keep complete variable names when a regex can backtrack to a short prefix.
     *
     * @param string $name The complete variable name.
     * @return void
     * @dataProvider longVariableProvider
     */
    public function testLongVariablesRemainSingleTokens($name)
    {
        $expected = $this->conditionalTokens([['VARIABLE', $name]]);

        $this->assertSame($expected, $this->tokens('{if ' . $name . '}out{/if}'));
    }

    /**
     * Cover boolean, operator, and ordinary prefixes around the former window limit.
     *
     * @return array
     */
    public function longVariableProvider()
    {
        $cases = [];
        $prefixes = [
            ['true', '-'], ['false', '-'], ['AND', '-'], ['OR', '-'], ['XOR', '-'],
            ['prefix', '-'], ['prefix', ':']
        ];

        foreach ($prefixes as [$prefix, $separator]) {
            foreach ([64, 447, 511, 512, 600, 1024] as $length) {
                $cases[$prefix . $separator . $length] = [$prefix . str_repeat($separator, $length) . 'suffix'];
            }
        }

        return $cases;
    }

    /**
     * Preserve complete tokens across delimiters, long values, and Unicode text.
     *
     * @param string $template The template to tokenize.
     * @param array $expected The expected token types and lexemes.
     * @return void
     * @dataProvider boundaryProvider
     */
    public function testTokenBoundaries($template, array $expected)
    {
        $this->assertSame($expected, $this->tokens($template));
    }

    /**
     * Supply delimiters that cannot be treated as the end of every token type.
     *
     * @return array
     */
    public function boundaryProvider()
    {
        $comment = '{!-- } ' . str_repeat('comment é } ', 100) . '--}';
        $word = str_repeat('variable_', 150);
        $space = str_repeat(' ', 1200);
        $string = 'left' . str_repeat('}é{', 200) . '"right';
        $quoted = '"' . str_replace('"', '\\"', $string) . '"';
        $tag = '{exp:foo:bar param="a}b{c"}';
        $padding = str_repeat(' ', 502) . 'é🙂';

        return [
            'comment containing earlier braces' => [$comment, [['COMMENT', $comment], ['EOS', true]]],
            'comment within expression' => [
                '{if true ' . $comment . ' AND false}out{/if}',
                $this->conditionalTokens([
                    ['BOOL', 'true'], ['WHITESPACE', ' '], ['COMMENT', $comment], ['WHITESPACE', ' '],
                    ['OPERATOR', 'AND'], ['WHITESPACE', ' '], ['BOOL', 'false']
                ])
            ],
            'long alphabetic variable' => [
                '{if ' . $word . '}out{/if}', $this->conditionalTokens([['VARIABLE', $word]])
            ],
            'long whitespace' => [
                '{if ' . $space . 'true}out{/if}',
                $this->conditionalTokens([['BOOL', 'true']], ' ' . $space)
            ],
            'quoted braces and escaped quote' => [
                '{if ' . $quoted . '}out{/if}', $this->conditionalTokens([['STRING', $string]])
            ],
            'nested tag with quoted braces' => [
                '{if ' . $tag . '}out{/if}', $this->conditionalTokens([['TAG', $tag]])
            ],
            'consecutive comments before a value' => [
                '{if ' . $comment . '{!-- } --}true}out{/if}',
                $this->conditionalTokens([['COMMENT', $comment], ['COMMENT', '{!-- } --}'], ['BOOL', 'true']])
            ],
            'comment stops at its first complete terminator' => [
                '{!-- } --}{if true}out{/if}{!-- tail } --}',
                array_merge(
                    [['COMMENT', '{!-- } --}']],
                    array_slice($this->conditionalTokens([['BOOL', 'true']]), 0, -1),
                    [['COMMENT', '{!-- tail } --}'], ['EOS', true]]
                )
            ],
            'number after a quoted brace without whitespace' => [
                '{if "}"==.5}out{/if}',
                $this->conditionalTokens([['STRING', '}'], ['OPERATOR', '=='], ['NUMBER', '.5']])
            ],
            'comment after a quoted brace without whitespace' => [
                '{if "}"{!-- } --}=="}"}out{/if}',
                $this->conditionalTokens([
                    ['STRING', '}'], ['COMMENT', '{!-- } --}'], ['OPERATOR', '=='], ['STRING', '}']
                ])
            ],
            'unicode whitespace' => [
                "{if true\xc2\xa0AND\xe2\x80\x83false}out{/if}",
                $this->conditionalTokens([
                    ['BOOL', 'true'], ['WHITESPACE', "\xc2\xa0"], ['OPERATOR', 'AND'],
                    ['WHITESPACE', "\xe2\x80\x83"], ['BOOL', 'false']
                ])
            ],
            'multibyte text at old window edge' => [
                '{if true}' . $padding . '{/if}', $this->conditionalTokens([['BOOL', 'true']], ' ', $padding)
            ],
            'missing closing brace' => [
                '{if true', [['LD', '{'], ['IF', 'if'], ['WHITESPACE', ' '], ['BOOL', 'true'], ['EOS', true]]
            ],
            'unterminated comment' => [
                '{!-- a } b', [['TEMPLATE_STRING', '{'], ['TEMPLATE_STRING', '!-- a } b'], ['EOS', true]]
            ]
        ];
    }

    /**
     * Continue the expression after nested tags and quoted braces have been consumed.
     *
     * @param string $expression The expression inside the conditional.
     * @param array $expected The expected expression token types and lexemes.
     * @return void
     * @dataProvider nestedTagProvider
     */
    public function testNestedTagsPreserveFollowingExpression($expression, array $expected)
    {
        $this->assertSame($this->conditionalTokens($expected), $this->tokens('{if ' . $expression . '}out{/if}'));
    }

    /**
     * Cover tag nesting, quoting, comments, and multiple boundary crossings.
     *
     * @return array
     */
    public function nestedTagProvider()
    {
        $tag = '{exp:plugin:value}';
        $other = '{exp:other:value}';
        $quoted = '{exp:plugin:value param="a}b{c"}';
        $recursive = '{exp:plugin:value {exp:other:value}}';
        $comparison = [['WHITESPACE', ' '], ['OPERATOR', '=='], ['WHITESPACE', ' '], ['STRING', 'test']];

        return [
            'plugin tag followed by comparison' => [
                $tag . " == 'test'", array_merge([['TAG', $tag]], $comparison)
            ],
            'quoted braces in tag parameters' => [
                $quoted . " == 'test'", array_merge([['TAG', $quoted]], $comparison)
            ],
            'escaped quote before a parameter brace' => [
                '{exp:plugin:value param="a\\"}b"}' . " == 'test'",
                array_merge([['TAG', '{exp:plugin:value param="a"}b"}']], $comparison)
            ],
            'recursive nested tag' => [
                $recursive . " == 'test'", array_merge([['TAG', $recursive]], $comparison)
            ],
            'two tags without surrounding whitespace' => [
                $tag . '==' . $other, [['TAG', $tag], ['OPERATOR', '=='], ['TAG', $other]]
            ],
            'quoted brace before a nested tag' => [
                "'a}b' == " . $tag,
                [['STRING', 'a}b'], ['WHITESPACE', ' '], ['OPERATOR', '=='], ['WHITESPACE', ' '], ['TAG', $tag]]
            ],
            'comment immediately before a nested tag' => [
                '{!-- } }} --}' . $tag . " == 'test'",
                array_merge([['COMMENT', '{!-- } }} --}'], ['TAG', $tag]], $comparison)
            ],
            'several tags and strings cross boundaries' => [
                $quoted . " == 'te}st' AND " . $other . " != 'no'",
                [
                    ['TAG', $quoted], ['WHITESPACE', ' '], ['OPERATOR', '=='], ['WHITESPACE', ' '],
                    ['STRING', 'te}st'], ['WHITESPACE', ' '], ['OPERATOR', 'AND'], ['WHITESPACE', ' '],
                    ['TAG', $other], ['WHITESPACE', ' '], ['OPERATOR', '!='], ['WHITESPACE', ' '], ['STRING', 'no']
                ]
            ]
        ];
    }

    /**
     * Preserve an unresolved tag comparison until a later template pass supplies its value.
     *
     * @param string $tag The unresolved nested tag.
     * @return void
     * @dataProvider deferredTagProvider
     */
    public function testNestedTagComparisonsSurviveDeferredEvaluation($tag)
    {
        $template = '{if ' . $tag . " == 'test'}yes{if:else}no{/if}";
        $deferred = (new Runner())->processConditionals($template, []);

        $this->assertSame($template, preg_replace('/\{!--.*?--\}/s', '', $deferred));
        $this->assertSame(
            'yes',
            (new Runner())->processConditionals(str_replace($tag, "'test'", $deferred), [])
        );
        $this->assertSame(
            'no',
            (new Runner())->processConditionals(str_replace($tag, "'other'", $deferred), [])
        );
    }

    /**
     * Supply tags whose parameters or nested tags contain earlier closing braces.
     *
     * @return array
     */
    public function deferredTagProvider()
    {
        return [
            'plain plugin tag' => ['{exp:plugin:value}'],
            'quoted braces' => ['{exp:plugin:value param="a}b{c"}'],
            'unicode parameter' => ['{exp:plugin:value param="é}🙂"}'],
            'recursive tag' => ['{exp:plugin:value {exp:other:value}}']
        ];
    }

    /**
     * Keep evaluated output stable when comments and strings contain apparent delimiters.
     *
     * @param string $template The complete template.
     * @param array $variables The resolved template variables.
     * @param string $expected The expected rendered conditional output.
     * @return void
     * @dataProvider evaluationBoundaryProvider
     */
    public function testBoundaryCrossingsPreserveConditionalResults($template, array $variables, $expected)
    {
        $this->assertSame($expected, (new Runner())->processConditionals($template, $variables));
    }

    /**
     * Exercise real expression evaluation after delimiters inside token contents.
     *
     * @return array
     */
    public function evaluationBoundaryProvider()
    {
        $long = str_repeat('é}🙂', 180) . '"end';
        $quoted = '"' . str_replace('"', '\\"', $long) . '"';

        return [
            'variable after a quoted brace comparison' => [
                "{if 'a}b' == 'a}b' AND enabled}yes{if:else}no{/if}", ['enabled' => true], 'yes'
            ],
            'long unicode string with braces and an escaped quote' => [
                '{if ' . $quoted . ' == ' . $quoted . '}yes{if:else}no{/if}', [], 'yes'
            ],
            'comment contains apparent conditional tags' => [
                '{if true {!-- } {if false}{/if} --} AND true}yes{if:else}no{/if}', [], 'yes'
            ],
            'nested conditionals after a quoted brace' => [
                '{if outer}{if "a}b" == "a}b"}yes{if:else}bad{/if}{if:else}no{/if}',
                ['outer' => true], 'yes'
            ],
            'elseif expression contains a quoted brace' => [
                '{if "a}b" == "no"}bad{if:elseif "c}d" == "c}d"}yes{if:else}no{/if}',
                [], 'yes'
            ],
            'adjacent conditionals refresh their boundaries' => [
                '{if "a}b" == "a}b"}A{/if}{if "c}d" == "c}d"}B{/if}{if "é}🙂" == "é}🙂"}C{/if}',
                [], 'ABC'
            ]
        ];
    }

    /**
     * Retain annotation context and diagnostic lines across multiline tags and strings.
     *
     * @return void
     */
    public function testBoundaryCrossingsPreserveTokenLocations()
    {
        $annotations = new RuntimeAnnotations();
        $annotations->useSharedStore();
        $annotation = $annotations->create(['context' => 'nested-boundary-test']);
        $tag = "{exp:plugin:value param=\"a}\nb\"}";
        $template = $annotation . "\n{if " . $tag . " == 'te}\nst'}out{/if}";
        $actual = [];

        foreach ((new Lexer())->tokenize($template) as $token) {
            if (in_array($token->type, ['TAG', 'OPERATOR', 'STRING', 'RD'], true)) {
                $actual[] = [$token->type, $token->lexeme, $token->lineno, $token->context];
            }
        }

        $this->assertSame([
            ['TAG', $tag, 3, 'nested-boundary-test'],
            ['OPERATOR', '==', 3, 'nested-boundary-test'],
            ['STRING', "te}\nst", 4, 'nested-boundary-test'],
            ['RD', '}', 4, 'nested-boundary-test'],
            ['RD', '}', 4, 'nested-boundary-test']
        ], $actual);
    }

    /**
     * Preserve legacy matching when invalid UTF-8 occurs beyond a shorter subject.
     *
     * @return void
     */
    public function testInvalidUtf8AfterConditionalPreservesLegacyTokens()
    {
        $tail = str_repeat(' ', 1000) . "\xff";
        $expected = [
            ['TEMPLATE_STRING', '{'], ['TEMPLATE_STRING', 'if true}yes'],
            ['LD', '{'], ['ENDIF', '/if'], ['RD', '}'], ['TEMPLATE_STRING', $tail], ['EOS', true]
        ];

        $this->assertSame($expected, $this->tokens('{if true}yes{/if}' . $tail));
    }

    /**
     * Continue matching once an invalid prefix has been consumed as template text.
     *
     * @return void
     */
    public function testInvalidUtf8BeforeConditionalPreservesLegacyTokens()
    {
        $expected = array_merge([['TEMPLATE_STRING', "\xff"]], $this->conditionalTokens([['BOOL', 'true']]));

        $this->assertSame($expected, $this->tokens("\xff{if true}out{/if}"));
    }

    /**
     * Evaluate long variable names using the supplied value rather than a shortened prefix.
     *
     * @return void
     */
    public function testLongVariableNamesRetainConditionalResults()
    {
        $names = [
            'true' . str_repeat('-', 600) . 'suffix',
            'AND' . str_repeat('-', 600) . 'suffix',
            'prefix' . str_repeat(':', 600) . 'suffix'
        ];

        foreach ($names as $name) {
            $template = '{if ' . $name . '}yes{if:else}no{/if}';

            $this->assertSame('yes', (new Runner())->processConditionals($template, [$name => true]));
            $this->assertSame('no', (new Runner())->processConditionals($template, [$name => false]));
        }
    }

    /**
     * Retain the existing exception for an unterminated quoted value containing braces.
     *
     * @param string $template The template with an unterminated quoted value.
     * @return void
     * @dataProvider unclosedStringProvider
     */
    public function testUnclosedStringRetainsItsException($template)
    {
        $this->expectException(LexerException::class);
        $this->expectExceptionMessage('Unclosed string.');

        (new Lexer())->tokenize($template);
    }

    /**
     * Cover missing quotes inside expressions and nested tag parameters.
     *
     * @return array
     */
    public function unclosedStringProvider()
    {
        return [
            'double quoted expression' => ['{if name == "a}b'],
            'single quoted tag parameter' => ["{if {exp:plugin:value param='a}b}"],
            'escaped closing quote in tag parameter' => ['{if {exp:plugin:value param="a}b\\"}'],
            'expression ends with a backslash' => ['{if "a}b' . "\\"]
        ];
    }

    /**
     * Reset cursor and matching boundaries when the same lexer handles another input.
     *
     * @return void
     */
    public function testTokenizeResetsCursorAndRegexWindowBetweenInputs()
    {
        $lexer = new Lexer();
        $long_template = '{if ' . str_repeat('long_', 150) . '}out{/if}';
        $templates = [
            $long_template,
            '',
            $long_template,
            'Plain text without template tags: é🙂.',
            "\xff{if true}out{/if}",
            '{if false}out{/if}',
            '{!-- a } b --}{if true}out{/if}'
        ];

        foreach ($templates as $template) {
            $this->assertSame($this->tokens($template), $this->tokens($template, $lexer));
        }
    }

    /**
     * Extract public token data while excluding generated conditional annotations.
     *
     * @param string $template The template to tokenize.
     * @param Lexer|null $lexer An existing lexer to reuse.
     * @return array
     */
    private function tokens($template, $lexer = null)
    {
        $tokens = [];

        foreach (($lexer ?: new Lexer())->tokenize($template) as $token) {
            if ($token instanceof Comment && $token->conditional_annotation) {
                continue;
            }

            $tokens[] = [$token->type, $token->lexeme];
        }

        return $tokens;
    }

    /**
     * Build the expected surrounding tokens for one conditional expression.
     *
     * @param array $expression The expression tokens.
     * @param string $whitespace The whitespace after the if keyword.
     * @param string $body The conditional body.
     * @return array
     */
    private function conditionalTokens(array $expression, $whitespace = ' ', $body = 'out')
    {
        return array_merge(
            [['LD', '{'], ['IF', 'if'], ['WHITESPACE', $whitespace]],
            $expression,
            [['RD', '}'], ['TEMPLATE_STRING', $body], ['LD', '{'], ['ENDIF', '/if'], ['RD', '}'], ['EOS', true]]
        );
    }
}
