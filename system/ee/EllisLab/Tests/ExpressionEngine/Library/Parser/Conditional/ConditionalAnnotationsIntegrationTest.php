<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Library\Parser\Conditional;

use EllisLab\ExpressionEngine\Library\Template\Annotation\Runtime as RuntimeAnnotations;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Runner as ConditionalRunner;
use PHPUnit\Framework\TestCase;

class ConditionalAnnotationsIntegrationTest extends TestCase {

	private $annotations;
	private $runner;

	public function setUp()
	{
		$this->annotations = new RuntimeAnnotations();
		$this->runner = new ConditionalRunner();

		$this->annotations->useSharedStore();
	}

	public function tearDown()
	{
		$this->annotations->clearSharedStore();
		unset($this->annotations);
		unset($this->runner);
	}

	public function testRemovesAnnotationOnEval()
	{
		$anno = $this->createTestAnnotation();

		$in = $anno.'{if 5 == 5}out{/if}';
		$this->assertEquals(
			'out',
			$this->runner->processConditionals($in, array())
		);

		$in = $anno.'{if 5 == 6}blanks{/if}';
		$this->assertEquals(
			'',
			$this->runner->processConditionals($in, array())
		);

		$in = $anno.'{if 5 == 7}no{if:else}yes{/if}';
		$this->assertEquals(
			'yes',
			$this->runner->processConditionals($in, array())
		);
	}

	public function testKeepsAnnotationOnRewrite()
	{
		$anno = $this->createTestAnnotation();

		$in = $anno.'{if var == 5}out{/if}';
		$out = $this->runner->processConditionals($in, array());

		$this->assertEquals($out, $in);
	}

	public function testKeepsChainedAnnotationsOnRewrite()
	{
		$anno1 = $this->createTestAnnotation();
		$anno2 = $this->createTestAnnotation();
		$anno3 = $this->createTestAnnotation();

		$in = $anno1.'{if var1 == 5}out{/if}';
		$in .= $anno2.'{if var2 == 5}out{/if}';
		$in .= $anno3.'{if var3 == 5}out{/if}';

		$out = $this->runner->processConditionals($in, array());

		$this->assertEquals($out, $in);
	}

	public function testRemovesCorrectInnerChainedAnnotationOnEval()
	{
		$anno1 = $this->createTestAnnotation();
		$anno2 = $this->createTestAnnotation();
		$anno3 = $this->createTestAnnotation();

		$in1 = $anno1.'{if var == 5}out{/if}';
		$in2 = $anno2.'{if 5 == 5}out{/if}';
		$in3 = $anno3.'{if var == 5}out{/if}';

		$out = $this->runner->processConditionals($in1.$in2.$in3, array());

		$this->assertEquals($out, $in1.'out'.$in3);
	}


	public function testKeepsNestedAnnotationsOnRewrite()
	{
		$anno1 = $this->createTestAnnotation();
		$anno2 = $this->createTestAnnotation();
		$anno3 = $this->createTestAnnotation();

		$in = $anno1.'{if var1 == 5}';
		$in .= $anno2.'{if var2 == 5}';
		$in .= $anno3.'{if var3 == 5}';
		$in .= 'out{/if}{/if}{/if}';

		$out = $this->runner->processConditionals($in, array());

		$this->assertEquals($out, $in);
	}

	public function testRemovesCorrectInnerNestedAnnotationOnEval()
	{
		$anno1 = $this->createTestAnnotation();
		$anno2 = $this->createTestAnnotation();
		$anno3 = $this->createTestAnnotation();

		$in1 = $anno1.'{if var1 == 5}';
		$in2 = $anno2.'{if 5 == 5}';
		$in3 = $anno3.'{if var3 == 5}';
		$body = 'out';
		$end3 = '{/if}';
		$end2 = '{/if}';
		$end1 = '{/if}';

		$out = $this->runner->processConditionals(
			$in1.$in2.$in3.$body.$end3.$end2.$end1,
			array()
		);

		$this->assertEquals(
			$out,
			$in1.$in3.$body.$end3.$end1
		);
	}

	private function createTestAnnotation()
	{
		return $this->annotations->create(array(
			'context' => 'test',
			'lineno' => 1,
			'conditional' => TRUE
		));
	}
}
