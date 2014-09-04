<?php
namespace EllisLab\ExpressionEngine\Service\Model\Relationship;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\Factory;
use EllisLab\ExpressionEngine\Service\AliasServiceInterface;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Relationship Query
 *
 * Manages querying on sub-relationships either via eager or lazy
 * queries.
 *
 * @package		ExpressionEngine
 * @subpackage	Model\Relationship
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class RelationshipQuery {

	private $info;
	private $from_model;

	/**
	 * @param Model $from_model model that is running the qyer
	 * @param AbstractRelationship $info  Relationship information
	 */
	public function __construct(Model $from_model, $info)
	{
		$this->info = $info;
		$this->from_model = $from_model;
	}

	/**
	 * Grab the information needed for the eager query.
	 *
	 * @param AliasServiceInterface  $alias_service
	 * @return RelationshipMeta  Database specific metdata
	 */
	public function eager(AliasServiceInterface $alias_service)
	{
		return new RelationshipMeta(
			$alias_service,
			$this->info->type,
			$this->info->name,
			$this->createFromArray(),
			$this->createToArray()
		);
	}

	/**
	 * Fetch data with a lazy query.
	 *
	 * @param ModelFactory $factory
	 * @return Model or Collection  query results
	 */
	public function lazy(Factory $factory)
	{
		$from_key = $this->info->key;
		$to_model = $this->info->model;
		$to_key   = $this->info->to_key;

		$from_id = $this->from_model->$from_key;

		$query = $factory->get($to_model);
		$query->filter($to_model.'.'.$to_key, $from_id);

		if ($this->info->is_collection)
		{
			return $query->all();
		}

		return $query->first();
	}

	/**
	 * Compile the from data for the eager query.
	 */
	private function createFromArray()
	{
		$from_class = get_class($this->from_model);

		return array(
			'model_class' => $from_class,
			'model_name'  => substr($from_class, strrpos($from_class, '\\') + 1),
			'key'		  => $this->info->key
		);
	}

	/**
	 * Compile the to data for the eager query.
	 */
	private function createToArray()
	{
		return array(
			'model_class'	=> $this->info->to_class,
			'model_name'	=> $this->info->model,
			'key'			=> $this->info->to_key
		);
	}
}
