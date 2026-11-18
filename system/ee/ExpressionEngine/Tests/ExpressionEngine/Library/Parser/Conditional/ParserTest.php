<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Library\Parser\Conditional;

use ExpressionEngine\Library\Parser\Conditional\Lexer;
use ExpressionEngine\Library\Parser\Conditional\Parser;
use ExpressionEngine\Library\Parser\Conditional\Exception\ParserException;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testOutputLastAnnotationNoopsWhenNoneExists()
    {
        $parser = new Parser([]);
        $parser->output('prefix');

        $parser->outputLastAnnotation();

        $this->assertSame('prefix', $this->readOutput($parser));
    }

    public function testOutputLastAnnotationAppendsStoredAnnotation()
    {
        $parser = new Parser([]);
        $parser->output('prefix');

        $annotation = (object) ['lexeme' => '{!-- conditional --}'];
        $this->setLastConditionalAnnotation($parser, $annotation);

        $parser->outputLastAnnotation();

        $this->assertSame('prefix{!-- conditional --}', $this->readOutput($parser));
    }

    private function parseTemplate(string $template, array $vars = [], bool $safety = false): string
    {
        $lexer = new Lexer();
        $tokens = $lexer->tokenize($template);
        $parser = new Parser($tokens);
        $parser->setVariables($vars);

        if ($safety) {
            $parser->safetyOn();
        }

        return $parser->parse();
    }

    public function testParseReturnsTemplateWhenNoConditionals()
    {
        $template = 'Hello world.';

        $this->assertSame($template, $this->parseTemplate($template));
    }

    public function testParseSelectsElseBranchWhenConditionIsFalse()
    {
        $template = '{if foo == "bar"}Yes{if:else}No{/if}';

        $this->assertSame('No', $this->parseTemplate($template, ['foo' => 'baz']));
    }

    public function testParseSelectsIfBranchWhenConditionIsTrue()
    {
        $template = '{if foo == "bar"}Yes{if:else}No{/if}';

        $this->assertSame('Yes', $this->parseTemplate($template, ['foo' => 'bar']));
    }

    public function testParseSkipsNestedConditionalsWhenOuterIsFalse()
    {
        $template = '{if foo}{if bar}X{/if}{if:else}Y{/if}';

        $this->assertSame('Y', $this->parseTemplate($template, ['foo' => false, 'bar' => true]));
    }

    public function testParseSafetyOnTreatsNonScalarsAsFalse()
    {
        $template = '{if foo}YES{if:else}NO{/if}';

        $this->assertSame('NO', $this->parseTemplate($template, ['foo' => ['array']], true));
    }

    public function testParseThrowsWhenEndIfMissing()
    {
        $this->expectException(ParserException::class);

        $this->parseTemplate('{if foo}Yes');
    }

    private function readOutput(Parser $parser): string
    {
        $reflection = new \ReflectionProperty(Parser::class, 'output');
        \TestReflectionHelper::makeAccessible($reflection);

        return $reflection->getValue($parser);
    }

    private function setLastConditionalAnnotation(Parser $parser, object $annotation): void
    {
        $reflection = new \ReflectionProperty(Parser::class, 'last_conditional_annotation');
        \TestReflectionHelper::makeAccessible($reflection);
        $reflection->setValue($parser, $annotation);
    }
}
