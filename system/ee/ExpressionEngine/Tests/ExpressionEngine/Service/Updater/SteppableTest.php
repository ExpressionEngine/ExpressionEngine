<?php

namespace ExpressionEngine\Tests\Service\Updater;

use ExpressionEngine\Service\Updater\SteppableTrait;
use Mockery;
use PHPUnit\Framework\TestCase;

class SteppableTest extends TestCase
{
    public function setUp(): void
    {
        $this->stepper = new Stepper();
    }

    public function tearDown(): void
    {
        $this->stepper = null;
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
    // if a fresh SteppableTrait object gets told to run an injected step right
    // off the bat; basically, injected steps need to always tell us where to go
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

    // Step string parser should allow for another step to be returned with
    // an argument
    public function testNestedInjectedStep()
    {
        $this->stepper = new StepperWithInjection();
        $this->stepper->runStep('injectNested[nestedStep[argument]]');

        $this->assertEquals('nestedStep[argument]', $this->stepper->getNextStep());
    }

    // Step string parser should allow for another step to be returned with
    // an argument
    public function testSkippedSteps()
    {
        $this->stepper = new StepperSkipSteps();
        $this->stepper->runStep('step1');

        $this->assertEquals('step3', $this->stepper->getNextStep());
    }
}

class Stepper
{
    use SteppableTrait;

    public $step1_called = false;
    public $step2_called = false;
    public $step3_called = false;

    public function __construct()
    {
        $this->setSteps([
            'step1',
            'step2',
            'step3'
        ]);
    }

    public function step1()
    {
        $this->step1_called = true;
    }

    public function step2()
    {
        $this->step2_called = true;
    }

    public function step3()
    {
        $this->step3_called = true;
    }
}

class StepperSkipSteps
{
    use SteppableTrait;

    public $step1_called = false;
    public $step2_called = false;
    public $step3_called = false;

    public function __construct()
    {
        $this->setSteps([
            'step1',
            'step2',
            'step3'
        ]);
    }

    public function step1()
    {
        $this->step1_called = true;
        $this->setNextStep('step3');
    }

    public function step2()
    {
        $this->step2_called = true;
    }

    public function step3()
    {
        $this->step3_called = true;
    }
}

class StepperWithInjection
{
    use SteppableTrait;

    public $step1_called = false;
    public $step2_called = false;
    public $step3_called = false;
    public $step4_called = false;
    public $injectedStepCalled = false;
    public $injectedWithParamCalled = false;
    public $injectedWithMultipleParamsCalled = false;
    public $injectedWithMultipleParamsResult = [];

    public function __construct()
    {
        $this->setSteps([
            'step1',
            'step2',
            'step3',
            'step4'
        ]);
    }

    public function step1()
    {
        $this->step1_called = true;
    }

    public function step2()
    {
        $this->step2_called = true;
        $this->setNextStep('injectedStep');
    }

    public function step3()
    {
        $this->step3_called = true;
        $this->setNextStep('step4');
    }

    public function injectedStep()
    {
        $this->injectedStepCalled = true;
        $this->setNextStep('injectedWithParam[hello]');
    }

    public function injectedWithParam($hello)
    {
        $this->injectedWithParamCalled = true;
        $this->setNextStep('injectedWithMultipleParams[' . $hello . ',1234]');
    }

    public function injectedFreshRequest()
    {
        $this->setNextStep('step3');
    }

    public function step4()
    {
        $this->step4_called = true;
    }

    public function injectedWithMultipleParams($string, $number)
    {
        $this->injectedWithMultipleParamsCalled = true;
        $this->injectedWithMultipleParamsResult = [$string, $number];
    }

    public function injectNested($step)
    {
        $this->setNextStep($step);
    }

    public function nestedStep($argument)
    {
        // Here just to pass the method_exists check from `testNestedInjectedStep`
    }
}
