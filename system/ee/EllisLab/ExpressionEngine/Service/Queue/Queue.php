<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Queue;

use EllisLab\ExpressionEngine\Service\Model\Facade as ModelFacade;

/**
 * Consent Service
 */
class Queue {

	/**
	 * @var string $identifier A string to identify what is being queued
	 */
	protected $identifier;

	/**
	 * @var object $model_delegate An injected `ee('Model')` object
	 */
	protected $model_delegate;

	public function __construct(ModelFacade $model_delegate, $identifier)
	{
		$this->model_delegate = $model_delegate;
		$this->identifier    = $identifier;
	}

	public function enqueue(array $chunks)
	{
		$total = count($chunks);
		$step = 1;

		// Adding to the queue
		$last = $this->last();
		if ($last)
		{
			$total += $last->total;
			$step   = $last->step + 1;

			$this->model_delegate->get('Queue')
				->filter('identifier', $this->identifier)
				->set('total', $total)
				->update();
		}

		do
		{
			$this->model_delegate->make('Queue', [
				'identifier' => $this->identifier,
				'step' => $step,
				'total' => $total,
				'data' => array_shift($chunks)
			])->save();

			$step++;
		}
		while( ! empty($chunks));
	}

	protected function last()
	{
		return $this->model_delegate->get('Queue')
			->filter('identifier', $this->identifier)
			->order('step', 'desc')
			->first();
	}

	public function next()
	{
		$return = [];

		$next = $this->model_delegate->get('Queue')
			->filter('identifier', $this->identifier)
			->order('step', 'asc')
			->first();

		if ($next)
		{
			$return = $next->getValues();
			$next->delete();
		}

		return $return;
	}

	public function isQueued()
	{
		return (bool) $this->model_delegate->get('Queue')
			->filter('identifier', $this->identifier)
			->count();
	}

	public function reset()
	{
		return $this->model_delegate->get('Queue')
			->filter('identifier', $this->identifier)
			->delete();
	}

	public function clear()
	{
		return $this->reset();
	}

}
// END CLASS

// EOF
