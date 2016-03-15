<?php

namespace EllisLab\ExpressionEngine\Service\Model\Query;

use Closure;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Batch Query
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Batch {

	/**
	 * The offset on their query
	 */
	protected $starting_offset = 0;

	/**
	 * The limit on their query
	 */
	protected $maximum_size = INF;

	/**
	 * The batch size to use
	 */
	protected $batch_size = 100;

	/**
	 * EllisLab\ExpressionEngine\Service\Model\Query\Builder
	 */
	protected $builder = NULL;

	/**
	 * @param Builder $builder  Query to batch
	 */
	public function __construct(Builder $builder)
	{
		$this->builder = $builder;
		$this->starting_offset = (int) $builder->getOffset();

		// TODO technically have a bit of a 32 vs 64 bit snafu here
		$this->maximum_size = (int) $builder->getLimit();
	}

	/**
	 * Change the batch size. Default is 100
	 *
	 * @param Int $batch_size  New batch size
	 */
	public function setBatchSize($batch_size)
	{
		$this->batch_size = $batch_size;
	}

	/**
	 * Execute the batch
	 *
	 * @param Closure $callback  Called with each model result
	 * @return Int    Total rows processed
	 */
	public function process(Closure $callback)
	{
		$count = 0;
		$offset = $this->starting_offset;
		$limit = $this->batch_size;

		do
		{
			if ( ! $limit = $this->clampToLimit($count, $limit))
			{
				break;
			}

			// set the new limit and offset
			$result = $this->builder
				->offset($offset)
				->limit($limit)
				->all();

			if ( ! isset($result))
			{
				break;
			}

			// result is a collection
			$result->each($callback);

			$processed = count($result);

			$count += $processed;

			$offset += $processed;
		}
		while ($processed == $limit);

		return $count;
	}

	/**
	 * Make sure the batching limit doesn't exceed
	 * the custom limit they had set.
	 *
	 * @param Int $count  Number of records processed so far.
	 */
	protected function clampToLimit($count, $limit)
	{
		if ($count + $limit > $this->maximum_size)
		{
			return $this->maximum_size - $count;
		}

		return $limit;
	}
}

// EOF
