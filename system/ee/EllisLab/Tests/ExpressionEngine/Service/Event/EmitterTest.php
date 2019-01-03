<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Service\Event;

use Mockery as m;
use EllisLab\ExpressionEngine\Service\Event\Emitter;
use EllisLab\ExpressionEngine\Service\Event\Publisher;
use EllisLab\ExpressionEngine\Service\Event\Subscriber;
use EllisLab\ExpressionEngine\Service\Event\ReflexiveSubscriber;
use PHPUnit\Framework\TestCase;

class EmitterTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function testEmitsEvent()
	{
		$event = new Emitter();
		$receiver = m::mock(new EmitterTestReceiver());

		$listener = $this->newListener($receiver);

		$event->on('emits.event', $listener);

		$receiver->shouldReceive('triggered')->with('it', 'works')->once();

		$event->emit('emits.event', 'it', 'works');
	}

	public function testEmitsWithParameters()
	{
		$event = new Emitter();
		$receiver = m::mock(new EmitterTestReceiver());

		$listener = $this->newListener($receiver);

		$event->on('with.args', $listener);

		$receiver->shouldReceive('triggered')->with("test", 42)->once();
		$event->emit('with.args', "test", 42);

		$receiver->shouldReceive('triggered')->with("again", "again")->twice();
		$event->emit('with.args', "again", "again");
		$event->emit('with.args', "again", "again");
	}

	public function testAddMultipleListeners()
	{
		$event = new Emitter();
		$receiver = m::mock(new EmitterTestReceiver());

		$listener1 = $this->newListener($receiver);
		$listener2 = $this->newListener($receiver);

		$event->on('with.args', $listener1);
		$event->on('with.args', $listener2);

		$receiver->shouldReceive('triggered')->with("test", 42)->twice();
		$event->emit('with.args', "test", 42);
	}

	public function testRemoveAllListeners()
	{
		$event = new Emitter();
		$receiver = m::mock(new EmitterTestReceiver());

		$listener1 = $this->newListener($receiver);
		$listener2 = $this->newListener($receiver);

		$event->on('with.args', $listener1);
		$event->on('with.args', $listener2);

		$receiver->shouldReceive('triggered')->with("test", 42)->twice();

		$event->emit('with.args', "test", 42);

		$event->off('with.args');

		$event->emit('with.args', "test", 42);
	}

	public function testRemoveOneListener()
	{
		$event = new Emitter();
		$receiver = m::mock(new EmitterTestReceiver());

		$listener1 = $this->newListener($receiver);
		$listener2 = $this->newListener($receiver);

		$event->on('with.args', $listener1);
		$event->on('with.args', $listener2);

		$receiver->shouldReceive('triggered')->with("twice", 42)->twice();
		$receiver->shouldReceive('triggered')->with("only", "one")->once();

		$event->emit('with.args', "twice", 42);

		$event->off('with.args', $listener1);

		$event->emit('with.args', "only", "one");
	}

	public function testOnce()
	{
		$event = new Emitter();
		$receiver = m::mock(new EmitterTestReceiver());

		$receiver->shouldReceive('triggered')->with('only', 'once')->once();

		$listener = $this->newListener($receiver);

		$event->once('emit.once', $listener);

		$event->emit('emit.once', 'only', 'once');
		$event->emit('emit.once', 'only', 'once');
		$event->emit('emit.once', 'only', 'once');
		$event->emit('emit.once', 'only', 'once');
	}

	public function testSubscribeToEmitter()
	{
		$event = new Emitter();
		$subscriber = m::mock(new EmitterTestSubscriber());

		$subscriber->shouldReceive('onOne')->twice();
		$subscriber->shouldReceive('onTwo')->with('arg')->once();

		$event->subscribe($subscriber);
		$event->emit('one');
		$event->emit('two', 'arg');
		$event->emit('one');

		$event->unsubscribe($subscriber);
		$event->emit('one');
		$event->emit('two');
		$event->emit('two');
	}

	protected function newListener($receiver)
	{
		return function($arg1, $arg2) use ($receiver)
		{
			$receiver->triggered($arg1, $arg2);
		};
	}
}

class EmitterTestReceiver {

	public function triggered($first = NULL, $second = NULL, $third = NULL)
	{
		die('failed to mock EmitterTestReceiver');
	}

}

class EmitterTestSubscriber implements Subscriber {

	public function getSubscribedEvents()
	{
		return array('one', 'two');
	}

	public function onOne() {}

	public function onTwo($arg1) {}

}
