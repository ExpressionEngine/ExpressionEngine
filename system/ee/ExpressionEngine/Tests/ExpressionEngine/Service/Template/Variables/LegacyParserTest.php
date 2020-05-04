<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Template\Variables;

use EllisLab\ExpressionEngine\Service\Template\Variables\LegacyParser;
use PHPUnit\Framework\TestCase;

class LegacyParserTest extends TestCase {

	public function setUp()
	{
		$this->parser = new LegacyParser();
	}

	public function tearDown()
	{
		$this->parser = NULL;
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
					'full_modifier' => ''
				]
			],
			[
				'prefixed:var',
				[
					'field_name' => 'var',
					'params' => [],
					'modifier' => '',
					'full_modifier' => ''
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
					'full_modifier' => ''
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
					'full_modifier' => 'some_mod'
				]
			],
			[
				'prefixed:var',
				[
					'field_name' => 'var',
					'params' => [],
					'modifier' => '',
					'full_modifier' => ''
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
					'full_modifier' => ''
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
					'full_modifier' => 'some_mod'
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
					'full_modifier' => 'multiple:modifiers'
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
				]
			]
		];

		return $tags;
	}
}
