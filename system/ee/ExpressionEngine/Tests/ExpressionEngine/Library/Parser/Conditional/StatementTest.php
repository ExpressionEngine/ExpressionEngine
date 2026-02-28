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

use ExpressionEngine\Library\Parser\Conditional\BooleanExpression;
use ExpressionEngine\Library\Parser\Conditional\Parser;
use ExpressionEngine\Library\Parser\Conditional\Statement;
use PHPUnit\Framework\TestCase;

class StatementTest extends TestCase
{
    public function testShouldAddBodyReturnsFalseWhenLastEvaluatedFalse()
    {
        $parser = $this->createParserMock();
        $statement = new Statement($parser);
        $parser->expects($this->never())->method('output');

        $statement->addIf($this->createExpression(true, false));

        $this->assertFalse($statement->shouldAddBody());
    }

    public function testShouldAddBodyReturnsFalseWhenStatementIsDone()
    {
        $parser = $this->createParserMock();
        $statement = new Statement($parser);
        $parser->expects($this->never())->method('output');

        $statement->addIf($this->createExpression(true, true));
        $statement->addElseIf($this->createExpression(true, false));

        $this->assertFalse($statement->shouldAddBody());
    }

    public function testCloseIfOutputsEndTagWhenConditionalWasRewritten()
    {
        $parser = $this->createParserMock();
        $statement = new Statement($parser);

        $parser->expects($this->exactly(2))
            ->method('output')
            ->withConsecutive(
                [$this->equalTo('{if unresolved_var}')],
                [$this->equalTo('{/if}')]
            );

        $statement->addIf($this->createExpression(false, false, 'unresolved_var'));
        $statement->closeIf();
    }

    public function testCloseIfDoesNotOutputEndTagWhenNoConditionalWasRewritten()
    {
        $parser = $this->createParserMock();
        $statement = new Statement($parser);
        $parser->expects($this->never())->method('output');

        $statement->addIf($this->createExpression(true, true));
        $statement->closeIf();
    }

    public function testAddElseReturnsFalseWhenPriorIfBranchWasTrue()
    {
        $parser = $this->createParserMock();
        $statement = new Statement($parser);
        $parser->expects($this->never())->method('output');

        $statement->addIf($this->createExpression(true, true));

        $this->assertFalse($statement->addElse());
    }

    public function testAddElseReturnsFalseWhenUnevaluatedPriorBranchAlreadyResolvedTrue()
    {
        $parser = $this->createParserMock();
        $statement = new Statement($parser);

        $parser->expects($this->exactly(2))
            ->method('output')
            ->withConsecutive(
                [$this->equalTo('{if unresolved_var}')],
                [$this->equalTo('{if:else}')]
            );

        $statement->addIf($this->createExpression(false, false, 'unresolved_var'));
        $statement->addElseIf($this->createExpression(true, true));

        $this->assertFalse($statement->addElse());
    }

    public function testAddElseOutputsElseAnnotationWhenAnyPriorBranchCannotEvaluate()
    {
        $parser = $this->createParserMock();
        $statement = new Statement($parser);

        $parser->expects($this->exactly(2))
            ->method('output')
            ->withConsecutive(
                [$this->equalTo('{if unresolved_var}')],
                [$this->equalTo('{if:else}')]
            );

        $statement->addIf($this->createExpression(false, false, 'unresolved_var'));

        $this->assertTrue($statement->addElse());
    }

    public function testAddElseDoesNotOutputElseAnnotationWhenAllPriorBranchesEvaluated()
    {
        $parser = $this->createParserMock();
        $statement = new Statement($parser);
        $parser->expects($this->never())->method('output');

        $statement->addIf($this->createExpression(true, false));

        $this->assertTrue($statement->addElse());
    }

    private function createParserMock(): Parser
    {
        $parser = $this->getMockBuilder(Parser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['output', 'outputLastAnnotation'])
            ->getMock();

        $parser->method('outputLastAnnotation');

        return $parser;
    }

    private function createExpression(bool $canEvaluate, bool $result = false, string $stringify = 'x == y'): BooleanExpression
    {
        $expression = $this->getMockBuilder(BooleanExpression::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['canEvaluate', 'evaluate', 'stringify'])
            ->getMock();

        $expression->method('canEvaluate')->willReturn($canEvaluate);
        $expression->method('stringify')->willReturn($stringify);
        $expression->method('evaluate')->willReturn($result);

        return $expression;
    }
}
