<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Service\Updater\Steppable;
use Mockery;

class SteppableTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->stepper = new Stepper();
	}

	public function tearDown()
	{
		$this->stepper = NULL;
	}

	public function testRun()
	{
		$this->stepper->run();

		$this->assertTrue($this->stepper->step1_called);
		$this->assertTrue($this->stepper->step2_called);
		$this->assertTrue($this->stepper->step3_called);
	}

	public function testIncrementalSteps()
	{
		$first_step = $this->stepper->getFirstStep();
		$this->assertEquals('step1', $first_step);
		$this->assertEquals('step1', $this->stepper->getNextStep());

		$this->stepper->runStep($first_step);
		$this->assertTrue($this->stepper->step1_called);
		$this->assertFalse($this->stepper->step2_called);
		$this->assertFalse($this->stepper->step3_called);
		$this->assertEquals('step2', $this->stepper->getNextStep());

		$this->stepper->runStep('step2');
		$this->assertTrue($this->stepper->step1_called);
		$this->assertTrue($this->stepper->step2_called);
		$this->assertFalse($this->stepper->step3_called);
		$this->assertEquals('step3', $this->stepper->getNextStep());

		$this->stepper->runStep('step3');
		$this->assertTrue($this->stepper->step1_called);
		$this->assertTrue($this->stepper->step2_called);
		$this->assertTrue($this->stepper->step3_called);
		$this->assertFalse($this->stepper->getNextStep());
	}

	public function testInjectedSteps()
	{
		$this->stepper = new StepperWithInjection();
		$this->stepper->run();

		$this->assertTrue($this->stepper->step1_called);
		$this->assertTrue($this->stepper->step2_called);
		$this->assertTrue($this->stepper->step3_called);
		$this->assertTrue($this->stepper->injectedStepCalled);
		$this->assertTrue($this->stepper->injectedWithParamCalled);
		$this->assertTrue($this->stepper->injectedWithMultipleParamsCalled);
		$this->assertEquals(['hello', 1234], $this->stepper->injectedWithMultipleParamsResult);
	}

	public function testInjectedStepsManually()
	{
		$this->stepper = new StepperWithInjection();
		$this->stepper->runStep('step2');
		$this->assertEquals('injectedStep', $this->stepper->getNextStep());

		$this->stepper->runStep('injectedStep');
		$this->assertEquals('injectedWithParam[hello]', $this->stepper->getNextStep());

		$this->stepper->runStep('injectedWithParam[hello]');
		$this->assertEquals('injectedWithMultipleParams[hello,1234]', $this->stepper->getNextStep());

		$this->stepper->runStep('injectedWithMultipleParams[hello,1234]');
		$this->assertEquals('step3', $this->stepper->getNextStep());
	}

	// Nomenclature may not be clear here, but this is testing what happens
	// if a fresh Steppable object gets told to run an injected step right off
	// the bat; basically, injected steps need to always tell us where to go
	// afterwards to make sure order of steps remains intact
	public function testInjectedStepFreshRequest()
	{
		$this->stepper = new StepperWithInjection();
		$this->stepper->runStep('injectedFreshRequest');
		$this->assertEquals('step3', $this->stepper->getNextStep());

		$this->stepper->runStep('step3');
		$this->assertEquals('step4', $this->stepper->getNextStep());

		$this->stepper->runStep('step4');
		$this->assertFalse($this->stepper->getNextStep());
	}
}

class Stepper {
	use Steppable;

	public $steps = [
		'step1',
		'step2',
		'step3'
	];

	public $step1_called = FALSE;
	public $step2_called = FALSE;
	public $step3_called = FALSE;

	public function step1()
	{
		$this->step1_called = TRUE;
	}

	public function step2()
	{
		$this->step2_called = TRUE;
	}

	public function step3()
	{
		$this->step3_called = TRUE;
	}
}

class StepperWithInjection {
	use Steppable;

	public $steps = [
		'step1',
		'step2',
		'step3',
		'step4'
	];

	public $step1_called = FALSE;
	public $step2_called = FALSE;
	public $step3_called = FALSE;
	public $step4_called = FALSE;
	public $injectedStepCalled = FALSE;
	public $injectedWithParamCalled = FALSE;
	public $injectedWithMultipleParamsCalled = FALSE;
	public $injectedWithMultipleParamsResult = [];

	public function step1()
	{
		$this->step1_called = TRUE;
	}

	public function step2()
	{
		$this->step2_called = TRUE;
		return 'injectedStep';
	}

	public function step3()
	{
		$this->step3_called = TRUE;
		return 'step4';
	}

	public function injectedStep()
	{
		$this->injectedStepCalled = TRUE;
		return 'injectedWithParam[hello]';
	}

	public function injectedWithParam($hello)
	{
		$this->injectedWithParamCalled = TRUE;
		return 'injectedWithMultipleParams['.$hello.',1234]';
	}

	public function injectedFreshRequest()
	{
		return 'step3';
	}

	public function step4()
	{
		$this->step4_called = TRUE;
	}

	public function injectedWithMultipleParams($string, $number)
	{
		$this->injectedWithMultipleParamsCalled = TRUE;
		$this->injectedWithMultipleParamsResult = [$string, $number];
	}
}
